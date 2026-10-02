import type {ComplexAttributeConverter} from 'lit';

/**
 * A boolean attribute that is on unless it is explicitly turned off.
 *
 * Lit's built-in boolean converter is presence-based: the attribute means
 * true when present and false when absent, so an option that should default
 * to on can never be switched off from markup. This converter inverts that —
 * only the literal string `"false"` is off, and every other value (including
 * an empty attribute) is on.
 *
 * Reach for it when the server renders the attribute as a config flag rather
 * than an HTML boolean, as `show-date="false"` does. A property that is off by
 * default wants Lit's `{type: Boolean}` instead, which keeps the ordinary HTML
 * semantics consumers expect.
 *
 * It serialises back as `"true"`/`"false"` rather than as a bare attribute, so
 * a reflected value survives a round trip through the DOM.
 */
export const defaultTrueBoolean: ComplexAttributeConverter<boolean> = {
  fromAttribute: (value: string | null): boolean => value !== 'false',
  toAttribute: (value: boolean): string => String(value),
};

/**
 * A JSON-valued attribute that falls back rather than throwing.
 *
 * An absent attribute arrives as `null` and an empty one as `''`. Neither is
 * valid JSON, so both have to be caught before they reach `JSON.parse` —
 * `disabled-time-ranges=""` would otherwise throw during the element's first
 * update, before it ever renders.
 */
export const jsonAttribute = <T>(
  fallback: () => T
): ComplexAttributeConverter<T> => ({
  fromAttribute: (value: string | null): T =>
    value ? (JSON.parse(value) as T) : fallback(),
  toAttribute: (value: T): string => JSON.stringify(value),
});
