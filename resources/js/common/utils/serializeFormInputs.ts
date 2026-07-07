/**
 * Serializes every named form control inside a container into a URL-encoded
 * string, mirroring jQuery's `.serialize()` semantics (unchecked checkboxes and
 * radios, disabled controls, and buttons/files are omitted).
 *
 * Used to post legacy HTML islands — server-rendered inputs that aren't part of
 * an Inertia form's state — back to endpoints that `parse_str()` them.
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

  return params.toString();
}
