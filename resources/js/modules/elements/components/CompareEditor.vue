<script setup lang="ts">
  /**
   * Gives the Compare screen's editable side its own screen props — the
   * version's edit screen payload — which the editor reads in place of the
   * page's, as a slideout's editor reads its panel's.
   */
  import {provide} from 'vue';
  import {
    ScreenPagePropsKey,
    type ScreenPageProps,
  } from '@/common/composables/screen';
  import CompareEditorForm from '@/modules/elements/components/CompareEditorForm.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import type {UiValues} from '@/modules/ui/types';

  const props = defineProps<{
    editor: ElementEditPayload & {saveParams: UiValues};
    uids: string[];
    changed: Record<string, boolean | null>;
  }>();

  const emit = defineEmits<{
    (event: 'edited', edited: boolean): void;
  }>();

  provide(ScreenPagePropsKey, () => props.editor as unknown as ScreenPageProps);
</script>

<template>
  <CompareEditorForm
    :editor="editor"
    :uids="uids"
    :changed="changed"
    @edited="emit('edited', $event)"
  >
    <template #empty><slot name="empty" /></template>
  </CompareEditorForm>
</template>
