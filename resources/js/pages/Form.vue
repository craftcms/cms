<script setup lang="ts">
  import {useForm} from '@inertiajs/vue3';
  import Pane from '@/common/components/Pane.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {
    FormChange,
    FormChangeKind,
    FormPayload,
  } from '@/modules/forms/types';
  import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';

  const props = defineProps<{
    form: FormPayload;
    submit: {
      method: 'delete' | 'get' | 'patch' | 'post' | 'put';
      url: string;
    };
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange, values: FormPayload['values']): void;
  }>();
  const inertiaForm = useForm<Record<string, any>>({});
  const {advanceBaseline, errors, onMutation, renderer} =
    useInertiaFormRenderer(inertiaForm, () => props.form);
  const {save} = useSettingsSave(inertiaForm, () => props.submit, {
    transform: () => renderer.value?.currentValues() ?? props.form.values,
    onSuccess: advanceBaseline,
  });

  useAppLayout({form: inertiaForm, onSave: save});

  function setValue(
    path: string[],
    value: unknown,
    kind: FormChangeKind = 'discrete'
  ): void {
    renderer.value?.setValue(path, value, kind);
  }

  function onChange(change: FormChange, values: FormPayload['values']): void {
    emit('change', change, values);
  }

  defineExpose({setValue});
</script>

<template>
  <Pane appearance="raised">
    <craft-field-group>
      <FormRenderer
        ref="renderer"
        :payload="form"
        :errors="errors"
        @update:mutation="onMutation"
        @change="onChange"
      />
    </craft-field-group>
  </Pane>
</template>
