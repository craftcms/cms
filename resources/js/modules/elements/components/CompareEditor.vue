<script setup lang="ts">
  /**
   * Gives the Compare screen's editable side its own screen props — the
   * version's edit screen payload — which the editor reads in place of the
   * page's, as a slideout's editor reads its panel's.
   */
  import {provide, useTemplateRef} from 'vue';
  import {
    ScreenPagePropsKey,
    type ScreenPageProps,
  } from '@/common/composables/screen';
  import CompareEditorForm from '@/modules/elements/components/CompareEditorForm.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import type {UiFieldOutlets} from '@/modules/ui/fieldOutlets';
  import type {UiPayload, UiValues} from '@/modules/ui/types';
  import type {UiValueSnapshot} from '@/modules/ui/valueAccess';

  const props = defineProps<{
    editor: ElementEditPayload & {saveParams: UiValues};
    changed: Record<string, boolean | null>;
    fieldOutlets: UiFieldOutlets;
    root: HTMLElement | null;
    footerTargets: {notices: HTMLElement | null; actions: HTMLElement | null};
  }>();

  const emit = defineEmits<{
    (event: 'edited', edited: boolean): void;
    (event: 'errors', uids: string[]): void;
    (event: 'layout', ui: UiPayload | null): void;
    (event: 'edits', values: Record<string, string>): void;
    (event: 'locked', locked: boolean): void;
  }>();

  provide(ScreenPagePropsKey, () => props.editor as unknown as ScreenPageProps);

  const form = useTemplateRef<InstanceType<typeof CompareEditorForm>>('form');

  defineExpose({
    copyValue: (uid: string, snapshot: UiValueSnapshot) =>
      form.value?.copyValue(uid, snapshot) ?? null,
  });
</script>

<template>
  <CompareEditorForm
    ref="form"
    :editor="editor"
    :changed="changed"
    :field-outlets="fieldOutlets"
    :root="root"
    :footer-targets="footerTargets"
    @edited="emit('edited', $event)"
    @errors="emit('errors', $event)"
    @layout="emit('layout', $event)"
    @edits="emit('edits', $event)"
    @locked="emit('locked', $event)"
  />
</template>
