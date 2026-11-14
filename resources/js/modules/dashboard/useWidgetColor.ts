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
