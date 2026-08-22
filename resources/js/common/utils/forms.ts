export type PostValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | File
  | PostValues
  | PostValue[];

export interface PostValues {
  [key: string]: PostValue;
}

type PostContainer = PostValues | PostValue[];

function isPostValues(value: PostValue): value is PostValues {
  return (
    value instanceof Object && !Array.isArray(value) && !(value instanceof File)
  );
}

function getValue(container: PostContainer, key: string): PostValue {
  return Array.isArray(container) ? container[Number(key)] : container[key];
}

function setValue(
  container: PostContainer,
  key: string,
  value: PostValue
): void {
  if (Array.isArray(container)) {
    container[Number(key)] = value;
  } else {
    container[key] = value;
  }
}

function isContainer(value: PostValue): value is PostContainer {
  return Array.isArray(value) || isPostValues(value);
}

/**
 * Expands a single POST array-style key into the target object, creating
 * nested arrays and objects as needed.
 */
function expandPostKey(
  expanded: PostValues,
  postKey: string,
  value: PostValue
): void {
  const match = postKey.match(/^(\w+)(\[.*)?/);
  const rootKey = match?.[1];

  if (!rootKey) {
    return;
  }

  const keys = match[2]
    ? (match[2].match(/\[[^[\]]*\]/g) ?? []).map((key) => key.slice(1, -1))
    : [];
  keys.unshift(rootKey);

  let parent: PostContainer = expanded;

  keys.forEach((rawKey, index) => {
    const key =
      rawKey || (Array.isArray(parent) ? String(parent.length) : rawKey);

    if (index === keys.length - 1) {
      setValue(parent, key, value);
      return;
    }

    const nextKey = keys[index + 1];
    let child = getValue(parent, key);
    if (!isContainer(child)) {
      child =
        !nextKey || Number.parseInt(nextKey) === Number(nextKey) ? [] : {};
      setValue(parent, key, child);
    }

    parent = child;
  });
}

/** Expands an object of POST array-style strings into a nested object. */
export function expandPostArray(values: PostValues): PostValues {
  const expanded: PostValues = {};

  Object.entries(values).forEach(([postKey, value]) => {
    expandPostKey(expanded, postKey, value);
  });

  return expanded;
}

/**
 * Expands FormData with POST array-style keys into a nested object. Repeated
 * keys are collected into arrays, and files pass through unchanged.
 */
export function expandFormData(data: FormData): PostValues {
  const expanded: PostValues = {};

  for (const [postKey, value] of data.entries()) {
    expandPostKey(expanded, postKey, value);
  }

  return expanded;
}
