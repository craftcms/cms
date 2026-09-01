/**
 * The normalized tag-attributes shape produced by Craft's server-side HTML
 * helpers (e.g. `Html::normalizeTagAttributes`). Top-level keys map to:
 *
 * - `class`: an array of class names — or, when PHP's `array_filter` leaves
 *   gaps, an object keyed by the original numeric indexes — or a plain string.
 * - `style`: a `{property: value}` map (or an empty array when there are no
 *   declarations).
 * - nested groups such as `data` / `aria`: a `{name: value}` map whose keys are
 *   already hyphenated and whose values may themselves be arrays/objects (which
 *   the server JSON-encodes).
 * - everything else: a scalar (`string` | `number` | `boolean`), or
 *   `null` / `false` for an omitted attribute.
 */
export type ServerAttributes = Record<string, unknown>;

/**
 * A plain attributes object ready to spread onto a component via Vue's
 * `v-bind`. `class` and `style` are returned in the array/object forms Vue
 * merges natively; nested groups are flattened to hyphenated keys
 * (`data-foo`, `aria-bar`).
 */
export type BindableAttributes = Record<string, unknown>;

/**
 * Options for {@link attrs}.
 */
export interface AttrsOptions {
  /**
   * Top-level server attribute keys to omit from the output, matched *before*
   * the `data` / `aria` nested flattening. So names are the raw server keys
   * (`'class'`, `'style'`, `'id'`, …), and excluding a group key drops the
   * entire flattened group — e.g. `exclude: ['data']` drops every `data-*`
   * attribute. Names that aren't present are simply ignored.
   */
  exclude?: string[];
}

/**
 * Converts a Yii/Craft-style server tag-attributes object into a flat object
 * suitable for binding onto one of our components with Vue's `v-bind`.
 *
 * Vue's `v-bind` merges `class` and `style` natively, but it will not expand a
 * nested `data` / `aria` object into `data-*` / `aria-*` attributes — so those
 * groups are flattened here, with any array/object values JSON-stringified to
 * match the server's encoding. `null` / `false` values are dropped, mirroring
 * the server, which omits them.
 *
 * Pass `{exclude}` to omit specific attributes. Exclusion matches the
 * *top-level* server key before nested groups are flattened, so `'class'`,
 * `'style'`, `'id'`, and group names like `'data'` / `'aria'` are all valid —
 * excluding a group key drops the whole flattened group (e.g.
 * `exclude: ['data']` removes every resulting `data-*` attribute). You cannot
 * target an individual flattened key such as `'data-id'`. Excluding a key that
 * isn't present is a no-op.
 *
 * @param attributes - Server-rendered, normalized tag attributes (e.g. the
 *   `cardAttributes` returned from a controller). May be `undefined`.
 * @param options - Optional settings; see {@link AttrsOptions}. Omitting this
 *   argument behaves exactly as binding every attribute.
 * @returns A flat, `v-bind`-ready attributes object.
 *
 * @example
 * ```html
 * <!-- bind every server attribute -->
 * <craft-card v-bind="attrs(element.cardAttributes)" />
 *
 * <!-- bind everything except the wrapper's own classes -->
 * <craft-card v-bind="attrs(element.cardAttributes, {exclude: ['class']})" />
 * ```
 */
export function attrs(
  attributes?: ServerAttributes,
  options: AttrsOptions = {}
): BindableAttributes {
  const result: BindableAttributes = {};

  if (!attributes) {
    return result;
  }

  const excluded = new Set(options.exclude ?? []);

  for (const [name, value] of Object.entries(attributes)) {
    // Exclusion matches the top-level server key, so dropping a group name
    // (e.g. 'data') skips the whole group before it would be flattened.
    if (excluded.has(name)) {
      continue;
    }

    // The server omits null/false attributes; mirror that here.
    if (value === null || value === false) {
      continue;
    }

    if (name === 'class') {
      // `array_filter` server-side can leave gaps, so a class list may arrive
      // as an object keyed by index; normalize that back to a flat array. Plain
      // arrays and strings are valid `v-bind:class` inputs as-is.
      result.class =
        Array.isArray(value) || typeof value !== 'object'
          ? value
          : Object.values(value as Record<string, unknown>);
    } else if (name === 'style') {
      // Already a `{property: value}` map (or an empty array when there are no
      // declarations); both are valid `v-bind:style` inputs.
      result.style = value;
    } else if (typeof value === 'object' && !Array.isArray(value)) {
      // Nested attribute group, e.g. `data` -> `data-*`, `aria` -> `aria-*`.
      // The server hyphenates the keys already, so just prefix with the group.
      for (const [sub, subValue] of Object.entries(
        value as Record<string, unknown>
      )) {
        if (subValue === null || subValue === false) {
          continue;
        }
        result[`${name}-${sub}`] =
          typeof subValue === 'object' && subValue !== null
            ? JSON.stringify(subValue)
            : subValue;
      }
    } else {
      // Plain scalar (id, etc.) — bind it directly.
      result[name] = value;
    }
  }

  return result;
}
