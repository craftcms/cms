export interface TextInputConfig {
  type?: string;
  inputmode?: string;
  id?: string;
  size?: number;
  name?: string;
  value?: string | number;
  maxlength?: number;
  /** Applied as-is; the legacy shim suppresses this on mobile before calling. */
  autofocus?: boolean;
  autocomplete?: boolean | string;
  disabled?: boolean;
  readonly?: boolean;
  title?: string;
  placeholder?: string;
  step?: number | string;
  min?: number | string;
  max?: number | string;
  describedBy?: string;
  showCharsLeft?: boolean;
  class?: string | string[];
  /** Arbitrary extra attributes (booleans, `aria`/`data` maps, class, style). */
  inputAttributes?: Record<string, unknown>;
}

/**
 * Creates a plain `<input class="text">` — the jQuery-free twin of the legacy
 * `Craft.ui.createTextInput`. Unlike the component factories this returns a
 * native input (not a custom element); it's the shared building block many CP
 * inputs are made of.
 *
 * The Garnish-specific enhancements the legacy method layered on top —
 * `Garnish.NiceText` (placeholder animation / chars-left counter), the
 * `.passwordwrapper` wrapper, and mobile-browser autofocus suppression — stay
 * in the legacy `Craft.ui.createTextInput` shim, which wraps the element this
 * returns. This function only produces the markup.
 */
export function createTextInput(
  config: TextInputConfig = {}
): HTMLInputElement {
  const input = document.createElement('input');
  const type = config.type ?? 'text';
  input.type = type;

  const classes = ['text'];
  if (Array.isArray(config.class)) {
    classes.push(...config.class);
  } else if (config.class) {
    classes.push(config.class);
  }
  if (config.placeholder) {
    classes.push('nicetext');
  }
  if (type === 'password') {
    classes.push('password');
  }
  if (config.disabled) {
    classes.push('disabled');
  }
  if (config.size === undefined) {
    classes.push('fullwidth');
  }
  input.className = classes.join(' ');

  if (config.inputmode) {
    input.setAttribute('inputmode', config.inputmode);
  }
  if (config.id) {
    input.id = config.id;
  }
  if (config.size !== undefined) {
    input.setAttribute('size', String(config.size));
  }
  if (config.name) {
    input.name = config.name;
  }
  if (config.value !== undefined && config.value !== null) {
    input.setAttribute('value', String(config.value));
  }
  if (config.maxlength !== undefined) {
    input.setAttribute('maxlength', String(config.maxlength));
  }
  if (config.autofocus) {
    input.autofocus = true;
  }

  // Defaults to off, matching the legacy `autocomplete: false` default.
  const autocomplete = config.autocomplete ?? false;
  input.setAttribute(
    'autocomplete',
    typeof autocomplete === 'boolean'
      ? autocomplete
        ? 'on'
        : 'off'
      : autocomplete
  );

  if (config.disabled) {
    input.disabled = true;
  }
  if (config.readonly) {
    input.readOnly = true;
  }
  if (config.title) {
    input.title = config.title;
  }
  if (config.placeholder) {
    input.placeholder = config.placeholder;
  }
  if (config.step !== undefined) {
    input.setAttribute('step', String(config.step));
  }
  if (config.min !== undefined) {
    input.setAttribute('min', String(config.min));
  }
  if (config.max !== undefined) {
    input.setAttribute('max', String(config.max));
  }
  if (config.describedBy) {
    input.setAttribute('aria-describedby', config.describedBy);
  }

  if (config.inputAttributes) {
    addAttributes(input, config.inputAttributes);
  }

  if (config.showCharsLeft && config.maxlength !== undefined) {
    input.setAttribute('data-show-chars-left', '');
    // Logical (direction-aware) padding — the legacy code branched on
    // `Craft.orientation` to pick padding-left/right; `padding-inline-end`
    // does the same without the global.
    input.style.paddingInlineEnd =
      7.2 * String(config.maxlength).length + 14 + 'px';
  }

  return input;
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return (
    typeof value === 'object' &&
    value !== null &&
    (Object.getPrototypeOf(value) === Object.prototype ||
      Object.getPrototypeOf(value) === null)
  );
}

/**
 * jQuery-free port of `Craft.ui.addAttributes`: applies boolean attributes,
 * nested `aria`/`data`/`ng` maps, and class/style objects. Non-boolean,
 * non-object top-level values are ignored — matching the legacy method.
 */
function addAttributes(
  el: HTMLElement,
  attributes: Record<string, unknown>
): void {
  for (const name in attributes) {
    const value = attributes[name];
    if (typeof value === 'boolean') {
      if (value) {
        el.setAttribute(name, '');
      }
    } else if (isPlainObject(value)) {
      if (['aria', 'data', 'data-ng', 'ng'].includes(name)) {
        for (const n in value) {
          const v = value[n];
          if (typeof v === 'object' && v !== null) {
            el.setAttribute(`${name}-${n}`, JSON.stringify(v));
          } else if (typeof v === 'boolean') {
            if (v) {
              el.setAttribute(`${name}-${n}`, '');
            }
          } else if (v !== null && v !== undefined) {
            el.setAttribute(`${name}-${n}`, String(v));
          }
        }
      } else if (name === 'class') {
        for (const cls in value) {
          if (value[cls]) {
            el.classList.add(cls);
          }
        }
      } else if (name === 'style') {
        Object.assign(el.style, value);
      } else {
        el.setAttribute(name, String(value));
      }
    }
  }
}
