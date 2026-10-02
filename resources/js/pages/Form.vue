<script setup lang="ts">
  import {actionClient, getActionUrl, t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {useForm} from '@inertiajs/vue3';
  import {computed, shallowRef, toRaw} from 'vue';
  import type {
    ActionItem,
    FormAction,
    FormSubmissionAction,
  } from '@/common/types';
  import {
    useAppLayout,
    type UseAppLayoutOptions,
  } from '@/common/composables/useAppLayout';
  import MetadataDetails from '@/common/components/MetadataDetails.vue';
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
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    elevatedFields?: string[] | '*';
    refreshUrl?: string;
    formActions?: FormAction[];
    defaultFormActions?: UseAppLayoutOptions['defaultFormActions'];
    /** Server-rendered markup for the details column. */
    metadataHtml?: string;
    /** Controls for the details column, submitted alongside `form`. */
    sidebarForm?: FormPayload;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange, values: FormPayload['values']): void;
  }>();
  const inertiaForm = useForm({});
  const {advanceBaseline, errors, onMutation, renderer} =
    useInertiaFormRenderer(inertiaForm, () => props.form);
  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarMutation,
    renderer: sidebarRenderer,
  } = useInertiaFormRenderer(inertiaForm, () => props.sidebarForm ?? null);
  const elevatedBaseline = shallowRef(structuredClone(currentValues()));
  const elevatedFields = props.elevatedFields;

  const {save} = useSettingsSave(inertiaForm, () => props.submit, {
    onSaveShortcut: (event) => {
      const action = submissionActions.value.find(
        (item) =>
          item.shortcut &&
          !!item.shift === event.shiftKey &&
          !item.hidden &&
          !item.disabled
      );

      if (action) {
        submitAction(action);
      } else if (!event.shiftKey) {
        save({redirect: false});
      }
    },
    transform: currentValues,
    onSuccess: () => {
      elevatedBaseline.value = structuredClone(currentValues());
      advanceBaseline();
      advanceSidebarBaseline();
    },
    passwordConfirmation: elevatedFields
      ? {
          required: () => {
            const values = currentValues();
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

  function isSubmissionAction(
    action: FormAction
  ): action is FormSubmissionAction {
    return (
      (!action.type || action.type === 'button') &&
      !('href' in action) &&
      (!('shortcut' in action) ||
        typeof action.shortcut === 'boolean' ||
        action.shortcut == null) &&
      !('onClick' in action && action.onClick) &&
      (!('action' in action) ||
        !action.action ||
        typeof action.action === 'string')
    );
  }

  const submissionActions = computed(() =>
    (props.formActions ?? []).filter(isSubmissionAction)
  );
  const translatedFormActions = computed<ActionItem[]>(() =>
    (props.formActions ?? []).map((action) => {
      if (!isSubmissionAction(action)) {
        return action;
      }

      return {
        ...action,
        action: undefined,
        variant: action.variant ?? (action.destructive ? 'danger' : undefined),
        shortcut: action.shortcut
          ? {key: 'S', shift: !!action.shift}
          : undefined,
        onClick: () => submitAction(action),
      };
    })
  );

  function submitAction(action: FormSubmissionAction): void {
    if (
      inertiaForm.processing ||
      action.hidden ||
      action.disabled ||
      (action.confirm && !window.confirm(action.confirm))
    ) {
      return;
    }

    const url = action.action;
    save({
      action: url
        ? {
            url: getActionUrl(url),
            method: 'post',
          }
        : undefined,
      data: {
        ...action.params,
        ...(action.redirect ? {redirect: action.redirect} : {}),
      },
      preserveScroll: action.retainScroll ?? false,
      keepOpen:
        !action.shift &&
        !action.action &&
        (!!action.shortcut || action.label === t('Save and continue editing')),
    });
  }

  useAppLayout(() => ({
    form: inertiaForm,
    defaultFormActions: submissionActions.value.some(
      (action) =>
        !action.hidden &&
        ((action.shortcut && !action.shift) ||
          action.label === t('Save and continue editing'))
    )
      ? []
      : props.defaultFormActions,
    formActions: translatedFormActions.value,
    contentMaxWidth: true,
    onSave: save,
  }));

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

  function currentValues(): FormPayload['values'] {
    return {
      ...toRaw(renderer.value?.currentValues() ?? props.form.values),
      ...toRaw(
        sidebarRenderer.value?.currentValues() ?? props.sidebarForm?.values
      ),
    };
  }

  function normalize(value: FormValue): string {
    return (
      JSON.stringify(Array.isArray(value) ? [...value].sort() : value) ?? ''
    );
  }
</script>

<template>
  <form @submit.prevent="save()">
    <CpContainer>
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
    </CpContainer>
    <MetadataDetails :html="metadataHtml">
      <template v-if="sidebarForm" #default>
        <craft-field-group>
          <FormRenderer
            ref="sidebarRenderer"
            :payload="sidebarForm"
            :errors="sidebarErrors"
            @update:mutation="onSidebarMutation"
          />
        </craft-field-group>
      </template>
    </MetadataDetails>
  </form>
</template>
