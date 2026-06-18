import {onMounted, type Ref} from 'vue';

export interface FieldLayoutDesignerData {
  /** Server-rendered field layout designer markup (rendered with `autoBoot: false`). */
  html: string;
}

/**
 * Boots the legacy `Craft.FieldLayoutDesigner` inside an Inertia page.
 *
 * The designer keeps its own hidden `fieldLayout` input in sync, so `serialize()`
 * reads the value back out at submit — the same thing a Garnish form would post.
 */
export function useFieldLayoutDesigner(
  hostRef: Ref<HTMLElement | undefined>,
  data: FieldLayoutDesignerData
) {
  onMounted(async () => {
    const host = hostRef.value;
    if (!host) {
      return;
    }

    host.innerHTML = data.html;

    const designerEl = host.querySelector<HTMLElement>('.layoutdesigner');
    if (designerEl) {
      const settings = JSON.parse(designerEl.dataset.settings ?? '{}');
      new window.Craft.FieldLayoutDesigner(designerEl, settings);
    }

    window.Craft?.initUiElements?.(host);
  });

  /**
   * Serializes the designer's inputs into a nested object (fieldLayout, …),
   * exactly like a Garnish form's jQuery serialization.
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
