import type {LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import type {StyleInfo} from 'lit/directives/style-map.js';

// oxlint-disable-next-line @typescript-eslint/no-explicit-any
type Constructor<T = object> = new (...args: any[]) => T;

/**
 * Named steps a padding value maps onto the `--c-spacing-*` scale.
 *
 * Deliberately narrower than the token scale, which also carries `xs` and
 * `2xl`: this is the vocabulary `craft-pane` shipped with, and widening it
 * would silently change what an existing `padding="xs"` resolves to. When the
 * design system is ready for the wider scale, widen it here, once.
 */
export const SPACING_STEPS = ['sm', 'md', 'lg', 'xl'] as const;

export type SpacingStep = (typeof SPACING_STEPS)[number];

/** Everything the `padding` property accepts. */
export type PaddingValue = SpacingStep | 'none' | string | number;

/** Whether a value is a plain number (or a numeric string like `"12"`). */
function isNumeric(value: PaddingValue): boolean {
  return !isNaN(parseFloat(String(value))) && isFinite(Number(value));
}

/**
 * Resolves a padding value to a CSS length.
 *
 * - `0`, `"0"`, and `"none"` collapse to `0`. `none` earns its place because
 *   it's what markup reaches for to mean "no padding here" — and because a
 *   bare `none` is not a valid length, so passing it through would drop the
 *   declaration and silently leave the default padding in place.
 * - `sm`/`md`/`lg`/`xl` map onto `--c-spacing-*`.
 * - A unitless number (or numeric string) is read as pixels.
 * - Anything else is passed through verbatim, so a consumer can hand over a
 *   token, a `calc()`, or any other CSS length.
 * - Nothing at all (`undefined`, `null`, or an empty attribute) resolves to
 *   `undefined`, which callers should treat as "write nothing and let the
 *   stylesheet's own fallback stand".
 */
export function resolvePadding(
  value: PaddingValue | null | undefined
): string | undefined {
  if (value === null || value === undefined || value === '') {
    return undefined;
  }

  if (value === 0 || value === '0' || value === 'none') {
    return '0';
  }

  if (isNumeric(value)) {
    return `calc(${value}rem / 16)`;
  }

  if (SPACING_STEPS.includes(value as SpacingStep)) {
    return `var(--c-spacing-${value})`;
  }

  return String(value);
}

/** How a host wires the shared `padding` behavior onto its own CSS. */
export interface PaddableOptions {
  /**
   * The custom property (or properties) the resolved value is written to. The
   * mixin never names one itself — the private var and its fallback chain
   * belong to the component's own stylesheet, so a component with one padded
   * box names one, and a component that pads two axes separately names both.
   */
  customProperty: string | string[];

  /**
   * The value of `padding` when the attribute is absent.
   *
   * Leave it out to keep the property unset, in which case
   * {@link PaddableHost.paddingStyles} stays empty and whatever the
   * stylesheet declares for the custom properties wins. That's the option to
   * take when the component's default padding isn't expressible as a single
   * value — the styles keep their shipped defaults untouched, and `padding`
   * only ever overrides them.
   */
  defaultValue?: PaddingValue;
}

/** The padding surface the mixin adds to a host element. */
export interface PaddableHost {
  /** Spacing for the host's padded region(s). */
  padding: PaddingValue | undefined;

  /** {@link padding} resolved to a CSS length, or `undefined` when unset. */
  readonly resolvedPadding: string | undefined;

  /**
   * The resolved value keyed by the configured custom properties, ready to
   * hand to `styleMap()`. Empty when `padding` is unset.
   */
  readonly paddingStyles: StyleInfo;
}

/**
 * Adds a declarative `padding` attribute to any Lit element: the consumer
 * writes `padding="md"` (or `0`, `none`, `24`, `2rem`, …) and the mixin
 * resolves it to a CSS length and hands it back keyed by the custom
 * properties the component nominated.
 *
 * The mixin deliberately carries only the behavior — the reactive property
 * and the value resolution. The custom property name, its fallback chain, and
 * every rule that consumes it stay in the component's own stylesheet, so
 * adopting this never dictates how a component is padded, only how a padding
 * value is spelled.
 *
 * ```ts
 * class CraftThing extends Paddable(LitElement, {
 *   customProperty: '--_thing-spacing',
 *   defaultValue: 'lg',
 * }) {
 *   render() {
 *     return html`<div style=${styleMap(this.paddingStyles)}>…</div>`;
 *   }
 * }
 * ```
 *
 * The styles pair with it by declaring the same custom property with a public
 * override and a default behind it, so the component still renders sensibly
 * if the property is never written:
 *
 * ```css
 * --_thing-spacing: var(--c-thing-padding, var(--c-spacing-lg));
 * ```
 */
export const Paddable = <T extends Constructor<LitElement>>(
  Base: T,
  {customProperty, defaultValue}: PaddableOptions
) => {
  const customProperties = Array.isArray(customProperty)
    ? customProperty
    : [customProperty];

  class PaddableElement extends Base {
    @property() padding: PaddingValue | undefined = defaultValue;

    get resolvedPadding(): string | undefined {
      return resolvePadding(this.padding);
    }

    get paddingStyles(): StyleInfo {
      const value = this.resolvedPadding;

      if (value === undefined) {
        // An empty StyleInfo leaves the custom properties untouched (and
        // `styleMap` clears any it wrote on a previous render), so the
        // stylesheet's fallback is what applies.
        return {};
      }

      return Object.fromEntries(
        customProperties.map((name) => [name, value])
      ) as StyleInfo;
    }
  }

  // Cast through named types only (`PaddableHost` + `T`). Returning the
  // anonymous `PaddableElement` class directly would leak Lit's protected
  // members into consumers' declaration emit (TS4094).
  return PaddableElement as unknown as Constructor<PaddableHost> & T;
};
