(() => {
	const banner = document.getElementById('celestial-cookie-consent-banner');
	if (!banner) {
		return;
	}

	const panel = banner.querySelector('section');
	const open = banner.querySelector('[data-open]');
	const customize = banner.querySelector('[data-customize]');
	const dialog = banner.querySelector('dialog');
	const inputs = Array.from(banner.querySelectorAll('[data-script-choice]'));
	const payload = banner.querySelector('[data-scripts]');
	const scripts = payload ? JSON.parse(payload.textContent) : [];
	const started = new Set();
	const key = 'celestial_cookie_consent_choice';
	if (new URLSearchParams(location.search).has('debug')) {
		const reset = banner.querySelector('[data-debug-reset]');
		reset.hidden = false;
		reset.addEventListener('click', () => {
			const secure = location.protocol === 'https:' ? '; Secure' : '';
			document.cookie = key + '=; Path=/; Max-Age=0; SameSite=Lax' + secure;
			location.reload();
		});
	}
	const version = banner.dataset.version;
	const stored = document.cookie.split(/;\s*/).find((entry) => entry.startsWith(key + '='));
	const [savedChoice, savedVersion = ''] = (stored?.slice(key.length + 1) || '').split('.');
	const all = '1'.repeat(inputs.length);
	const none = '0'.repeat(inputs.length);
	let selection = null;

	if (savedVersion === version) {
		if (savedChoice === 'accepted') {
			selection = all;
		} else if (savedChoice === 'rejected') {
			selection = none;
		} else if (savedChoice.startsWith('custom-')) {
			const bits = savedChoice.slice(7);
			if (bits.length === inputs.length && /^[01]*$/.test(bits)) {
				selection = bits;
			}
		}
	}

	function startAllowedScripts() {
		scripts.forEach((code, index) => {
			if (selection?.[index] !== '1' || started.has(index)) {
				return;
			}
			started.add(index);
			const script = document.createElement('script');
			script.textContent = code;
			document.head.appendChild(script);
		});
	}

	function openPreferences() {
		inputs.forEach((input, index) => {
			input.checked = selection?.[index] === '1';
		});
		dialog.showModal();
	}

	banner.hidden = false;
	panel.hidden = selection !== null;
	open.hidden = !panel.hidden;
	startAllowedScripts();

	open.addEventListener('click', openPreferences);
	customize.addEventListener('click', openPreferences);
	dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());

	banner.querySelectorAll('[data-choice]').forEach((button) => {
		button.addEventListener('click', () => {
			const action = button.dataset.choice;
			let storedChoice = 'rejected';
			selection = none;

			if (action === 'all') {
				storedChoice = 'accepted';
				selection = all;
			} else if (action === 'custom') {
				selection = inputs.map((input) => input.checked ? '1' : '0').join('');
				storedChoice = 'custom-' + selection;
			}

			const secure = location.protocol === 'https:' ? '; Secure' : '';
			document.cookie = key + '=' + storedChoice + '.' + version + '; Path=/; Max-Age=15552000; SameSite=Lax' + secure;
			panel.hidden = true;
			if (dialog.open) {
				dialog.close();
			}
			open.hidden = false;
			open.focus();

			if (Array.from(started).some((index) => selection[index] !== '1')) {
				location.reload();
			} else {
				startAllowedScripts();
			}
		});
	});
})();
