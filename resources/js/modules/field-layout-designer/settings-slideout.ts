export interface OpenLayoutSettingsOptions {
  title: string;
  triggerElement?: HTMLElement | null;
  /** Identifies the component being edited, for the refresh request. */
  requestData: () => Record<string, unknown>;
  /** Persists the settings. Rejects with the axios error on a failure. */
  apply: (values: Record<string, unknown>) => Promise<void>;
}

/**
 * Whether the Vue slideout stack is available on this page.
 *
 * `SlideoutHost` is only mounted by the Inertia CP shell, and the designer is
 * also reachable from legacy-stack screens via the `fieldLayoutDesigner()`
 * Twig function. The globals are registered when that shell boots, so their
 * presence is the documented signal for "this is an Inertia page".
 */
export function canUseVueSlideout(): boolean {
  return typeof (window as any).Craft?.openSlideout === 'function';
}

/**
 * Opens a layout component's settings in a Vue slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: the form is
 * built by POSTing the layout currently being edited, which is unsaved client
 * state with no URL to fetch. The panel owns its own Save and Cancel, so the
 * caller doesn't wire a footer or a submit handler.
 *
 * The Vue side is imported on demand. The designer is loaded by the legacy
 * bundle too, and a static import would drag the whole Inertia shell into
 * every page that renders a field layout.
 *
 * Returns false when the panel was not opened — the user declined to discard
 * unsaved changes in a panel this one would have replaced.
 */
export async function openLayoutComponentSettings(
  data: any,
  options: OpenLayoutSettingsOptions
): Promise<boolean> {
  const [{openSlideoutWith}, {default: LayoutComponentSettings}] =
    await Promise.all([
      import('@/common/slideouts'),
      import('./LayoutComponentSettings.vue'),
    ]);

  const panel = openSlideoutWith(
    LayoutComponentSettings as any,
    {
      payload: data.form,
      title: options.title,
      requestData: options.requestData,
      apply: options.apply,
    },
    {opener: options.triggerElement ?? null}
  );

  if (!panel) {
    return false;
  }

  // Server-rendered controls in the form (condition builders, field selects)
  // register their own assets.
  const craft = Craft as typeof Craft & {
    appendHeadHtml(html: string): Promise<void>;
    appendBodyHtml(html: string): Promise<void>;
  };

  if (data.headHtml) {
    await craft.appendHeadHtml(data.headHtml);
  }
  if (data.bodyHtml) {
    await craft.appendBodyHtml(data.bodyHtml);
  }

  return true;
}
