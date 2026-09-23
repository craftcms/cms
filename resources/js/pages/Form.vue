<script setup lang="ts">
  import {actionClient} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {useForm} from '@inertiajs/vue3';
  import {computed, shallowRef, toRaw} from 'vue';
  import {
    useAppLayout,
    type UseAppLayoutOptions,
  } from '@/common/composables/useAppLayout';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {ActionItem, FormAltAction} from '@/common/types';
  import type {
    FormChange,
    FormChangeKind,
    FormPayload,
    FormValue,
    FormValues,
  } from '@/modules/forms/types';
  import {useInertiaFormRenderer} from '@/modules/forms/useInertiaFormRenderer';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = defineProps<{
    form: FormPayload;
    /** Omit for node-only screens with no form submission. */
    submit?: UrlMethodPair;
    elevatedFields?: string[] | '*';
    refreshUrl?: string;
    defaultFormActions?: UseAppLayoutOptions['defaultFormActions'];
    formActions?: FormAltAction[];
    /** Server-rendered markup for the details column. */
    metadataHtml?: string;
    contentMaxWidth?: boolean;
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

  const translatedFormActions = computed<ActionItem[]>(
    () =>
      props.formActions?.map((altAction) => ({
        label: altAction.label,
        variant: altAction.destructive ? 'danger' : undefined,
        onClick: () => {
          if (altAction.confirm && !window.confirm(altAction.confirm)) {
            return;
          }

          save?.({
            action: altAction.action
              ? {url: altAction.action, method: 'post'}
              : undefined,
            data: altAction.params,
          });
        },
      })) ?? []
  );

  const isBareTable = computed(() => {
    const [node, ...rest] = props.form.nodes;
    return (
      rest.length === 0 &&
      node?.component === 'craft:admin-table' &&
      node.props.bordered === false
    );
  });

  // `PageScreen` shows the Save button purely on `form` being truthy (`v-if="form"`) —
  // it doesn't look at `onSave`/`submit`. Passing `inertiaForm` unconditionally would
  // show a Save button with nothing to save on a node-only screen (a listing, say).
  useAppLayout({
    form: props.submit ? inertiaForm : null,
    defaultFormActions: props.defaultFormActions,
    formActions: translatedFormActions.value,
    contentMaxWidth: props.contentMaxWidth ?? true,
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
  <component :is="submit ? 'form' : 'div'" @submit.prevent="save?.()">
    <CpContainer>
      <component
        :is="isBareTable ? 'div' : 'craft-pane'"
        v-bind="isBareTable ? {} : {appearance: 'raised'}"
      >
        <component
          :is="isBareTable ? 'div' : 'craft-field-group'"
          v-bind="isBareTable ? {} : {class: 'py-4'}"
        >
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
        </component>
      </component>
    </CpContainer>
  </component>
  <LayoutSlot v-if="metadataHtml" name="details">
    <DynamicHtmlRenderer :html="metadataHtml" />
  </LayoutSlot>
</template>
