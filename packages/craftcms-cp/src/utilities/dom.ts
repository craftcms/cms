let existingCss: string[] | null = null;
let existingJs: string[] | null = null;

/**
 * Disposer returned from {@link appendHeadHtml} / {@link appendBodyHtml}.
 * Calling it removes any nodes that were appended and rolls back the
 * dedup cache entries for stylesheets and scripts that were added.
 */
export type AppendHtmlDisposer = () => void;

function waitForScript(script: HTMLScriptElement): Promise<void> {
  return new Promise((resolve) => {
    script.addEventListener('load', () => resolve(), {once: true});
    script.addEventListener('error', () => resolve(), {once: true});
  });
}

export async function appendElementHtml(
  html: string,
  parent: HTMLElement
): Promise<AppendHtmlDisposer> {
  const appended: Node[] = [];
  const cssAdded: string[] = [];
  const jsAdded: string[] = [];

  const dispose: AppendHtmlDisposer = () => {
    for (const node of appended) {
      node.parentNode?.removeChild(node);
    }
    if (existingCss) {
      for (const href of cssAdded) {
        const idx = existingCss.indexOf(href);
        if (idx !== -1) {
          existingCss.splice(idx, 1);
        }
      }
    }
    if (existingJs) {
      for (const src of jsAdded) {
        const idx = existingJs.indexOf(src);
        if (idx !== -1) {
          existingJs.splice(idx, 1);
        }
      }
    }
  };

  if (!html) {
    return dispose;
  }

  const div = document.createElement('div');
  div.innerHTML = html.trim();
  const nodes = Array.from(div.childNodes);

  for (const node of nodes) {
    if (node instanceof HTMLLinkElement && node.href) {
      if (!existingCss) {
        existingCss = Array.from(document.querySelectorAll('link[href]')).map(
          (n) => (n as HTMLLinkElement).href.replace(/&/g, '&amp;')
        );
      }

      const href = node.href.replace(/&/g, '&amp;');
      if (existingCss.includes(href)) {
        continue;
      }

      existingCss.push(href);
      cssAdded.push(href);
      const link = document.createElement('link');
      Array.from(node.attributes).forEach((attr) => {
        link.setAttribute(attr.name, attr.value);
      });
      parent.appendChild(link);
      appended.push(link);
      continue;
    }

    if (node instanceof HTMLScriptElement) {
      const script = document.createElement('script');
      let scriptLoaded: Promise<void> | null = null;

      Array.from(node.attributes).forEach((attr) => {
        script.setAttribute(attr.name, attr.value);
      });

      if (node.src) {
        if (!existingJs) {
          existingJs = Array.from(document.querySelectorAll('script[src]')).map(
            (n) => (n as HTMLScriptElement).src.replace(/&/g, '&amp;')
          );
        }

        const src = node.src.replace(/&/g, '&amp;');
        if (existingJs.includes(src)) {
          continue;
        }

        existingJs.push(src);
        jsAdded.push(src);
        script.async = false;
        scriptLoaded = waitForScript(script);
      } else {
        script.textContent = node.textContent;
      }

      parent.appendChild(script);
      appended.push(script);

      if (scriptLoaded) {
        await scriptLoaded;
      }

      continue;
    }

    const cloned = node.cloneNode(true);
    parent.appendChild(cloned);
    appended.push(cloned);
  }

  return dispose;
}

/**
 * Appends HTML to the page `<head>`.
 *
 * Returns a disposer that removes the appended nodes when called.
 */
export async function appendHeadHtml(
  html: string
): Promise<AppendHtmlDisposer> {
  return appendElementHtml(html, document.head);
}

/**
 * Appends HTML to the page `<body>`.
 *
 * Returns a disposer that removes the appended nodes when called.
 */
export async function appendBodyHtml(
  html: string
): Promise<AppendHtmlDisposer> {
  return appendElementHtml(html, document.body);
}

/**
 * A custom element that carries form state on the host (e.g. Lion-based
 * controls like `craft-select-rich` that have no light-DOM posting input).
 */
interface FormValueHost extends HTMLElement {
  name?: unknown;
  disabled?: unknown;
  modelValue?: unknown;
  serializedValue?: unknown;
  value?: unknown;
}

/** Lion choice-input model shape (`craft-checkbox`, `craft-option`, …). */
function isChoiceModelValue(
  modelValue: unknown
): modelValue is {value: unknown; checked: boolean} {
  return (
    typeof modelValue === 'object' &&
    modelValue !== null &&
    'checked' in modelValue &&
    typeof (modelValue as {checked: unknown}).checked === 'boolean'
  );
}

/**
 * Serializes every named form control inside a container into a URL-encoded
 * string, mirroring jQuery's `.serialize()` semantics (unchecked checkboxes
 * and radios, disabled controls, and buttons/files are omitted).
 *
 * Custom elements that hold their value on the host (a `name` plus a Lion
 * `modelValue`/`serializedValue`, or a `value` property) are serialized too —
 * unless their light DOM contains a named native control, in which case that
 * control is the posting surface and already covered (the SSR pattern used by
 * `craft-checkbox`, `craft-switch`, etc.).
 *
 * Unlike `FormData`, this works on any element — not just `<form>` — so it can
 * scope serialization to a fragment of a page-level form, such as a legacy
 * HTML island whose inputs aren't part of an Inertia form's state. Values are
 * read from live DOM properties, so user edits are always captured (cloning
 * into a detached form would only see attributes).
 */
export function serializeFormInputs(container: HTMLElement): string {
  const params = new URLSearchParams();
  const controls = container.querySelectorAll<
    HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
  >('input[name], select[name], textarea[name]');

  for (const control of controls) {
    if (control.disabled) {
      continue;
    }

    if (control instanceof HTMLInputElement) {
      if (
        ['file', 'submit', 'button', 'reset', 'image'].includes(control.type)
      ) {
        continue;
      }

      if (
        (control.type === 'checkbox' || control.type === 'radio') &&
        !control.checked
      ) {
        continue;
      }
    }

    if (control instanceof HTMLSelectElement && control.multiple) {
      for (const option of control.selectedOptions) {
        params.append(control.name, option.value);
      }

      continue;
    }

    params.append(control.name, control.value);
  }

  for (const host of container.querySelectorAll<FormValueHost>('*')) {
    if (!host.tagName.includes('-')) {
      continue;
    }

    const name = host.name;

    if (typeof name !== 'string' || name === '' || host.disabled === true) {
      continue;
    }

    // A named native control in the host's light DOM is the posting surface;
    // it was already serialized above.
    if (host.querySelector('input[name], select[name], textarea[name]')) {
      continue;
    }

    if (isChoiceModelValue(host.modelValue)) {
      if (host.modelValue.checked) {
        params.append(name, String(host.modelValue.value ?? ''));
      }

      continue;
    }

    const value = host.serializedValue ?? host.value;

    if (value === undefined || value === null) {
      continue;
    }

    if (Array.isArray(value)) {
      for (const item of value) {
        params.append(name, String(item));
      }
    } else {
      params.append(name, String(value));
    }
  }

  return params.toString();
}
