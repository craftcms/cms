<script setup lang="ts">
  /**
   * The element editor, for full pages and slideouts alike. It renders no
   * chrome of its own: it configures the surrounding shell through
   * `useAppLayout()` and `LayoutSlot`, so the shell decides where the header,
   * save controls and details column go.
   */
  import {t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    provide,
    useTemplateRef,
    type Component,
  } from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import MetadataDetailsContent from '@/common/components/MetadataDetailsContent.vue';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import ElementActionMenu from '@/modules/elements/components/ElementActionMenu.vue';
  import ElementActivityAvatars from '@/modules/elements/components/ElementActivityAvatars.vue';
  import ElementViewButtons from '@/modules/elements/components/ElementViewButtons.vue';
  import ElementContextMenu from '@/modules/elements/components/ElementContextMenu.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useIsSlideout} from '@/common/composables/screen';
  import {useSlideout} from '@/common/slideouts/useSlideout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import {useElementEditor} from '@/modules/elements/composables/useElementEditor';
  import type {FormValues} from '@/modules/forms/types';
  import ElementDetailsTabs from '@/modules/elements/components/ElementDetailsTabs.vue';
  import {elementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
  import type {FormSaveOptions} from '@/common/types';
  import WorkflowEditLockCallout from '@/modules/workflows/components/WorkflowEditLockCallout.vue';
  import WorkflowDraftsStatus from '@/modules/workflows/components/WorkflowDraftsStatus.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import VarDump from '@/common/components/VarDump.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import {
    NestedOwnerEditorKey,
    nestedOwnerContext,
  } from '@/modules/elements/nested-owner';

  const contentEl = useTemplateRef<HTMLElement>('content');
  const slideout = useSlideout();

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => FormValues;
    transform?: (data: object) => FormValues;
    formWrapper?: Component;
    showDetails?: boolean;
  }>();

  const editor = useElementEditor({
    saveData: props.saveData,
    transform: props.transform,
    root: () => contentEl.value,
  });

  const {
    activity,
    activityTimelineVersion,
    autosave,
    discardDraft,
    errors,
    form,
    formPayload,
    onMutation,
    onSidebarMutation,
    props: payload,
    renderer,
    refreshAfterNestedChange,
    refreshForm,
    refreshLayout,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    startEditingReviewedDraft,
    submitAction,
    updatePayload,
    workflowReviewLocked,
  } = editor;

  provide(NestedOwnerEditorKey, {
    async prepare(path) {
      if (payload.readOnly) {
        return null;
      }

      if (payload.canAutosave) {
        await autosave.save();
        if (autosave.status.value !== 'saved') {
          return null;
        }
        await nextTick();
      }

      const context = nestedOwnerContext(formPayload.value, path);
      return context
        ? {
            ...context,
            requiresDerivative: Boolean(payload.canAutosave),
            canonicalId: payload.canonicalId,
            draftId: payload.draftId,
            isProvisionalDraft: payload.isProvisionalDraft,
          }
        : null;
    },
    async refresh() {
      await refreshAfterNestedChange();
    },
  });

  /**
   * Fields outside of a tab — in a field layout whose tab was never saved,
   * say — need the spacing a tab would otherwise give them.
   */
  const hasUntabbedFields = computed(
    () =>
      formPayload.value?.nodes.some((node) => node.component !== 'craft:tab') ??
      false
  );

  const hasDetails = computed(
    () =>
      Boolean(props.showDetails) ||
      Boolean(sidebarPayload.value) ||
      Boolean(payload.metadataHtml) ||
      Boolean(payload.activityTimelineUrl) ||
      elementDetailsTabRegistry.hasVisible(payload)
  );
  const detailsTabs = useTemplateRef<{select: (tabId: string) => void}>(
    'detailsTabs'
  );

  // Alternate saves in the Save button's menu, and the buttons grouped with
  // it (Create a draft, …).
  const formActionItems = computed(() =>
    payload.editorActions.menu.map((action) => ({
      label: action.label,
      shortcut: action.shortcut ? {key: 'S', shift: action.shift} : undefined,
      onClick: () => submitAction(action),
    }))
  );

  const saveButtons = computed(() =>
    payload.editorActions.buttons.map((action) => ({
      label: action.label,
      variant: action.variant,
      disabled: action.disabled,
      disabledReason: action.disabledReason,
      onClick: () => submitAction(action),
    }))
  );

  // Mirrors the legacy wording: a changed draft names the draft, anything else
  // names the element type.
  const staleMessage = computed(() =>
    t('This {type} has been updated.', {
      type:
        activity.staleType.value === 'element' &&
        payload.draftId !== null &&
        !payload.isProvisionalDraft
          ? t('draft')
          : payload.elementDisplayName,
    })
  );

  // In a panel, the panel's own screen is what's out of date — not the page
  // behind it.
  function reload(): void {
    if (slideout) {
      void slideout.reload();
    } else {
      router.reload();
    }
  }

  function primaryAction(options?: FormSaveOptions): void {
    if (payload.editorActions.primary.tabId) {
      detailsTabs.value?.select(payload.editorActions.primary.tabId);
      return;
    }

    save(options);
  }

  // The View buttons stay out of slideouts, which have no room for them. The
  // action menu goes in their header, as in Craft 5. Neither shows on pages the
  // shell marks read-only.
  const isSlideout = useIsSlideout();
  const page = usePage<{readOnly?: boolean}>();
  const showElementControls = computed(
    () => !isSlideout && !page.props.readOnly
  );
  const showActionMenu = computed(() => isSlideout || !page.props.readOnly);

  useAppLayout(() => ({
    title: payload.title,
    form,
    onSave: workflowReviewLocked.value ? undefined : primaryAction,
    saveDisabled: workflowReviewLocked.value,
    submitButtonLabel: payload.editorActions.primary.label,
    // The element supplies its own full set of alternate saves — including its
    // own "Save and continue editing" — so the layout's default would duplicate.
    defaultFormActions: [],
    formActions: formActionItems.value,
    formAdditionalButtons: saveButtons.value,
    editUrl: payload.cpEditUrl,
  }));
</script>

<template>
  <LayoutSlot v-if="payload.contextMenu" name="context-menu">
    <ElementContextMenu
      :label="payload.contextMenu.label"
      :items="payload.contextMenu.items"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="payload.isProvisionalDraft || payload.statusLabelHtml"
    name="content-toolbar-meta"
  >
    <craft-badge
      v-if="payload.isProvisionalDraft"
      fill="info"
      class="relative text-sm font-normal inline-flex"
    >
      <craft-icon name="pen-circle" slot="prefix"></craft-icon>
      {{ t('Edited') }}
    </craft-badge>
    <DynamicHtmlRenderer
      v-else-if="payload.statusLabelHtml"
      :html="payload.statusLabelHtml"
    />
  </LayoutSlot>

  <!--
    Each piece below is its own component, so moving one is a matter of changing
    the layout slot it's placed in. The save buttons are the exception: they
    ride in `useAppLayout`'s form-action props, so the shell groups them with
    its Save button.
  -->
  <LayoutSlot
    v-if="
      activity.activity.value.length ||
      (!isSlideout && payload.workflow.draftReviews.length) ||
      (showElementControls && payload.previewTargets.length)
    "
    name="content-toolbar-meta"
  >
    <ElementActivityAvatars :entries="activity.activity.value" />
    <WorkflowDraftsStatus
      v-if="!isSlideout && payload.workflow.draftReviews.length"
      :drafts="payload.workflow.draftReviews"
    />
    <ElementViewButtons
      v-if="showElementControls"
      :targets="payload.previewTargets"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="showActionMenu && payload.actionMenu.length"
    name="content-toolbar-actions"
  >
    <ElementActionMenu
      :items="payload.actionMenu"
      :current-entry-type-id="form.typeId"
      :slideout="slideout"
      :flush="!isSlideout"
    />
  </LayoutSlot>

  <!-- Where the legacy editor puts its spinner and checkmark. -->
  <LayoutSlot v-if="autosave.status.value !== 'idle'" name="additional-buttons">
    <AutosaveMessage
      :status="autosave.status.value"
      :saved-at="autosave.savedAt.value"
      :error="autosave.error.value"
      :http-status="autosave.httpStatus.value"
      @refresh="reload"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="
      activity.isStale.value ||
      payload.readOnly ||
      payload.notice ||
      payload.mergeNotice ||
      payload.workflow.convertedToDraft ||
      workflowReviewLocked
    "
    name="content-notices"
  >
    <div class="element-notices">
      <craft-callout
        v-if="payload.workflow.convertedToDraft && !workflowReviewLocked"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{
          t(
            'Your changes are saved in a draft and won’t be published until the draft is approved and applied.'
          )
        }}
      </craft-callout>

      <WorkflowEditLockCallout
        v-if="workflowReviewLocked"
        class="mb-4"
        @start-editing="startEditingReviewedDraft"
      />

      <craft-callout
        v-if="activity.isStale.value"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
        appearance="fill"
        rounded="none"
      >
        {{ staleMessage }}

        <craft-button
          slot="action"
          type="button"
          variant="outline"
          size="small"
          @click="reload"
          inherit
        >
          {{ t('Reload') }}
        </craft-button>
      </craft-callout>

      <craft-callout
        v-if="payload.readOnly"
        variant="neutral"
        rounded="none"
        appearance="fill"
        icon="lock"
      >
        {{ t('This is a read-only view.') }}
      </craft-callout>

      <craft-callout
        v-if="payload.notice"
        variant="accent"
        icon="edit"
        class="mb-4"
        rounded="none"
        appearance="fill"
      >
        {{ payload.notice }}

        <craft-button
          v-if="payload.canDiscardDraft"
          slot="action"
          type="button"
          variant="outline"
          size="small"
          @click="discardDraft"
          inherit
        >
          {{ t('Discard changes') }}
        </craft-button>
      </craft-callout>

      <craft-callout
        v-if="payload.mergeNotice"
        variant="warning"
        icon="triangle-exclamation"
        class="mb-4"
      >
        {{ payload.mergeNotice }}
      </craft-callout>
    </div>
  </LayoutSlot>

  <div ref="content" class="py-3">
    <CpContainer>
      <component
        :is="formWrapper ?? 'div'"
        v-bind="formWrapper ? {editor, region: 'content'} : {}"
      >
        <component
          :is="hasUntabbedFields ? 'craft-field-group' : 'div'"
          v-if="formPayload"
        >
          <FormRenderer
            ref="renderer"
            :payload="formPayload"
            :errors="errors"
            :refresh="formPayload.refreshable ? refreshLayout : undefined"
            :modified="autosave.modified.value"
            :disabled="workflowReviewLocked"
            @update:mutation="onMutation"
          />
        </component>
      </component>

      <slot :payload="payload" />
    </CpContainer>
  </div>

  <LayoutSlot
    v-if="hasDetails || $slots['details-header']"
    name="content-details"
  >
    <ElementDetailsTabs
      ref="detailsTabs"
      :payload="payload"
      :activity-timeline-version="activityTimelineVersion"
      :update-payload="updatePayload"
      :submit-action="submitAction"
      :sync-location-hash="!isSlideout"
    >
      <template #info>
        <!-- Anything the element type shows above its meta fields, e.g. an
        asset's file preview. -->
        <slot name="details-header" :payload="payload" />

        <MetadataDetailsContent :html="payload.metadataHtml">
          <template #default>
            <component
              :is="formWrapper ?? 'div'"
              v-bind="formWrapper ? {editor, region: 'sidebar'} : {}"
            >
              <craft-field-group v-if="sidebarPayload">
                <FormRenderer
                  ref="sidebarRenderer"
                  :payload="sidebarPayload"
                  :errors="sidebarErrors"
                  :modified="autosave.modified.value"
                  :disabled="workflowReviewLocked"
                  @update:mutation="onSidebarMutation"
                />
              </craft-field-group>
            </component>
          </template>
        </MetadataDetailsContent>
      </template>
    </ElementDetailsTabs>
  </LayoutSlot>
</template>

<style scoped lang="scss">
  craft-callout {
    --c-callout-padding-inline: calc(var(--cp-container-padding) - 4px);
  }
</style>
