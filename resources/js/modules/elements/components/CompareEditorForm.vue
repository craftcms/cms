<script setup lang="ts">
  /**
   * The editable side of the Compare screen: the version's own editor, with
   * its autosave, drafts and Save button, showing just the fields the
   * comparison lists, in its order and within their tabs, with sidebar tabs
   * shown as ordinary ones. `CompareEditor`
   * provides the editor's payload as the screen's props.
   */
  import {t} from '@craftcms/ui';
  import {computed, provide, useTemplateRef, watch} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import {
    useElementEditor,
    type ElementEditPayload,
  } from '@/modules/elements/composables/useElementEditor';
  import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiNodePayload, UiPayload, UiValues} from '@/modules/ui/types';

  const props = defineProps<{
    editor: ElementEditPayload & {saveParams: UiValues};
    /** The layout UIDs of the fields to show, in order. */
    uids: string[];
    /** Whether each field changed, by layout UID. */
    changed: Record<string, boolean | null>;
  }>();

  const emit = defineEmits<{
    /** Whether there are unsaved edits to the current version, in a provisional draft. */
    (event: 'edited', edited: boolean): void;
  }>();

  const contentEl = useTemplateRef<HTMLElement>('content');

  const editor = useElementEditor({
    saveData: () => ({...props.editor.saveParams}),
    root: () => contentEl.value,
  });

  const {
    autosave,
    errors,
    form,
    onMutation,
    props: payload,
    renderer,
    save,
    uiPayload,
    releaseDraftLocked,
    workflowReviewLocked,
  } = editor;

  provide(NestedOwnerEditorKey, editor.nestedOwnerEditor);

  const locked = computed(
    () =>
      workflowReviewLocked.value || releaseDraftLocked.value || payload.readOnly
  );

  function fieldNodes(nodes: UiNodePayload[]): UiNodePayload[] {
    return nodes.flatMap((node) =>
      node.control ? [node] : fieldNodes(node.children ?? [])
    );
  }

  const layoutUid = (node: UiNodePayload) =>
    String(node.props.layoutUid ?? node.uid);

  /** The editor's form, with the comparison's fields and changed markers. */
  const pane = computed<UiPayload | null>(() => {
    const ui = uiPayload.value;

    if (!ui) {
      return null;
    }

    const nodes = new Map(
      fieldNodes(ui.nodes).map((node) => [layoutUid(node), node])
    );
    const tabs = new Map(
      ui.nodes.flatMap((node) =>
        node.component === 'craft:tab'
          ? fieldNodes(node.children ?? []).map(
              (field) => [layoutUid(field), node] as const
            )
          : []
      )
    );
    const groups = new Map<string, [UiNodePayload | null, UiNodePayload[]]>();

    for (const uid of props.uids) {
      const node = nodes.get(uid);

      if (!node) {
        continue;
      }

      const tab = tabs.get(uid) ?? null;
      const key = tab ? `tab:${tab.uid}` : `field:${uid}`;
      const group = groups.get(key) ?? [tab, []];
      groups.set(key, group);
      group[1].push({
        ...node,
        props: {
          ...node.props,
          width: null,
          status: props.changed[uid] ? 'modified' : null,
          statusLabel: props.changed[uid] ? t('Changed') : null,
        },
      });
    }

    return {
      ...ui,
      refreshable: false,
      nodes: [...groups.values()].flatMap(([tab, fields]) =>
        tab ? [{...tab, children: fields}] : fields
      ),
    };
  });

  watch(
    () => payload.isProvisionalDraft,
    (edited) => emit('edited', edited),
    {immediate: true}
  );

  useAppLayout(() => ({
    form,
    onSave: locked.value ? undefined : save,
    saveDisabled: locked.value,
    handlesReadOnly: true,
    submitButtonLabel: payload.editorActions.primary.label,
    defaultFormActions: [],
  }));
</script>

<template>
  <LayoutSlot v-if="form.hasErrors" name="error-summary">
    <ErrorSummary :errors="form.errors" :title="t('Couldn’t save changes')" />
  </LayoutSlot>

  <LayoutSlot v-if="autosave.status.value !== 'idle'" name="footer-meta">
    <AutosaveMessage
      :status="autosave.status.value"
      :saved-at="autosave.savedAt.value"
      :error="autosave.error.value"
      :http-status="autosave.httpStatus.value"
    />
  </LayoutSlot>

  <div ref="content">
    <component
      :is="
        pane.nodes.some((node) => node.component !== 'craft:tab')
          ? 'craft-field-group'
          : 'div'
      "
      v-if="pane?.nodes.length"
    >
      <UiRenderer
        ref="renderer"
        :payload="pane"
        :errors="errors"
        :modified="autosave.modified.value"
        :disabled="workflowReviewLocked || releaseDraftLocked"
        @update:mutation="onMutation"
      />
    </component>
  </div>
</template>
