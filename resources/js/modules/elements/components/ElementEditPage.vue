<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
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
  } = useElementEditPage({saveData: props.saveData});

  useAppLayout(() => ({
    title: payload.title,
    form,
    onSave: save,
  }));
</script>

<template>
  <!--
    Tabs are rendered by `FormNodeList` inside the form itself, and header
    actions come through `useAppLayout`'s form-action props — filling the
    `actions` layout slot here would replace the layout's own save button.
  -->
  <Pane appearance="raised">
    <craft-callout v-if="payload.readOnly" variant="neutral" icon="lock">
      {{ t('This is a read-only view.') }}
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
