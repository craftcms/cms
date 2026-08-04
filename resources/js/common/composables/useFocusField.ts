import {useTemplateRef, watch} from 'vue';
import {LitElement} from 'lit';

export const useFocusField = (refId: string) => {
  const field = useTemplateRef<LitElement | HTMLElement>(refId);
  watch(field, async (newValue) => {
    if (newValue?.tagName.includes('CRAFT-')) {
      await customElements.whenDefined(newValue.tagName.toLowerCase());

      // Wait for LitElement to complete its first render
      await (newValue as LitElement)?.updateComplete;
    }

    // Now it's safe to focus or interact
    newValue?.focus();
  });
};
