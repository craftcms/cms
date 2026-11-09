<script setup lang="ts">
  /**
   * An editable side of the Compare screen: the version's own editor, with
   * its autosave, drafts and Save button, showing just the fields the
   * comparison lists, in its order and within their tabs, with sidebar tabs
   * shown as ordinary ones. Changed fields are badged rather than marked with
   * their own statuses, and each field renders in the screen's row for it.
   * Its form re-renders as conditions change, as the editor's does, and the
   * screen follows along.
   * `CompareEditor` provides the editor's payload as the screen's props.
   */
  import {t} from '@craftcms/ui';
  import {computed, nextTick, provide, useTemplateRef, watch} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {firstFocusableWithin} from '@/common/utils/dom';
  import {ButtonVariant} from '@craftcms/ui';
  import FormActions from '@/common/components/FormActions.vue';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import ReleaseCallout from '@/modules/releases/components/ReleaseCallout.vue';
  import WorkflowEditLock from '@/modules/workflows/components/WorkflowEditLock.vue';
  import {
    useElementEditor,
    type ElementEditPayload,
  } from '@/modules/elements/composables/useElementEditor';
  import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
  import type {UiFieldOutlets} from '@/modules/ui/fieldOutlets';
  import {
    editedFields,
    fieldNodes,
    layoutUid,
    rememberValues,
    withoutCompareAction,
  } from '@/modules/elements/compareLayout';
  import {
    canonical,
    nodeHasErrors,
    pathsMatch,
    UiFieldIdScope,
    valueAt,
  } from '@/modules/ui/runtime';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiValueSnapshot} from '@/modules/ui/valueAccess';
  import type {
    UiChange,
    UiNodePayload,
    UiPayload,
    UiValues,
  } from '@/modules/ui/types';

  const props = defineProps<{
    editor: ElementEditPayload & {saveParams: UiValues};
    /** Whether each field changed, by layout UID. */
    changed: Record<string, boolean | null>;
    /** Where each field renders, by layout UID. */
    fieldOutlets: UiFieldOutlets;
    /** The element containing the fields, which answers their requests for the editor. */
    root: HTMLElement | null;
    /** Where the version's footer goes: why it's locked, then its Save button. */
    footerTargets: {notices: HTMLElement | null; actions: HTMLElement | null};
    /** The other side's editor, while Cmd/Ctrl+S should save it instead. */
    saveShortcutTarget?: () => ((event: KeyboardEvent) => void) | null;
    /** Runs before each submission, which goes ahead when it resolves true. */
    beforeSave?: () => Promise<boolean>;
    /** Prefixes the fields' ids, so they stay apart from the other side's. */
    fieldIdScope: string;
    /**
     * Whether the other side is saving, which reloads the page and with it this
     * editor, so an edit made here meanwhile would be lost.
     */
    otherSideSaving?: boolean;
  }>();

  const emit = defineEmits<{
    /** Whether there are unsaved edits to the current version, in a provisional draft. */
    (event: 'edited', edited: boolean): void;
    /** The layout UIDs of the fields with errors, as of the last save. */
    (event: 'errors', uids: string[]): void;
    /** The editor's form as it stands, after a condition shows or hides something, say. */
    (event: 'layout', ui: UiPayload | null): void;
    /**
     * The fields edited here, whose values now differ from those the screen
     * loaded with: each one's value, as a canonical string, by layout UID.
     */
    (event: 'edits', values: Record<string, string>): void;
    /** Whether the version can't be edited for now, as while it's in review. */
    (event: 'locked', locked: boolean): void;
    /** The form's values as they stand, as each edit changes them. */
    (event: 'values', values: UiValues): void;
    /** Whether a save is under way, from when it's asked for until it lands. */
    (event: 'saving', saving: boolean): void;
  }>();

  const contentEl = useTemplateRef<HTMLElement>('content');

  const editor = useElementEditor({
    saveData: () => ({...props.editor.saveParams}),
    root: () => props.root ?? contentEl.value,
    saveShortcutTarget: () => props.saveShortcutTarget?.() ?? null,
    beforeSave: props.beforeSave,
  });

  provide(UiFieldIdScope, props.fieldIdScope);

  const {
    autosave,
    errors,
    form,
    onMutation,
    props: payload,
    refreshLayout,
    renderer,
    save,
    savePending,
    saveForShortcut,
    settleChanges,
    uiPayload,
    values,
    releaseDraftLocked,
    startEditingDraft,
    updatePayload,
    workflowReviewLocked,
  } = editor;

  provide(NestedOwnerEditorKey, editor.nestedOwnerEditor);

  const locked = computed(
    () =>
      workflowReviewLocked.value || releaseDraftLocked.value || payload.readOnly
  );

  /**
   * The editor's form with just its fields, in their tabs, badged when they
   * changed. Everything else in the layout, such as headings and tips, is left
   * out, as the Compare screen only lines up fields.
   */
  function toPane(ui: UiPayload): UiPayload {
    const field = (node: UiNodePayload): UiNodePayload => {
      const uid = layoutUid(node);

      return withoutCompareAction({
        ...node,
        props: {
          ...node.props,
          width: null,
          status: null,
          statusLabel: null,
          // The screen marks changes itself, so not with the editor's indicator too.
          showStatus: false,
          labelBadge: props.changed[uid] ? t('Changed') : null,
        },
      });
    };

    return {
      ...ui,
      nodes: ui.nodes.flatMap((node) => {
        if (node.component !== 'craft:tab') {
          return node.control ? [field(node)] : [];
        }

        const children = fieldNodes(node.children ?? []).map(field);

        return children.length || node.props.deferred
          ? [{...node, children}]
          : [];
      }),
    };
  }

  const pane = computed(() =>
    uiPayload.value ? toPane(uiPayload.value) : null
  );

  /** Re-renders the form for a condition, say, keeping it to the screen's fields. */
  async function refresh(
    values: UiValues,
    scope?: string[]
  ): Promise<UiPayload> {
    const ui = await refreshLayout(values, scope);

    return !scope || pathsMatch(scope, uiPayload.value?.scope ?? [])
      ? toPane(ui)
      : ui;
  }

  watch(uiPayload, (ui) => emit('layout', ui ?? null), {immediate: true});

  watch(
    () => payload.isProvisionalDraft,
    (edited) => emit('edited', edited),
    {immediate: true}
  );

  watch(
    () =>
      fieldNodes(pane.value?.nodes ?? [])
        .filter((node) => nodeHasErrors(node, errors.value))
        .map(layoutUid),
    (uids) => emit('errors', uids),
    {immediate: true}
  );

  /** Each field's value as the screen loaded it, which edits are measured against. */
  const loadedValues = new Map<string, string>();

  watch(pane, (ui) => ui && rememberValues(ui.nodes, ui.values, loadedValues), {
    immediate: true,
  });

  function onChange(_change: UiChange, values: UiValues): void {
    const nodes = fieldNodes(pane.value?.nodes ?? []);
    const edited = new Set(editedFields(nodes, values, loadedValues));

    emit(
      'edits',
      Object.fromEntries(
        nodes.flatMap((node) =>
          node.control && edited.has(layoutUid(node))
            ? [[layoutUid(node), canonical(valueAt(values, node.control.path))]]
            : []
        )
      )
    );
  }

  watch(locked, (isLocked) => emit('locked', isLocked), {immediate: true});

  watch(values, (current) => emit('values', current), {immediate: true});

  watch(
    () => form.processing || savePending.value,
    (saving) => emit('saving', saving),
    {immediate: true}
  );

  /**
   * Gives a field a value read from elsewhere, such as the other version's, as
   * if it were entered there. Returns what the field had before, to write back
   * to undo it (`null` when it can't be read), or `null` when the field
   * couldn't take the value.
   */
  function copyValue(
    uid: string,
    snapshot: UiValueSnapshot
  ): {previous: UiValueSnapshot | null} | null {
    const path = fieldNodes(pane.value?.nodes ?? []).find(
      (node) => layoutUid(node) === uid
    )?.control?.path;

    if (!path || locked.value || props.otherSideSaving || !renderer.value) {
      return null;
    }

    const previous = renderer.value.readValue(path);

    if (!renderer.value.writeValue(path, snapshot)) {
      return null;
    }

    onChange({kind: 'discrete', path}, renderer.value.currentValues());

    return {previous};
  }

  defineExpose({
    copyValue,
    save,
    saveForShortcut,
    settleChanges,
    form,
    nestedOwnerEditor: editor.nestedOwnerEditor,
  });

  /** Unlocks the draft's fields, and moves focus to the first one. */
  async function startEditing(): Promise<void> {
    startEditingDraft();
    await nextTick();

    const first = fieldNodes(pane.value?.nodes ?? [])[0];
    const outlet = first ? props.fieldOutlets(layoutUid(first)) : undefined;

    if (outlet) {
      firstFocusableWithin(outlet)?.focus();
    }
  }

  const notice = computed(() =>
    workflowReviewLocked.value
      ? 'review'
      : releaseDraftLocked.value && payload.release
        ? 'release'
        : null
  );
  /** Whether there's a Save button or autosave status to show. */
  const hasActions = computed(
    () => !locked.value || autosave.status.value !== 'idle'
  );
</script>

<template>
  <LayoutSlot v-if="form.hasErrors" name="error-summary">
    <ErrorSummary :errors="form.errors" :title="t('Couldn’t save changes')" />
  </LayoutSlot>

  <!-- The notices' target is a live region, there before they are, so they're announced. -->
  <Teleport v-if="footerTargets.notices && notice" :to="footerTargets.notices">
    <WorkflowEditLock
      v-if="notice === 'review'"
      :payload="payload"
      :update-payload="updatePayload"
      @start-editing="startEditing"
    />
    <ReleaseCallout
      v-else-if="notice === 'release'"
      :release="payload.release!"
      locked
      :can-start-editing="!payload.readOnly"
      @start-editing="startEditing"
    />
  </Teleport>

  <Teleport
    v-if="footerTargets.actions && hasActions"
    :to="footerTargets.actions"
  >
    <div class="compare-footer__actions">
      <!-- The Save button saves this side, whichever side the page's form
           would submit for. -->
      <FormActions
        :form="form"
        :read-only="payload.readOnly"
        :save-disabled="locked"
      >
        <template #primary-action>
          <craft-button
            type="button"
            :variant="ButtonVariant.Primary"
            .loading="form.processing || savePending"
            .disabled="form.processing || savePending || otherSideSaving"
            @click="save()"
          >
            {{ payload.editorActions.primary.label ?? t('Save') }}
          </craft-button>
        </template>
      </FormActions>
      <AutosaveMessage
        v-if="autosave.status.value !== 'idle'"
        :status="autosave.status.value"
        :saved-at="autosave.savedAt.value"
        :error="autosave.error.value"
        :http-status="autosave.httpStatus.value"
      />
    </div>
  </Teleport>

  <div ref="content">
    <UiRenderer
      v-if="pane?.nodes.length"
      ref="renderer"
      :payload="pane"
      :errors="errors"
      :modified="autosave.modified.value"
      :read-only="workflowReviewLocked || releaseDraftLocked || otherSideSaving"
      :refresh="pane.refreshable ? refresh : undefined"
      :field-outlets="fieldOutlets"
      @change="onChange"
      @update:mutation="onMutation"
    />
  </div>
</template>

<style scoped>
  .compare-footer__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
    min-height: var(--cp-footer-height);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
  }
</style>
