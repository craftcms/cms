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

export function isVisible(el: HTMLElement): boolean {
  if (typeof el.checkVisibility === 'function') {
    return el.checkVisibility({checkOpacity: true, checkVisibilityCSS: true});
  }

  // Fallback: mirrors jQuery's :visible behavior
  return el.offsetWidth > 0 || el.offsetHeight > 0;
}

/**
 * Serializes every named form control inside a container into a URL-encoded
 * string, mirroring jQuery's `.serialize()` semantics (unchecked checkboxes
 * and radios, disabled controls, and buttons/files are omitted).
 *
 * Unlike `FormData`, this works on any element — not just `<form>` — so it can
 * scope serialization to a fragment of a page-level form, such as a legacy
 * HTML island whose inputs aren't part of an Inertia form's state. Values are
 * read from live DOM properties, so user edits are always captured (cloning
 * into a detached form would only see attributes).
 */
export function serializeFormInputs(container: HTMLElement): string {
  return collectFormInputs(container).toString();
}

/**
 * Like [[serializeFormInputs()]], but returns a plain object instead of a
 * URL-encoded string.
 *
 * Names are kept verbatim — PHP-style bracket names (`settings[path]`) stay
 * flat keys, matching what the server would see after parsing the string
 * form. Repeated names are grouped into arrays rather than last-one-wins.
 */
export function serializeFormInputsAsObject(
  container: HTMLElement
): Record<string, string | string[]> {
  const object: Record<string, string | string[]> = {};

  for (const [name, value] of collectFormInputs(container)) {
    const existing = object[name];

    if (existing === undefined) {
      object[name] = value;
    } else if (Array.isArray(existing)) {
      existing.push(value);
    } else {
      object[name] = [existing, value];
    }
  }

  return object;
}

function collectFormInputs(container: HTMLElement): URLSearchParams {
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

  return params;
}
