import {nextTick, onBeforeUnmount, onMounted, type Ref} from 'vue';
import {FieldLayoutDesigner} from '@/modules/field-layout-designer';

/**
 * Boots the (legacy) `Craft.FieldLayoutDesigner` inside an Inertia page and
 * keeps it alive across saves.
 *
 * Inertia re-renders the designer markup and swaps it into `hostRef` via
 * `v-html`; that `innerHTML` replace orphans the imperatively-booted designer
 * (its drag handles / JS go dead). So this owns a {@link reboot} that the page
 * calls when the designer's `html` prop changes — tearing the old instance down
 * (cascading to the card view designer + its sortable checkbox library) before
 * booting a fresh one on the new DOM.
 *
 * The designer keeps its own hidden `fieldLayout` input in sync, so
 * {@link serialize} reads the value back out at submit — the same thing a
 * Garnish form would post.
 */
export function useFieldLayoutDesigner(hostRef: Ref<HTMLElement | undefined>) {
  let designer: FieldLayoutDesigner | null = null;

  /** Boot the designer on the current host DOM. Idempotent (no-op if booted). */
  function boot(): void {
    if (designer) {
      return;
    }

    const host = hostRef.value;
    if (!host) {
      return;
    }

    const designerEl = host.querySelector<HTMLElement>('.layoutdesigner');
    if (designerEl) {
      const settings = JSON.parse(designerEl.dataset.settings ?? '{}');
      designer = new FieldLayoutDesigner(designerEl, settings);
    }

    window.Craft?.initUiElements?.(host);
  }

  /**
   * Tear the designer down. `FieldLayoutDesigner.destroy()` cascades to the card
   * view designer and its `SortableCheckboxSelect` and releases the global
   * footprint (Craft.Grid's window listener, the tabs' HUDs).
   */
  function destroy(): void {
    designer?.destroy();
    designer = null;
  }

  /**
   * Re-boot the designer after the host's `v-html` is swapped. Destroys BEFORE
   * re-booting (never partial — a half teardown leaves duplicate listeners and
   * orphaned Garnish instances), and waits a tick so the new DOM is in place
   * before booting.
   *
   * Ordering: the `<craft-generated-fields-table>` custom element re-boots
   * itself via its own connected/disconnected callbacks when the markup swaps —
   * no involvement here. Its only dependency on the card view designer (the
   * `cvd` getter) is lazy (read on a field-name edit, not at boot), so this
   * same-tick reboot re-populates `cvdData` for the new designer before any
   * interaction; the stale table's teardown reads `cvd` through optional
   * chaining, so it's safe even if the old CVD is already gone.
   */
  async function reboot(): Promise<void> {
    destroy();
    await nextTick();
    boot();
  }

  onMounted(boot);
  onBeforeUnmount(destroy);

  /**
   * The designer's field layout config as the JSON string from its hidden
   * `fieldLayout` input, passed through verbatim.
   *
   * It must stay a string (not parsed): the server reads it via
   * `JsonHelper::decode(Request::input('fieldLayout'))`, which requires a JSON
   * string and throws "Invalid JSON data" on a non-string. The FLD already
   * encodes the config correctly (arrays as arrays), so the raw value is sent
   * as-is. `generatedFields` is sent as a separate top-level value (see
   * useGeneratedFieldsTable), not merged into this object.
   */
  function serialize(): string {
    const host = hostRef.value;
    if (!host) {
      return '{}';
    }

    const configInput = host.querySelector<HTMLInputElement>(
      '[name="fieldLayout"]'
    );
    return configInput?.value || '{}';
  }

  return {serialize, reboot};
}
