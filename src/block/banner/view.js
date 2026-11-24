import { getContext, getElement, store } from '@wordpress/interactivity';

const key = 'celestial_cookie_consent_choice';
const started = new Set();
const preferencesHash = '#cookie-preferences';

function cookieAttributes() {
	return '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
}

function readConsentCookie() {
	try {
		const stored = document.cookie.split(/;\s*/).find((entry) => entry.startsWith(key + '='));
		return stored?.slice(key.length + 1);
	} catch {
		return null;
	}
}

function writeConsentCookie(value, maxAge) {
	try {
		document.cookie = key + '=' + value + '; Max-Age=' + maxAge + cookieAttributes();
		return readConsentCookie() === (maxAge === 0 ? undefined : value);
	} catch {
		return false;
	}
}

function showStorageError(banner) {
	const dialog = banner.querySelector('dialog');
	const container = dialog.open ? dialog.querySelector('header + div') || dialog : banner;
	let notice = container.querySelector(':scope > [data-ccc-storage-error]');

	if (!notice) {
		notice = document.createElement('p');
		notice.setAttribute('role', 'alert');
		notice.setAttribute('data-ccc-storage-error', '');
		container.appendChild(notice);
	}

	notice.textContent = state.labels.storageError;
}

function startAllowedScripts() {
	state.scripts.forEach(({ code, index }) => {
		if (state.selection?.[index] !== '1' || started.has(index)) {
			return;
		}

		started.add(index);
		const script = document.createElement('script');
		script.textContent = code;
		document.head.appendChild(script);
	});
}

function getBanner() {
	return getElement().ref.closest('[data-wp-interactive="celestial-cookie-consent"]');
}

function openPreferences(dialog) {
	if (!dialog.open) {
		state.draft = state.scripts.map(({ index }) => state.selection?.[index] === '1');
		dialog.showModal();
	}
}

function openPreferencesFromHash(dialog) {
	if (location.hash !== preferencesHash) {
		return;
	}

	// Clear the fragment so the same link can reopen the dialog.
	history.replaceState(history.state, '', location.pathname + location.search);
	openPreferences(dialog);
}

function enableBackdropDismiss(dialog) {
	if ('closedBy' in dialog) {
		dialog.closedBy = 'any';
		return;
	}

	// Older browsers retarget backdrop clicks to the dialog itself.
	function isBackdrop(event) {
		if (event.target !== dialog) {
			return false;
		}
		const { left, right, top, bottom } = dialog.getBoundingClientRect();
		return event.clientX < left || event.clientX >= right || event.clientY < top || event.clientY >= bottom;
	}

	let startedOnBackdrop = false;
	dialog.addEventListener('pointerdown', (event) => {
		startedOnBackdrop = isBackdrop(event);
	});
	dialog.addEventListener('click', (event) => {
		if (startedOnBackdrop && isBackdrop(event)) {
			dialog.close();
		}
		startedOnBackdrop = false;
	});
}

const { state } = store('celestial-cookie-consent', {
	state: {
		isInitializing: true,
		debug: false,
		selection: null,
		draft: [],
		get isPanelVisible() {
			return state.selection === null;
		},
		get hidePanel() {
			return !state.isPanelVisible;
		},
		get hideReset() {
			return !state.debug;
		},
		get hidePolicy() {
			return !state.policyUrl;
		},
		get isChecked() {
			return Boolean(state.draft[getContext().item.index]);
		},
		get hideDescription() {
			return !getContext().item.description;
		},
	},
	callbacks: {
		initialize() {
			const dialog = getBanner().querySelector('dialog');
			enableBackdropDismiss(dialog);
			const [choice, version = ''] = (readConsentCookie() || '').split('.');
			const count = state.scripts.length;

			if (version === state.version) {
				if (choice === 'accepted' || choice === 'rejected') {
					state.selection = (choice === 'accepted' ? '1' : '0').repeat(count);
				} else if (choice.startsWith('custom-')) {
					const bits = choice.slice(7);
					if (bits.length === count && /^[01]*$/.test(bits)) {
						state.selection = bits;
					}
				}
			}

			state.debug = new URLSearchParams(location.search).has('debug');
			state.isInitializing = false;
			window.addEventListener('hashchange', () => openPreferencesFromHash(dialog));
			requestAnimationFrame(() => openPreferencesFromHash(dialog));
			startAllowedScripts();
		},
	},
	actions: {
		openPreferences() {
			openPreferences(getBanner().querySelector('dialog'));
		},
		closePreferences() {
			getBanner().querySelector('dialog').close();
		},
		toggleScript(event) {
			state.draft[getContext().item.index] = event.target.checked;
		},
		reset() {
			if (!writeConsentCookie('', 0)) {
				showStorageError(getBanner());
				return;
			}
			location.reload();
		},
		save() {
			const banner = getBanner();
			const action = getElement().ref.dataset.choice;
			let storedChoice;
			let selection;

			if (action === 'custom') {
				selection = state.draft.map((checked) => checked ? '1' : '0').join('');
				storedChoice = 'custom-' + selection;
			} else {
				const accept = action === 'all';
				selection = (accept ? '1' : '0').repeat(state.scripts.length);
				storedChoice = accept ? 'accepted' : 'rejected';
			}

			const value = storedChoice + '.' + state.version;
			if (!writeConsentCookie(value, 15552000)) {
				showStorageError(banner);
				return;
			}

			state.selection = selection;
			banner.querySelectorAll('[data-ccc-storage-error]').forEach((notice) => notice.remove());
			banner.querySelector('dialog').close();

			if (Array.from(started).some((index) => state.selection[index] !== '1')) {
				location.reload();
			} else {
				startAllowedScripts();
			}
		},
	},
});
