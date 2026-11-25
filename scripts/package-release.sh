#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

tag=${1:?Specify a release tag, such as v1.0.0}
output_dir=${2:?Specify an absolute output directory}
if [[ ! "$tag" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ || "$output_dir" != /* ]]; then
	printf 'Expected a vMAJOR.MINOR.PATCH tag and an absolute output directory.\n' >&2
	exit 1
fi

version=${tag#v}
plugin_version=$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*$/\1/p' celestial-cookie-consent.php)
if [[ "$plugin_version" != "$version" ]] || ! node -e '
	const version = process.argv[1];
	const lock = require("./package-lock.json");
	process.exit(Number([require("./package.json").version, lock.version, lock.packages[""].version].some(value => value !== version)));
' "$version"; then
	printf 'The tag must match the versions in the plugin header, package.json and package-lock.json.\n' >&2
	exit 1
fi

staging=$(mktemp -d)
trap 'rm -rf "$staging"' EXIT
plugin=celestial-cookie-consent
archive="$plugin-$version.zip"

awk -v version="$version" '
	/^## / {
		if (found) exit
		if ($2 == version) found = 1
		next
	}
	found { print }
	END { if (!found) exit 1 }
' CHANGELOG.md > "$staging/release-notes.md" || {
	printf 'Add a CHANGELOG.md entry for %s.\n' "$version" >&2
	exit 1
}

npm run build
for file in build/block/banner/{block.json,render.php,index.js,index.asset.php,view.js,view.asset.php,style-index.css} build/backend/{index.js,index.asset.php}; do
	test -s "$file"
done

mkdir -p "$staging/$plugin" "$output_dir"
cp -R celestial-cookie-consent.php README.md CHANGELOG.md theme.example.json languages includes build "$staging/$plugin/"
(
	cd "$staging"
	zip -qr "$archive" "$plugin"
)
unzip -tq "$staging/$archive"
mv "$staging/$archive" "$staging/release-notes.md" "$output_dir/"
