type PostContainer = Record<string, unknown> | unknown[];

/**
 * Expands a single POST array-style key (e.g. `fields[matrix][0][type]`)
 * into the given target object, creating nested containers as needed.
 */
function expandPostKey(
  expanded: Record<string, unknown>,
  postKey: string,
  value: unknown
): void {
  const m = postKey.match(/^(\w+)(\[.*)?/);

  if (!m?.[1]) {
    return;
  }

  // Get all of the nested keys, chopping off the brackets
  const keys: string[] = m[2]
    ? (m[2].match(/\[[^[\]]*\]/g) ?? []).map((k) => k.slice(1, -1))
    : [];

  keys.unshift(m[1]);

  let parentElem: PostContainer = expanded;

  for (let i = 0; i < keys.length; i++) {
    const key = keys[i] as string;
    const container = parentElem as Record<string, unknown>;

    if (i < keys.length - 1) {
      if (typeof container[key] !== 'object' || container[key] === null) {
        // Figure out what this will be by looking at the next key
        const nextKey = keys[i + 1];
        container[key] =
          !nextKey || Number.parseInt(nextKey) === Number(nextKey) ? [] : {};
      }

      parentElem = container[key] as PostContainer;
    } else {
      // Last one. Set the value
      const lastKey = key || String((parentElem as unknown[]).length);
      container[lastKey] = value;
    }
  }
}

/**
 * Expands an object of POST array-style strings into a nested object.
 */
export function expandPostArray(
  arr: Record<string, unknown>
): Record<string, unknown> {
  const expanded: Record<string, unknown> = {};

  for (const [postKey, value] of Object.entries(arr)) {
    expandPostKey(expanded, postKey, value);
  }

  return expanded;
}

/**
 * Expands a FormData object with POST array-style keys into a nested object.
 *
 * Repeated keys (e.g. multiple `tags[]` entries) are collected into arrays.
 * File values are passed through as-is, so the result is only
 * JSON-serializable if the form contains no file inputs.
 */
export function expandFormData(data: FormData): Record<string, unknown> {
  const expanded: Record<string, unknown> = {};

  for (const [postKey, value] of data.entries()) {
    expandPostKey(expanded, postKey, value);
  }

  return expanded;
}
