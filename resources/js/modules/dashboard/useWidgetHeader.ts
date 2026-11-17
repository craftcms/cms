import {
  inject,
  onBeforeUnmount,
  provide,
  shallowRef,
  watchEffect,
  type InjectionKey,
  type ShallowRef,
} from 'vue';

/** What a widget's header shares with the component rendering the widget. */
export interface WidgetHeaderContext {
  /** Where `WidgetHeaderActions` renders its buttons, once it's mounted. */
  actionsTarget: ShallowRef<HTMLElement | null>;
  /** How many components show their own title (see `useWidgetHeader()`). */
  ownTitles: ShallowRef<number>;
  /**
   * The ID of the widget's title heading, which its settings button is
   * described by.
   */
  titleId: string;
}

const widgetHeader: InjectionKey<WidgetHeaderContext> = Symbol(
  'dashboard-widget-header'
);

/**
 * Provides the header context to the component rendering a widget. Called by
 * the widget's frame, which draws the header.
 */
export function provideWidgetHeader(titleId: string): WidgetHeaderContext {
  const context: WidgetHeaderContext = {
    actionsTarget: shallowRef(null),
    ownTitles: shallowRef(0),
    titleId,
  };
  provide(widgetHeader, context);

  return context;
}

export interface UseWidgetHeaderOptions {
  /**
   * Leaves the widget's title out, for a widget that shows a title of its
   * own, such as `SparklineStat`'s figure. Give that title the `titleId`
   * this returns, as the widget's heading. The header's buttons sit in the
   * card's top corner, with no header band, centered on a title line as tall
   * as other widgets' titles. Give the card `--widget-title-line-size` for a
   * title of another size.
   */
  ownTitle?: boolean | (() => boolean);
}

/**
 * Lets a dashboard widget's component adjust the header the dashboard draws
 * above it. Outside a dashboard widget, it does nothing.
 */
export function useWidgetHeader(options: UseWidgetHeaderOptions = {}): {
  actionsTarget: ShallowRef<HTMLElement | null>;
  /** The ID for a title of the widget's own, as its heading. */
  titleId: string | undefined;
} {
  const context = inject(widgetHeader, null);
  const actionsTarget = context?.actionsTarget ?? shallowRef(null);

  if (context && options.ownTitle !== undefined) {
    const own = options.ownTitle;
    let owning = false;
    const setOwning = (value: boolean) => {
      if (value !== owning) {
        context.ownTitles.value += value ? 1 : -1;
        owning = value;
      }
    };

    watchEffect(() => setOwning(typeof own === 'function' ? own() : own));
    onBeforeUnmount(() => setOwning(false));
  }

  return {actionsTarget, titleId: context?.titleId};
}
