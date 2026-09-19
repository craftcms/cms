import {
  type LayoutSlotRegistry,
  useLayoutSlotRegistry,
} from '@/common/composables/layoutSlots';

/**
 * Whether a shell region is in play: filled by an inline slot, or by a
 * page-side `<LayoutSlot>` teleport.
 *
 * Shells may only toggle visibility (`v-show`) and classes from this —
 * removing an outlet's wrapper would break the teleport.
 *
 * A shell passes the registry it just provided, since `inject` can't see a
 * component's own `provide`. Components inside a shell can let it default.
 */
export function useScreenRegions(
  slots: object,
  registry: LayoutSlotRegistry = useLayoutSlotRegistry()
) {
  return {
    has: (name: string): boolean =>
      Boolean((slots as Record<string, unknown>)[name]) || registry.has(name),
  };
}
