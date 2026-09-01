import type CraftButton from '../components/button/button.js';
import type {ButtonVariant} from '../components/button/button.js';
import '../components/button/button.js';
import {t} from './translate.js';
import {capitalize} from './string.js';

/**
 * Programmatic element creators — the modern replacements for the legacy
 * `Craft.ui.create*` factories. Each returns a `@craftcms/ui` custom element
 * (a plain `HTMLElement`, not a jQuery collection) configured through the
 * same config keys the legacy factory accepted, plus the component's own
 * modern options (`variant`, `size`).
 *
 * New code should import these directly. The legacy `Craft.ui.create*`
 * methods keep working — `resources/js/modules/ui` patches them to delegate
 * here and wraps the result for jQuery-era call sites.
 */

/**
 * Config for {@link createButton}. The legacy keys (`class`, `html`,
 * `spinner`, `toggle`, `controls`, …) match `Craft.ui.createButton`'s config
 * surface so legacy call sites can migrate mechanically.
 */
export interface CreateButtonConfig {
  /** The button's `type`. Defaults to `button`. */
  type?: 'button' | 'submit' | 'reset';
  id?: string;
  /**
   * Class names for the element. Legacy style classes (`disabled`,
   * `loading`, `submit`, …) have no effect on `craft-button` by themselves —
   * use the modern properties instead (the `Craft.ui` compatibility shim
   * maps them for old call sites).
   */
  class?: string;
  /** Plain-text label, rendered into a slotted `<span class="label">`. */
  label?: string;
  /** HTML label (legacy `html` key); ignored when `label` is set. */
  html?: string;
  /** Icon name, rendered by the component itself. */
  icon?: string | null;
  ariaLabel?: string;
  ariaDescribedBy?: string;
  role?: string;
  /** Marks the button as a disclosure toggle (`aria-expanded="false"`). */
  toggle?: boolean;
  /** `aria-controls` target id. */
  controls?: string;
  /** `data-*` attributes. */
  data?: Record<string, string | number | boolean>;
  /**
   * Legacy key: appended a spinner element. `craft-button` has a built-in
   * spinner driven by its `loading` property, so this is accepted but
   * ignored.
   */
  spinner?: boolean;
  disabled?: boolean;
  loading?: boolean;
  variant?: ButtonVariant;
  size?: CraftButton['size'];
}

/**
 * Creates a `<craft-button>`. The label lives in a slotted
 * `<span class="label">`, preserving the legacy markup contract that call
 * sites read/update the label through a `.label` child.
 */
export function createButton(config: CreateButtonConfig = {}): CraftButton {
  const button = document.createElement('craft-button') as CraftButton;

  button.type = config.type ?? 'button';

  if (config.id) {
    button.id = config.id;
  }
  if (config.class) {
    for (const token of config.class.split(/\s+/).filter(Boolean)) {
      button.classList.add(token);
    }
  }
  if (config.ariaLabel) {
    button.setAttribute('aria-label', config.ariaLabel);
  }
  if (config.ariaDescribedBy) {
    button.setAttribute('aria-describedby', config.ariaDescribedBy);
  }
  if (config.role) {
    button.setAttribute('role', config.role);
  }
  if (config.toggle) {
    button.setAttribute('aria-expanded', 'false');
  }
  if (config.controls) {
    button.setAttribute('aria-controls', config.controls);
  }
  if (config.data) {
    for (const [key, value] of Object.entries(config.data)) {
      button.setAttribute(`data-${key}`, String(value));
    }
  }

  if (config.icon) {
    button.icon = config.icon;
  }

  if (config.label || config.html) {
    const label = document.createElement('span');
    label.className = 'label';
    if (config.label) {
      label.textContent = config.label;
    } else {
      label.innerHTML = config.html!;
    }
    button.append(label);
  }

  if (config.disabled) {
    button.disabled = true;
  }
  if (config.loading) {
    button.loading = true;
  }
  if (config.variant) {
    button.variant = config.variant;
  }
  if (config.size) {
    button.size = config.size;
  }

  return button;
}

/**
 * Creates a submit `<craft-button>` (legacy `Craft.ui.createSubmitButton`).
 * Accent variant by default; keeps the legacy `submit` class token for
 * selector compatibility.
 */
export function createSubmitButton(
  config: CreateButtonConfig = {}
): CraftButton {
  const button = createButton({
    ...config,
    type: 'submit',
    label: config.label || t('Submit'),
    variant: config.variant ?? 'accent',
  });
  button.classList.add('submit');

  return button;
}

/**
 * Creates a "Paste elements" `<craft-button>` (legacy
 * `Craft.ui.createPasteButton`). Keeps the legacy `paste-btn` class token
 * for selector compatibility.
 */
export function createPasteButton(
  config: CreateButtonConfig = {}
): CraftButton {
  const button = createButton({
    ...config,
    icon: 'duplicate',
    label: config.label || capitalize(t('Paste {type}', {type: t('elements')})),
  });
  button.classList.add('paste-btn');

  return button;
}
