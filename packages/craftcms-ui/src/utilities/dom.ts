type OwnedAsset = {
  node: HTMLLinkElement | HTMLScriptElement;
  loaded: Promise<void> | null;
  references: number;
};

type AssetDetails = {
  key: string;
  selector: 'link[href]' | 'script[src]';
  value: string;
};

const ownedAssets = new Map<string, OwnedAsset>();

/**
 * Disposer returned from {@link appendHeadHtml} / {@link appendBodyHtml}.
 * Calling it removes any nodes that were appended and rolls back the
 * dedup cache entries for stylesheets and scripts that were added.
 */
export type AppendHtmlDisposer = () => void;

export async function appendElementHtml(
  html: string,
  parent: HTMLElement,
  rejectOnError = false,
): Promise<AppendHtmlDisposer> {
  const appended: Node[] = [];
  const releases: Array<() => void> = [];

  const dispose: AppendHtmlDisposer = () => {
    for (const node of appended) {
      node.remove();
    }
    releases.forEach((release) => release());
  };

  if (!html) {
    return dispose;
  }

  const template = document.createElement('template');
  template.innerHTML = html.trim();

  try {
    for (const source of template.content.childNodes) {
      const asset = assetDetails(source);

      if (asset) {
        const ownedAsset = ownedAssets.get(asset.key);

        if (ownedAsset) {
          ownedAsset.references++;
          releases.push(() => releaseAsset(asset.key, ownedAsset));
          if (ownedAsset.loaded) {
            await awaitAsset(ownedAsset.loaded, rejectOnError);
          }

          continue;
        }

        if (hasAsset(asset)) {
          continue;
        }
      }

      const node = cloneNode(source);
      const loaded = asset && node instanceof HTMLScriptElement ? waitForScript(node, asset.value) : null;

      parent.appendChild(node);

      if (asset && (node instanceof HTMLLinkElement || node instanceof HTMLScriptElement)) {
        const ownedAsset = { node, loaded, references: 1 };

        ownedAssets.set(asset.key, ownedAsset);
        releases.push(() => releaseAsset(asset.key, ownedAsset));
      } else {
        appended.push(node);
      }

      if (loaded) {
        await awaitAsset(loaded, rejectOnError);
      }
    }

    return dispose;
  } catch (error) {
    dispose();

    throw error;
  }
}

function cloneNode(source: Node): Node {
  if (!(source instanceof HTMLScriptElement)) {
    return source.cloneNode(true);
  }

  const script = document.createElement('script');

  for (const attribute of source.attributes) {
    script.setAttribute(attribute.name, attribute.value);
  }

  script.textContent = source.textContent;
  script.async = false;

  return script;
}

function assetDetails(node: Node): AssetDetails | null {
  if (node instanceof HTMLLinkElement && node.href) {
    return {
      key: `link:${node.href}`,
      selector: 'link[href]',
      value: node.href,
    };
  }

  if (node instanceof HTMLScriptElement && node.src) {
    return {
      key: `script:${node.src}`,
      selector: 'script[src]',
      value: node.src,
    };
  }

  return null;
}

function hasAsset(asset: AssetDetails): boolean {
  return Array.from(document.querySelectorAll(asset.selector)).some(
    (element) =>
      (element instanceof HTMLLinkElement ? element.href : (element as HTMLScriptElement).src) === asset.value,
  );
}

function waitForScript(script: HTMLScriptElement, url: string): Promise<void> {
  return new Promise((resolve, reject) => {
    script.addEventListener('load', () => resolve(), { once: true });
    script.addEventListener('error', () => reject(new Error(`Failed to load asset [${url}].`)), {
      once: true,
    });
  });
}

async function awaitAsset(loaded: Promise<void>, rejectOnError: boolean): Promise<void> {
  try {
    await loaded;
  } catch (error) {
    if (rejectOnError) {
      throw error;
    }
  }
}

function releaseAsset(key: string, asset: OwnedAsset): void {
  asset.references--;

  if (asset.references > 0) {
    return;
  }

  asset.node.remove();
  ownedAssets.delete(key);
}

/**
 * Appends HTML to the page `<head>`.
 *
 * Returns a disposer that removes the appended nodes when called.
 */
export async function appendHeadHtml(html: string): Promise<AppendHtmlDisposer> {
  return appendElementHtml(html, document.head);
}

/**
 * Appends HTML to the page `<body>`.
 *
 * Returns a disposer that removes the appended nodes when called.
 */
export async function appendBodyHtml(html: string): Promise<AppendHtmlDisposer> {
  return appendElementHtml(html, document.body);
}

export function isVisible(el: HTMLElement): boolean {
  if (typeof el.checkVisibility === 'function') {
    return el.checkVisibility({ checkOpacity: true, checkVisibilityCSS: true });
  }

  // Fallback: mirrors jQuery's :visible behavior
  return el.offsetWidth > 0 || el.offsetHeight > 0;
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
function isChoiceModelValue(modelValue: unknown): modelValue is { value: unknown; checked: boolean } {
  return (
    typeof modelValue === 'object' &&
    modelValue !== null &&
    'checked' in modelValue &&
    typeof (modelValue as { checked: unknown }).checked === 'boolean'
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
export function serializeFormInputsAsObject(container: HTMLElement): Record<string, string | string[]> {
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
  const controls = container.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
    'input[name], select[name], textarea[name]',
  );

  for (const control of controls) {
    if (control.disabled) {
      continue;
    }

    if (control instanceof HTMLInputElement) {
      if (['file', 'submit', 'button', 'reset', 'image'].includes(control.type)) {
        continue;
      }

      if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) {
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

  return params;
}
