import {onMounted, type Ref} from 'vue';
import {FieldLayoutDesigner} from '@/modules/field-layout-designer';

/**
 * Boots the legacy `Craft.FieldLayoutDesigner` inside an Inertia page.
 *
 * The designer keeps its own hidden `fieldLayout` input in sync, so `serialize()`
 * reads the value back out at submit — the same thing a Garnish form would post.
 */
export function useFieldLayoutDesigner(hostRef: Ref<HTMLElement | undefined>) {
  onMounted(async () => {
    const host = hostRef.value;
    if (!host) {
      return;
    }

    const designerEl = host.querySelector<HTMLElement>('.layoutdesigner');
    if (designerEl) {
      const settings = JSON.parse(designerEl.dataset.settings ?? '{}');
      new FieldLayoutDesigner(designerEl, settings);
    }

    window.Craft?.initUiElements?.(host);
  });

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

  return {serialize};
}
