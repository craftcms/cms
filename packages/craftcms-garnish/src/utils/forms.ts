/**
 * Form / POST-data utilities (jQuery-free).
 */

type InputLike = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * The beginning of an input's `name` with any `[brackets]` stripped.
 */
export function getInputBasename(elem: Element): string | null {
  const name = elem.getAttribute('name');
  if (name) {
    return name.replace(/\[.*/, '');
  }
  return null;
}

/**
 * An input's value as it would be POSTed.
 *
 * - Unchecked checkbox/radio → `null`.
 * - A multi-value input whose name doesn't end in `[]` → only the last value.
 * - A `<select>` whose value is null → `''` (compat wart: not `null`).
 */
export function getInputPostVal(input: Element): string | string[] | null {
  const el = input as InputLike;
  const type = el.getAttribute('type');

  // Unchecked checkbox/radio?
  if (type === 'checkbox' || type === 'radio') {
    if ((el as HTMLInputElement).checked) {
      return (el as HTMLInputElement).value;
    }
    return null;
  }

  // Read the value (multi-selects yield an array).
  let val: string | string[] | null;
  if (el.nodeName === 'SELECT' && (el as HTMLSelectElement).multiple) {
    val = Array.from((el as HTMLSelectElement).selectedOptions).map(
      (opt) => opt.value
    );
  } else {
    val = el.value ?? null;
  }

  // Flatten array values whose input name doesn't end in "[]" (e.g. multi-select).
  const name = el.getAttribute('name') ?? '';
  if (Array.isArray(val) && name.slice(-2) !== '[]') {
    if (val.length) {
      return val[val.length - 1]!;
    }
    return null;
  }

  // A dropdown with a null value → empty string (consistent with element.value).
  if (val === null && el.nodeName === 'SELECT') {
    return '';
  }

  return val;
}

/**
 * Inputs within a container.
 *
 * NOTE: the selector includes the bogus `text` tag from the legacy code (it
 * matches nothing). Preserved verbatim as a documented compat wart.
 */
export function findInputs(container: Element): HTMLElement[] {
  return Array.from(
    container.querySelectorAll<HTMLElement>('input,text,textarea,select,button')
  );
}

/**
 * Serialize the inputs within a container to a flat POST data map, handling
 * `name[]` array indexing (disabled inputs and `null`-valued inputs are
 * skipped). The jQuery-free equivalent of the legacy `getPostData`.
 *
 * @param container - The element whose inputs to serialize.
 * @returns A `name → value` map ready to POST.
 */
export function getPostData(container: Element): Record<string, string> {
  const postData: Record<string, string> = {};
  const arrayInputCounters: Record<string, number> = {};
  const inputs = findInputs(container);

  for (const input of inputs) {
    if ((input as InputLike).disabled) {
      continue;
    }

    let inputName = input.getAttribute('name');
    if (!inputName) {
      continue;
    }

    let inputVal = getInputPostVal(input);
    if (inputVal === null) {
      continue;
    }

    const isArrayInput = inputName.slice(-2) === '[]';
    let croppedName = '';

    if (isArrayInput) {
      croppedName = inputName.substring(0, inputName.length - 2);
      if (arrayInputCounters[croppedName] === undefined) {
        arrayInputCounters[croppedName] = 0;
      }
    }

    const values = Array.isArray(inputVal) ? inputVal : [inputVal];

    for (const value of values) {
      if (isArrayInput) {
        inputName = `${croppedName}[${arrayInputCounters[croppedName]}]`;
        arrayInputCounters[croppedName]!++;
      }
      postData[inputName!] = value;
    }
  }

  return postData;
}

/** Copies values input-by-input, skipping file inputs. */
export function copyInputValues(source: Element, target: Element): void {
  const sourceInputs = findInputs(source);
  const targetInputs = findInputs(target);

  for (let i = 0; i < sourceInputs.length; i++) {
    const targetInput = targetInputs[i] as InputLike | undefined;
    if (targetInput === undefined) {
      break;
    }

    if (targetInput.getAttribute('type') !== 'file') {
      targetInput.value = (sourceInputs[i] as InputLike).value;
    }
  }
}
