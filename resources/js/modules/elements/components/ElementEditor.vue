<script setup lang="ts">
  /**
   * The element editor, for full pages and slideouts alike. It renders no
   * chrome of its own: it configures the surrounding shell through
   * `useAppLayout()` and `LayoutSlot`, so the shell decides where the header,
   * save controls and details column go.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import AutosaveMessage from '@/modules/elements/components/AutosaveMessage.vue';
  import ElementActionMenu from '@/modules/elements/components/ElementActionMenu.vue';
  import ElementActivityAvatars from '@/modules/elements/components/ElementActivityAvatars.vue';
  import ElementViewButtons from '@/modules/elements/components/ElementViewButtons.vue';
  import ElementContextMenu from '@/modules/elements/components/ElementContextMenu.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useIsSlideout} from '@/common/composables/screen';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import {useElementEditor} from '@/modules/elements/composables/useElementEditor';
  import type {FormValues} from '@/modules/forms/types';
  import ElementDetailsTabs from '@/modules/elements/components/ElementDetailsTabs.vue';
  import {elementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
  import CpContainer from '@/common/components/CpContainer.vue';
  import VarDump from '@/common/components/VarDump.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';

  const props = defineProps<{
    /**
     * Identity attributes merged into every submission — the one per-type
     * piece of the pipeline (e.g. an entry's `entryId`/`sectionId`).
     */
    saveData?: () => FormValues;
  }>();

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
    save,
    sidebarErrors,
    sidebarPayload,
    sidebarRenderer,
    submitAction,
  } = useElementEditor({saveData: props.saveData});

  const hasDetails = computed(
    () =>
      Boolean(sidebarPayload.value) ||
      Boolean(payload.metadataHtml) ||
      Boolean(payload.activityTimelineUrl) ||
      elementDetailsTabRegistry.hasVisible(payload)
  );

  // Alternate saves in the Save button's menu, and the buttons grouped with
  // it (Create a draft, …).
  const formActionItems = computed(() =>
    payload.formActions.map((action) => ({
      label: action.label,
      onClick: () => submitAction(action),
    }))
  );

  const saveButtons = computed(() =>
    payload.headerActions.map((action) => ({
      label: action.label,
      variant: action.variant,
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

  function reload(): void {
    router.reload();
  }

  // The View buttons and the action menu stay out of slideouts, which have no
  // room for them, and out of pages the shell marks read-only.
  const isSlideout = useIsSlideout();
  const page = usePage<{readOnly?: boolean}>();
  const showElementControls = computed(
    () => !isSlideout && !page.props.readOnly
  );

  useAppLayout(() => ({
    title: payload.title,
    form,
    onSave: save,
    submitButtonLabel: payload.submitButtonLabel,
    // The element supplies its own full set of alternate saves — including its
    // own "Save and continue editing" — so the layout's default would duplicate.
    defaultFormActions: [],
    formActions: formActionItems.value,
    formAdditionalButtons: saveButtons.value,
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
      (showElementControls && payload.previewTargets.length)
    "
    name="content-toolbar-meta"
  >
    <ElementActivityAvatars :entries="activity.activity.value" />
    <ElementViewButtons
      v-if="showElementControls"
      :targets="payload.previewTargets"
    />
  </LayoutSlot>

  <LayoutSlot
    v-if="showElementControls && payload.actionMenu.length"
    name="content-toolbar-actions"
  >
    <ElementActionMenu
      :items="payload.actionMenu"
      :current-entry-type-id="form.typeId"
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
      payload.mergeNotice
    "
    name="content-notices"
  >
    <div class="element-notices">
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

  <div class="py-3">
    <CpContainer>
      <FormRenderer
        v-if="formPayload"
        ref="renderer"
        :payload="formPayload"
        :errors="errors"
        :modified="autosave.modified.value"
        @update:mutation="onMutation"
      />

      <slot :payload="payload" />
    </CpContainer>
  </div>

  <LayoutSlot
    v-if="hasDetails || $slots['details-header']"
    name="content-details"
  >
    <ElementDetailsTabs
      :payload="payload"
      :activity-timeline-version="activityTimelineVersion"
    >
      <template #info>
        <!-- Anything the element type shows above its meta fields, e.g. an
        asset's file preview. -->
        <slot name="details-header" :payload="payload" />

        <div class="p-lg">
          <!--
          The meta fields render as their own Form, bridged into the same Inertia
          form as the field layout above, so they submit as ordinary inputs.
        -->
          <craft-field-group>
            <FormRenderer
              v-if="sidebarPayload"
              ref="sidebarRenderer"
              :payload="sidebarPayload"
              :errors="sidebarErrors"
              :modified="autosave.modified.value"
              @update:mutation="onSidebarMutation"
            />
          </craft-field-group>

          <hr class="my-lg" />
          <DynamicHtmlRenderer
            v-if="payload.metadataHtml"
            :html="payload.metadataHtml"
          />
        </div>
      </template>
    </ElementDetailsTabs>
  </LayoutSlot>
</template>
