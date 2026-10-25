<script setup lang="ts">
  import {ref} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {store} from '@actions/Settings/AddressSettingsController';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = defineProps<{
    title: string;
    fieldLayoutDesigner: {html: string};
  }>();

  const form = useForm({});

  // The field layout designer renders inside `fieldLayoutDesigner.html` as a
  // self-booting custom element. `fldHost` just scopes the submit-time
  // `serialize()` queries below, since the element lives in the compiled markup
  // and can't be reffed directly.
  const fldHost = ref<HTMLElement>();

  // Inertia posts the form object, not the designer's own inputs, so read the
  // values back out of the self-booting custom elements at submit. `fieldLayout`
  // must stay a string (the server `JsonHelper::decode()`s it); `generatedFields`
  // is the ordered row list.
  const {save} = useSettingsSave(form, store, {
    transform: (data) => ({
      ...data,
      fieldLayout:
        fldHost.value
          ?.querySelector('craft-field-layout-designer')
          ?.serialize() ?? '{}',
      generatedFields:
        fldHost.value
          ?.querySelector('craft-generated-fields-table')
          ?.serialize() ?? [],
    }),
  });

  useAppLayout({
    title: props.title,
    form,
    defaultFormActions: [],
    onSave: save,
  });
</script>

<template>
  <CpContainer>
    <div ref="fldHost">
      <DynamicHtmlRenderer :html="fieldLayoutDesigner.html" />
    </div>
  </CpContainer>
</template>
