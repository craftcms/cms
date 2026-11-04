import {nextTick, ref, type Ref} from 'vue';

/**
 * The pane shown when the "Customize sources" modal is too narrow for all three
 * side by side: pages lead to their sources, and a source to its settings.
 */
export type SourceScreen = 'pages' | 'sources' | 'settings';

export const FOCUSABLE =
  'button, [href], input, select, textarea, craft-button, [tabindex]:not([tabindex="-1"])';

/** Focuses `el`, or the control inside it when it's a component host. */
export function focusElement(el: Element | null | undefined): void {
  const target =
    el?.shadowRoot?.querySelector<HTMLElement>(FOCUSABLE) ??
    (el as HTMLElement | null);
  target?.focus();
}

/**
 * Which of the modal's panes is shown, and finding and focusing rows in them.
 * `body` holds the panes, each marked with its `data-screen`.
 */
export function useSourceScreens(body: Readonly<Ref<HTMLElement | null>>) {
  const screen = ref<SourceScreen>('sources');

  function rowElement(
    screen: SourceScreen,
    id: string | null
  ): HTMLElement | null {
    return id === null
      ? null
      : (body.value?.querySelector<HTMLElement>(
          `[data-screen="${screen}"] [data-row-id="${CSS.escape(id)}"]`
        ) ?? null);
  }

  /** Focuses a row's select button, or the list's first when it isn't there. */
  function focusRow(screen: SourceScreen, id: string | null): void {
    const row =
      rowElement(screen, id) ??
      body.value?.querySelector(`[data-screen="${screen}"] [data-row-id]`);

    focusElement(row?.querySelector('craft-action-item'));
  }

  /**
   * Shows `next`, and — when that hid the pane focus was in — moves focus into
   * it. Side by side, nothing is hidden, so focus stays put.
   */
  async function showScreen(next: SourceScreen): Promise<void> {
    const previous = body.value?.querySelector<HTMLElement>(
      `[data-screen="${screen.value}"]`
    );
    screen.value = next;
    await nextTick();

    if (previous && !previous.checkVisibility()) {
      body.value
        ?.querySelector<HTMLElement>(`[data-screen="${next}"]`)
        ?.querySelector<HTMLElement>(
          'button, [href], input, select, textarea, craft-button, craft-action-item, [tabindex]:not([tabindex="-1"])'
        )
        ?.focus();
    }
  }

  return {screen, rowElement, focusRow, showScreen};
}
