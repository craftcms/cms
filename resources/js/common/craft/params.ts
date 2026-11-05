/**
 * Query-string serialization compatible with jQuery's `$.param()`, which the
 * server's bracketed param parsing and saved delta data both depend on.
 */

const nonObjectTags = new Set([
  '[object Boolean]',
  '[object Number]',
  '[object String]',
  '[object Function]',
  '[object Array]',
  '[object Date]',
  '[object RegExp]',
  '[object Error]',
  '[object Symbol]',
]);

/** Whether a value is a plain `{}` object, as jQuery's `isPlainObject()` sees it. */
export function isPlainObject(
  value: unknown
): value is Record<string, unknown> {
  if (Object.prototype.toString.call(value) !== '[object Object]') {
    return false;
  }

  const proto = Object.getPrototypeOf(value);
  if (proto === null) {
    return true;
  }

  const ctor =
    Object.prototype.hasOwnProperty.call(proto, 'constructor') &&
    proto.constructor;

  return (
    typeof ctor === 'function' &&
    Function.prototype.toString.call(ctor) ===
      Function.prototype.toString.call(Object)
  );
}

function isObjectLike(value: unknown): value is Record<string, unknown> {
  return (
    value !== null &&
    typeof value === 'object' &&
    !nonObjectTags.has(Object.prototype.toString.call(value))
  );
}

type Add = (key: string, value: unknown) => void;

function buildParams(
  prefix: string,
  obj: unknown,
  traditional: boolean,
  add: Add
): void {
  if (Array.isArray(obj)) {
    obj.forEach((v, i) => {
      if (traditional || prefix.endsWith('[]')) {
        add(prefix, v);
      } else {
        // Only non-scalar items get their index in the key.
        const index = typeof v === 'object' && v != null ? i : '';
        buildParams(`${prefix}[${index}]`, v, traditional, add);
      }
    });
  } else if (!traditional && isObjectLike(obj)) {
    for (const name in obj) {
      buildParams(`${prefix}[${name}]`, obj[name], traditional, add);
    }
  } else {
    add(prefix, obj);
  }
}

/**
 * Serializes an object into a query string, nesting with brackets
 * (`a[b][]=1`). An array is read as a list of `{name, value}` form fields.
 */
export function param(a: unknown, traditional = false): string {
  const s: string[] = [];
  const add: Add = (key, valueOrFunction) => {
    const value =
      typeof valueOrFunction === 'function'
        ? valueOrFunction()
        : valueOrFunction;
    s.push(
      `${encodeURIComponent(key)}=${encodeURIComponent(value == null ? '' : String(value))}`
    );
  };

  if (a == null) {
    return '';
  }

  if (Array.isArray(a)) {
    for (const field of a) {
      add(field?.name, field?.value);
    }
  } else {
    for (const prefix in a as Record<string, unknown>) {
      buildParams(
        prefix,
        (a as Record<string, unknown>)[prefix],
        traditional,
        add
      );
    }
  }

  return s.join('&');
}
