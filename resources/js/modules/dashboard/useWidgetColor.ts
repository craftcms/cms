import {
  computed,
  inject,
  provide,
  type ComputedRef,
  type InjectionKey,
} from 'vue';

const widgetColor: InjectionKey<ComputedRef<string | undefined>> = Symbol(
  'dashboard-widget-color'
);

/**
 * Makes a widget's color available to the component that renders it, which
 * may not take the widget itself as a prop.
 */
export function provideWidgetColor(color: () => string | null) {
  provide(
    widgetColor,
    computed(() => color() ?? undefined)
  );
}

/**
 * The color of the widget being rendered, for its card's `data-color`:
 * `undefined` when it has none, so the attribute is left off and the card is
 * uncolored. Outside a dashboard widget it's the widgets' default, gray.
 */
export function useWidgetColor(): ComputedRef<string | undefined> {
  return inject(
    widgetColor,
    computed(() => 'gray')
  );
}

/** Colors that leave a widget untinted, like having no color at all. */
const NEUTRAL_COLORS = ['white', 'gray', 'slate', 'black'];

/** Whether a widget's color tints it, rather than leaving it neutral. */
export function isTintedColor(color: string | null | undefined): boolean {
  return !!color && !NEUTRAL_COLORS.includes(color);
}
