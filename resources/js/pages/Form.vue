<script setup lang="ts">
  import {actionClient} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {useForm} from '@inertiajs/vue3';
  import {shallowRef, toRaw} from 'vue';
  import {
    useAppLayout,
    type UseAppLayoutOptions,
  } from '@/common/composables/useAppLayout';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {
    FormChange,
    FormChangeKind,
    FormPayload,
    FormValue,
    FormValues,
  } from '@/modules/forms/types';
  import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    elevatedFields?: string[] | '*';
    refreshUrl?: string;
    defaultFormActions?: UseAppLayoutOptions['defaultFormActions'];
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange, values: FormPayload['values']): void;
  }>();
  const inertiaForm = useForm({});
  const elevatedBaseline = shallowRef(
    structuredClone(toRaw(props.form.values))
  );
  const elevatedFields = props.elevatedFields;
  const {advanceBaseline, errors, onMutation, renderer} =
    useInertiaFormRenderer(inertiaForm, () => props.form);
  const {save} = useSettingsSave(inertiaForm, () => props.submit, {
    transform: () => renderer.value?.currentValues() ?? props.form.values,
    onSuccess: () => {
      elevatedBaseline.value = structuredClone(
        toRaw(renderer.value?.currentValues() ?? props.form.values)
      );
      advanceBaseline();
    },
    passwordConfirmation: elevatedFields
      ? {
          required: () => {
            const values = renderer.value?.currentValues() ?? props.form.values;
            const fields =
              elevatedFields === '*'
                ? [
                    ...new Set([
                      ...Object.keys(elevatedBaseline.value),
                      ...Object.keys(values),
                    ]),
                  ]
                : elevatedFields;

            return fields.some(
              (field) =>
                normalize(values[field]) !==
                normalize(elevatedBaseline.value[field])
            );
          },
        }
      : undefined,
  });

  useAppLayout({
    form: inertiaForm,
    defaultFormActions: props.defaultFormActions,
    onSave: save,
  });

  function setValue(
    path: string[],
    value: FormValue,
    kind: FormChangeKind = 'discrete'
  ): void {
    renderer.value?.setValue(path, value, kind);
  }

  function onChange(change: FormChange, values: FormPayload['values']): void {
    emit('change', change, values);
  }

  defineExpose({save, setValue});

  async function refresh(
    values: FormPayload['values'],
    scope: string[] = []
  ): Promise<FormPayload> {
    const {data} = await actionClient.post(props.refreshUrl!, {values, scope});

    if (!data.form) {
      throw new Error('The refresh endpoint did not return a Form payload.');
    }

    return data.form;
  }

  function normalize(value: FormValue): string {
    return (
      JSON.stringify(Array.isArray(value) ? [...value].sort() : value) ?? ''
    );
  }
</script>

<template>
  <form @submit.prevent="save()">
    <craft-pane appearance="raised" padding="none">
      <div class="py-2">
        <craft-field-group class="py-4">
          <FormRenderer
            ref="renderer"
            :payload="form"
            :refresh="refreshUrl ? refresh : undefined"
            :errors="errors"
            @update:mutation="onMutation"
            @change="onChange"
          >
            <template
              v-for="(_, slotName) in $slots"
              :key="slotName"
              #[slotName]="slotProps"
            >
              <slot :name="slotName" v-bind="slotProps" />
            </template>
          </FormRenderer>
        </craft-field-group>
      </div>
    </craft-pane>
  </form>
</template>
