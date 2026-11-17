type IfEquals<X, Y, A, B> =
  (<T>() => T extends X ? 1 : 2) extends <T>() => T extends Y ? 1 : 2 ? A : B;

type WritableKeys<T> = {
  [K in keyof T]-?: IfEquals<
    {[Q in K]: T[K]},
    {-readonly [Q in K]: T[K]},
    K,
    never
  >;
}[keyof T];

/** The settable, non-method properties of an element. */
export type ElementProps<E> = Partial<{
  [K in WritableKeys<E> as E[K] extends (...args: never[]) => unknown
    ? never
    : K]: E[K];
}>;

export type CreateProps<E> = ElementProps<E> & {
  /**
   * Attributes with no matching property, such as `data-*`, `aria-*` and
   * `slot`-style markers. `true` sets an empty attribute; `false`, `null` and
   * `undefined` leave it off.
   */
  attrs?: Record<string, string | number | boolean | null | undefined>;
};

/**
 * Creates an element, typed by its tag. Properties are checked against the
 * element's class, so a misspelled or unknown property is a compile error.
 * `undefined` properties are skipped, so optional config can pass straight
 * through.
 *
 * @example
 * create('craft-button', {variant: 'primary', type: 'submit'}, t('Save'));
 * create('input', {type: 'url', name: 'url', attrs: {'aria-label': t('URL')}});
 */
export function create<K extends keyof HTMLElementTagNameMap>(
  tag: K,
  props: CreateProps<HTMLElementTagNameMap[K]> = {},
  ...children: (Node | string)[]
): HTMLElementTagNameMap[K] {
  const {attrs, ...rest} = props;
  const element = document.createElement(tag);

  for (const [name, value] of Object.entries(attrs ?? {})) {
    if (value === true) {
      element.setAttribute(name, '');
    } else if (value !== false && value !== null && value !== undefined) {
      element.setAttribute(name, String(value));
    }
  }

  for (const [name, value] of Object.entries(rest)) {
    // `undefined` would be stringified by setters like `input.value`
    if (value !== undefined) {
      (element as Record<string, unknown>)[name] = value;
    }
  }
  element.append(...children);

  return element;
}
