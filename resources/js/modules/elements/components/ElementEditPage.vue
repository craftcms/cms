<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import ElementContextMenu from '@/modules/elements/components/ElementContextMenu.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import Pane from '@/common/components/Pane.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import {useElementEditPage} from '@/modules/elements/composables/useElementEditPage';

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => Record<string, unknown>;
  }>();

  const {
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
  } = useElementEditPage({saveData: props.saveData});

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
    formAdditionalButtons: headerButtons.value,
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

  <Pane appearance="raised">
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
  </Pane>

  <LayoutSlot v-if="sidebarPayload || payload.metadataHtml" name="details">
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
