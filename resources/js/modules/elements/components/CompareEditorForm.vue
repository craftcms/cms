<script setup lang="ts">
  /**
   * The editable side of the Compare screen: the version's own editor, with
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
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {firstFocusableWithin} from '@/common/utils/dom';
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
    sideIconHtml,
    withoutCompareAction,
  } from '@/modules/elements/compareLayout';
  import {
    canonical,
    nodeHasErrors,
    pathsMatch,
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
  }>();

  const contentEl = useTemplateRef<HTMLElement>('content');

  const editor = useElementEditor({
    saveData: () => ({...props.editor.saveParams}),
    root: () => props.root ?? contentEl.value,
  });

  const {
    autosave,
    errors,
    form,
    onMutation,
    props: payload,
    refreshLayout,
    renderer,
    save,
    uiPayload,
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
          headingPrefix: sideIconHtml('to'),
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

    if (!path || locked.value || !renderer.value) {
      return null;
    }

    const previous = renderer.value.readValue(path);

    if (!renderer.value.writeValue(path, snapshot)) {
      return null;
    }

    onChange({kind: 'discrete', path}, renderer.value.currentValues());

    return {previous};
  }

  defineExpose({copyValue});

  /** Unlocks the draft's fields, and moves focus to the first one. */
  async function startEditing(): Promise<void> {
    startEditingDraft();
    await nextTick();

    if (props.root) {
      firstFocusableWithin(props.root)?.focus();
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

  // The page's form still submits through `onSave`, but the screen's own
  // footer, rather than the page's, holds the Save button, on the side it saves.
  useAppLayout(() => ({
    form,
    onSave: locked.value ? undefined : save,
    saveDisabled: locked.value,
    handlesReadOnly: true,
    hideFooter: true,
  }));
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
      <FormActions
        :form="form"
        :submit-label="payload.editorActions.primary.label"
        :read-only="payload.readOnly"
        :save-disabled="locked"
      />
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
      :read-only="workflowReviewLocked || releaseDraftLocked"
      :refresh="pane.refreshable ? refresh : undefined"
      :field-outlets="fieldOutlets"
      @change="onChange"
      @update:mutation="onMutation"
    />
  </div>
</template>

<style scoped>
  /*
   * Matches the page shell's footer, frosted over the fields it floats over,
   * across both sides, with its buttons on the side they save.
   */
  .compare-footer__actions {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
    min-height: var(--cp-footer-height);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    border-block-start: 1px solid var(--c-color-border-quiet);
    background-color: color-mix(
      in oklab,
      var(--c-surface-default) 70%,
      transparent
    );
    backdrop-filter: blur(var(--blur-md, 12px));
  }

  @container compare (width >= 48rem) {
    .compare-footer__actions {
      padding-inline-start: calc(50% + 1px + var(--cp-container-padding));
    }

    /* The hairline runs on through, below the footer's top border. */
    .compare-footer__actions::before {
      content: '';
      position: absolute;
      inset-block: 1px 0;
      inset-inline-start: 50%;
      border-inline-start: 1px solid var(--compare-hairline);
      pointer-events: none;
    }
  }
</style>
