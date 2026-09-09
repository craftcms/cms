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
    /**
     * Omit for a screen with nothing to save as a whole — a listing, say,
     * that may still hold ordinary Field controls (row-selection checkboxes
     * and the like) driven by the same value tracking below. Those just need
     * their own action button reading `currentValues()`/`setValue()` off
     * this component's exposed API, rather than a single generic save. When
     * omitted, no `<form>` element is rendered at all — see the template.
     */
    submit?: UrlMethodPair;
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

  // Only wire up a save flow (and its cmd/ctrl + s shortcut) when there's
  // somewhere to submit to — otherwise there's nothing for `save()` to post.
  const save = props.submit
    ? useSettingsSave(inertiaForm, () => props.submit!, {
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
                const values =
                  renderer.value?.currentValues() ?? props.form.values;
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
      }).save
    : undefined;

  // `PageScreen` shows the Save button purely on `form` being truthy (`v-if="form"`) —
  // it doesn't look at `onSave`/`submit`. Passing `inertiaForm` unconditionally would
  // show a Save button with nothing to save on a node-only screen (a listing, say).
  useAppLayout({
    form: props.submit ? inertiaForm : null,
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
  <!--
    A node-only screen (no `submit`) renders as a plain `<div>` — there's
    nothing to submit, so there's no reason to imply otherwise with a `<form>`
    element. Value tracking below is all Vue-side state; it doesn't depend on
    an actual `<form>` tag existing in the DOM either way.
  -->
  <component :is="submit ? 'form' : 'div'" @submit.prevent="save?.()">
    <craft-pane appearance="raised">
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
    </craft-pane>
  </component>
</template>
