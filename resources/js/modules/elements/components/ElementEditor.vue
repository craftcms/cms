<script setup lang="ts">
  /**
   * The element editor without page chrome: it configures the surrounding
   * layout through `useAppLayout()` and `LayoutSlot` rather than rendering one,
   * so a host owns the header, form and footer. `ElementEditScreen` is the
   * full-page counterpart.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {router} from '@inertiajs/vue3';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import ElementContextMenu from '@/modules/elements/components/ElementContextMenu.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import {useElementEditor} from '@/modules/elements/composables/useElementEditor';
  import {useElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {FormValues} from '@/modules/forms/types';

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => FormValues;
  }>();

  const {
    activity,
    autosave,
    discardDraft,
    errors,
    form,
    formPayload,
    onMutation,
    onSidebarMutation,
    props: payload,
    renderer,
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    submitAction,
  } = useElementEditor({saveData: props.saveData});

  // Alternate saves in the Save button's menu, and the buttons beside it.
  const formActionItems = computed(() =>
    payload.formActions.map((action) => ({
      label: action.label,
      onClick: () => submitAction(action),
    }))
  );

  const headerButtons = computed(() =>
    payload.headerActions.map((action) => ({
      label: action.label,
      variant: action.variant,
      onClick: () => submitAction(action),
    }))
  );

  // The element's own actions (Validate, Copy, Delete, …). Behaviors are
  // dispatched client-side rather than via registered jQuery handlers.
  const actionMenuItems = useElementActionMenu(() => payload.actionMenu, {
    // The entry type can be switched in the sidebar without saving, so the
    // settings slideout should follow the field rather than the stored value.
    currentEntryTypeId: () => form.typeId ?? null,
  });

  // "View" opens the element on the front end. The hrefs arrive ready to
  // follow — a live element points at its own URL, anything else at a
  // token-minting redirect that lands on the tokenized preview.
  //
  // These ride in `formAdditionalButtons` because the layout's
  // `additional-buttons` slot is a component slot, not a layout-slot outlet,
  // so it can't be filled from a page using the ambient layout.
  const viewButtons = computed(() =>
    payload.previewTargets.map((target, index) => ({
      label: payload.previewTargets.length === 1 ? t('View') : target.label,
      variant: 'outline',
      key: `view-${index}`,
      onClick: () => window.open(target.url, '_blank', 'noopener'),
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

  function reload(): void {
    router.reload();
  }

  const autosaveMessage = computed(() => {
    switch (autosave.status.value) {
      case 'saving':
        return t('Saving…');
      case 'saved':
        return autosave.savedAt.value
          ? t('Saved {timestamp}', {timestamp: autosave.savedAt.value})
          : t('Saved');
      case 'failed':
        return autosave.error.value ?? t('Couldn’t save draft.');
      default:
        return null;
    }
  });

  useAppLayout(() => ({
    title: payload.title,
    form,
    onSave: save,
    submitButtonLabel: payload.submitButtonLabel,
    // The element supplies its own full set of alternate saves — including its
    // own "Save and continue editing" — so the layout's default would duplicate.
    defaultFormActions: [],
    formActions: formActionItems.value,
    formAdditionalButtons: [...viewButtons.value, ...headerButtons.value],
    formAdditionalActions: actionMenuItems.value,
  }));
</script>

<template>
  <LayoutSlot v-if="payload.contextMenu" name="context-menu">
    <ElementContextMenu
      :label="payload.contextMenu.label"
      :items="payload.contextMenu.items"
    />
  </LayoutSlot>

  <!--
    Tabs are rendered by `FormNodeList` inside the form itself, and header
    actions come through `useAppLayout`'s form-action props — filling the
    `actions` layout slot here would replace the layout's own save button.
  -->
  <!--
    Autosave state lives next to the save button, where the legacy editor puts
    its spinner and checkmark.
  -->
  <LayoutSlot v-if="autosaveMessage" name="toolbar">
    <span
      class="text-sm text-neutral-text-quiet"
      role="status"
      aria-live="polite"
      :class="{'text-danger-text': autosave.status.value === 'failed'}"
    >
      {{ autosaveMessage }}
    </span>
  </LayoutSlot>

  <!-- Who else is working on this element, beside the save controls. -->
  <LayoutSlot v-if="activity.activity.value.length" name="toolbar">
    <div
      role="region"
      :aria-label="t('Recent Activity')"
      class="flex items-center gap-1"
    >
      <span
        v-for="entry in activity.activity.value"
        :key="entry.userId"
        :title="entry.message"
        :aria-label="entry.message"
        class="inline-flex"
        v-html="entry.userThumb"
      />
    </div>
  </LayoutSlot>

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

  <craft-callout v-if="payload.readOnly" variant="neutral" icon="lock">
    {{ t('This is a read-only view.') }}
  </craft-callout>

  <craft-callout
    v-if="payload.notice"
    variant="neutral"
    icon="edit"
    class="mb-4"
  >
    {{ payload.notice }}

    <craft-button
      v-if="payload.canDiscardDraft"
      slot="action"
      type="button"
      appearance="outline"
      size="small"
      @click="discardDraft"
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

  <FormRenderer
    v-if="formPayload"
    ref="renderer"
    :payload="formPayload"
    :errors="errors"
    @update:mutation="onMutation"
  />

  <slot :payload="payload" />

  <LayoutSlot
    v-if="sidebarPayload || payload.metadataHtml || $slots['details-header']"
    name="details"
  >
    <!-- Anything the element type shows above its meta fields, e.g. an
      asset's file preview. -->
    <slot name="details-header" :payload="payload" />

    <!--
      The meta fields render as their own Form, bridged into the same Inertia
      form as the field layout above, so they submit as ordinary inputs.
    -->
    <FormRenderer
      v-if="sidebarPayload"
      ref="sidebarRenderer"
      :payload="sidebarPayload"
      :errors="sidebarErrors"
      @update:mutation="onSidebarMutation"
    />

    <DynamicHtmlRenderer
      v-if="payload.metadataHtml"
      :html="payload.metadataHtml"
    />
  </LayoutSlot>
</template>
