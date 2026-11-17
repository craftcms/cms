import {
  type LayoutSlotRegistry,
  useLayoutSlotRegistry,
} from '@/common/composables/layoutSlots';
import {
  DETAILS_PANEL_ID,
  type ScreenPanels,
  useScreenPanels,
} from '@/common/composables/screenPanels';

/**
 * The footer slots a shell forwards to `ContentFooter`, only when the page
 * filled them so the footer's own fallbacks still apply.
 */
const FOOTER_SLOTS = [
  'content-footer',
  'footer-meta',
  'additional-buttons',
  'primary-action',
] as const;

/**
 * Whether a shell region is in play: filled by an inline slot, or by a
 * page-side `<LayoutSlot>` teleport.
 *
 * Shells may only toggle visibility (`v-show`) and classes from this —
 * removing an outlet's wrapper would break the teleport. Call `has` and the
 * helpers built on it from the template, not a computed: slot presence isn't
 * reactive. The panel checks are, so a computed may use `hasPanels`.
 *
 * A shell passes the layout slot registry and the panels it just provided,
 * since `inject` can't see a component's own `provide`. Components inside a
 * shell can let them default.
 */
export function useScreenRegions(
  slots: object,
  registry: LayoutSlotRegistry = useLayoutSlotRegistry(),
  panels: ScreenPanels | null = useScreenPanels()
) {
  const filledSlots = slots as Record<string, unknown>;

  function has(name: string): boolean {
    return Boolean(filledSlots[name]) || registry.has(name);
  }

  function hasPanels(): boolean {
    return (panels?.panels.value.length ?? 0) > 0;
  }

  return {
    has,
    footerSlots: () => FOOTER_SLOTS.filter((name) => filledSlots[name]),
    hasPanels,
    /**
     * The details column: the panels pages and the shell add with
     * `ScreenPanel`, or whatever a page puts in `content-details`.
     */
    hasDetails: () => has('content-details') || hasPanels(),
    /**
     * Whether the page's `content-details` becomes a panel of its own, which
     * it does once there are other panels to sit beside.
     */
    detailsContentIsPanel: () =>
      has('content-details') &&
      (panels?.panels.value.some((panel) => panel.id !== DETAILS_PANEL_ID) ??
        false),
  };
}
