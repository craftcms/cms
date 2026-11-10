<script setup lang="ts">
  /**
   * Gives an editable side of the Compare screen its own screen props — the
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
  import type {FormSaveOptions} from '@/common/types';

  const props = defineProps<{
    editor: ElementEditPayload & {saveParams: UiValues};
    changed: Record<string, boolean | null>;
    fieldOutlets: UiFieldOutlets;
    root: HTMLElement | null;
    footerTargets: {notices: HTMLElement | null; actions: HTMLElement | null};
    saveShortcutTarget?: () => ((event: KeyboardEvent) => void) | null;
    beforeSave?: () => Promise<boolean>;
    fieldIdScope: string;
    otherSideSaving?: boolean;
  }>();

  const emit = defineEmits<{
    (event: 'edited', edited: boolean): void;
    (event: 'errors', uids: string[]): void;
    (event: 'layout', ui: UiPayload | null): void;
    (event: 'edits', values: Record<string, string>): void;
    (event: 'locked', locked: boolean): void;
    (event: 'values', values: UiValues): void;
    (event: 'saving', saving: boolean): void;
  }>();

  provide(ScreenPagePropsKey, () => props.editor as unknown as ScreenPageProps);

  const form = useTemplateRef<InstanceType<typeof CompareEditorForm>>('form');

  defineExpose({
    copyValue: (uid: string, snapshot: UiValueSnapshot) =>
      form.value?.copyValue(uid, snapshot) ?? null,
    save: (options?: FormSaveOptions) => form.value?.save(options),
    saveForShortcut: (event: KeyboardEvent) =>
      form.value?.saveForShortcut(event),
    settleChanges: async () => (await form.value?.settleChanges()) ?? true,
    /** The editor's Inertia form, once it's rendered. */
    form: () => form.value?.form ?? null,
    nestedOwnerEditor: () => form.value?.nestedOwnerEditor ?? null,
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
    :save-shortcut-target="saveShortcutTarget"
    :before-save="beforeSave"
    :field-id-scope="fieldIdScope"
    :other-side-saving="otherSideSaving"
    @edited="emit('edited', $event)"
    @errors="emit('errors', $event)"
    @layout="emit('layout', $event)"
    @edits="emit('edits', $event)"
    @locked="emit('locked', $event)"
    @values="emit('values', $event)"
    @saving="emit('saving', $event)"
  />
</template>
