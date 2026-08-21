import {
    createButton,
    createPasteButton,
    createSubmitButton,
    type CraftButton,
    type CreateButtonConfig,
} from '@craftcms/ui';
import {createTextInput} from '@craftcms/ui/factory';

// jQuery remains a page global — the patched `Craft.ui.create*` methods keep
// returning jQuery collections, since that's the legacy call-site contract.
declare const $: any;

/**
 * `Craft.ui.create*` compatibility shim.
 *
 * Patches the legacy `Craft.ui` button factories to build `@craftcms/ui`
 * custom elements via the importable creators (`createButton`,
 * `createSubmitButton`, `createPasteButton` from `@craftcms/ui`) instead of
 * legacy `<button class="btn">` markup. The legacy call-site contract is
 * preserved:
 *
 * - the methods still return a **jQuery collection** wrapping the element;
 * - the element carries a slotted `<span class="label">`, so
 *   `.find('.label').text(…)` keeps working;
 * - legacy **style classes** call sites toggle (`disabled`, `loading`,
 *   `submit`, `secondary`, `small`, `big`) are bridged onto the component's
 *   properties by a `class`-attribute observer, so chains like
 *   `.addClass('loading')` / `.removeClass('disabled')` still take effect.
 *
 * New code should import the creators directly and use the component
 * properties instead; this shim only exists so the remaining legacy call
 * sites migrate on their own schedule.
 *
 * Load order: `Craft.ui` is assigned wholesale by the legacy bundle's
 * `UI.js`, which may load before or after this module (Vite entry vs.
 * fragment-injected legacy bundle). When it hasn't loaded yet, a property
 * setter on `Craft.ui` patches the object the moment it's assigned.
 */

/**
 * Legacy style classes → component property sync. The classes stay on the
 * element (some double as selectors, e.g. `.submit`); the observer just
 * mirrors their presence onto the properties that actually drive the
 * component. One-way: class list → properties.
 */
const CLASS_SYNCS: Array<(button: CraftButton, classes: DOMTokenList) => void> =
    [
        (button, classes) => {
            button.disabled = classes.contains('disabled');
        },
        (button, classes) => {
            button.loading = classes.contains('loading');
        },
        (button, classes) => {
            if (classes.contains('submit')) {
                button.variant = 'primary';
            }
        },
        (button, classes) => {
            if (classes.contains('secondary')) {
                button.variant = 'solid';
            }
        },
        (button, classes) => {
            if (classes.contains('small')) {
                button.size = 'small';
            } else if (classes.contains('big')) {
                button.size = 'large';
            }
        },
    ];

function bridgeLegacyClasses(button: CraftButton): CraftButton {
    const sync = () => {
        for (const apply of CLASS_SYNCS) {
            apply(button, button.classList);
        }
    };

    sync();
    new MutationObserver(sync).observe(button, {
        attributes: true,
        attributeFilter: ['class'],
    });

    return button;
}

type LegacyCreator = (config?: CreateButtonConfig) => any;

function patchUi(
    ui: Record<string, unknown> & {
        createButton?: LegacyCreator;
        createSubmitButton?: LegacyCreator;
        createPasteButton?: LegacyCreator;
    }
): void {
    ui.createButton = (config = {}) =>
        $(bridgeLegacyClasses(createButton(config)));
    ui.createSubmitButton = (config = {}) =>
        $(bridgeLegacyClasses(createSubmitButton(config)));
    ui.createPasteButton = (config = {}) =>
        $(bridgeLegacyClasses(createPasteButton(config)));
    (ui as any).createTextInput = (config = {}) => $(createTextInput(config));
}

declare global {
    interface Window {
        Craft: any;
    }
}

window.Craft = window.Craft || {};

if (window.Craft.ui) {
    patchUi(window.Craft.ui);
} else {
    // The legacy bundle hasn't assigned `Craft.ui` yet — patch it on arrival.
    let currentUi: any;
    Object.defineProperty(window.Craft, 'ui', {
        configurable: true,
        enumerable: true,
        get: () => currentUi,
        set: (value) => {
            currentUi = value;
            if (value) {
                patchUi(value);
            }
        },
    });
}
