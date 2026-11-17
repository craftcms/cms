import {randomString, rtrim} from './strings';

/**
 * Input name and ID namespacing for markup built in the browser. The legacy
 * bundle still exposes these on the `Craft` global for plugins.
 */

/** Formats an HTML ID out of an input name. IDs must begin with a letter. */
export function formatInputId(inputName: string): string {
  const id = rtrim(
    inputName.replace(/^[^A-Za-z]+/, '').replace(/[^A-Za-z0-9_.]+/g, '-'),
    '-'
  );

  return id || randomString(10);
}

/** Namespaces an input name: `title` in `fields` becomes `fields[title]`. */
export function namespaceInputName(
  name: string,
  namespace?: string | null
): string {
  if (!namespace) {
    return name;
  }

  return name.replace(/([^'"[\]]+)([^'"]*)/, `${namespace}[$1]$2`);
}

/** Namespaces an input ID: `title` in `fields` becomes `fields-title`. */
export function namespaceId(id: string, namespace?: string | null): string {
  return (namespace ? `${formatInputId(namespace)}-` : '') + formatInputId(id);
}
