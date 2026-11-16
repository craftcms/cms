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
  /** How many components are hiding the title (see `useWidgetHeader()`). */
  titleHiders: ShallowRef<number>;
}

const widgetHeader: InjectionKey<WidgetHeaderContext> = Symbol(
  'dashboard-widget-header'
);

/**
 * Provides the header context to the component rendering a widget. Called by
 * the widget's frame, which draws the header.
 */
export function provideWidgetHeader(): WidgetHeaderContext {
  const context: WidgetHeaderContext = {
    actionsTarget: shallowRef(null),
    titleHiders: shallowRef(0),
  };
  provide(widgetHeader, context);

  return context;
}

export interface UseWidgetHeaderOptions {
  /**
   * Hides the widget's title, for a widget that shows a title of its own,
   * such as a total. The title stays as the widget's heading for screen
   * readers, and the header's buttons sit in the card's top corner, with no
   * header band. Give the card `--widget-title-line-size` to center them on
   * the widget's own title.
   */
  titleHidden?: boolean | (() => boolean);
}

/**
 * Lets a dashboard widget's component adjust the header the dashboard draws
 * above it. Outside a dashboard widget, it does nothing.
 */
export function useWidgetHeader(options: UseWidgetHeaderOptions = {}): {
  actionsTarget: ShallowRef<HTMLElement | null>;
} {
  const context = inject(widgetHeader, null);
  const actionsTarget = context?.actionsTarget ?? shallowRef(null);

  if (context && options.titleHidden !== undefined) {
    const hidden = options.titleHidden;
    let hiding = false;
    const setHiding = (value: boolean) => {
      if (value !== hiding) {
        context.titleHiders.value += value ? 1 : -1;
        hiding = value;
      }
    };

    watchEffect(() =>
      setHiding(typeof hidden === 'function' ? hidden() : hidden)
    );
    onBeforeUnmount(() => setHiding(false));
  }

  return {actionsTarget};
}
