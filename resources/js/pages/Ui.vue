<script setup lang="ts">
  import {actionClient, getActionUrl, t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {useForm, usePage} from '@inertiajs/vue3';
  import {computed, provide, shallowRef, toRaw, watch} from 'vue';
  import type {
    ActionItem,
    ActionItemButton,
    FormAction,
    FormSubmissionAction,
  } from '@/common/types';
  import {
    useAppLayout,
    type UseAppLayoutOptions,
  } from '@/common/composables/useAppLayout';
  import MetadataDetails from '@/common/components/MetadataDetails.vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {UiTableFlush} from '@/modules/ui/runtime';
  import type {
    UiChange,
    UiChangeKind,
    UiPayload,
    UiValue,
    UiValues,
  } from '@/modules/ui/types';
  import {useInertiaUiRenderer} from '@/modules/ui/useInertiaUiRenderer';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = withDefaults(
    defineProps<{
      ui: UiPayload;
      /** Omit for node-only screens with no form submission. */
      submit?: UrlMethodPair;
      elevatedFields?: string[] | '*';
      refreshUrl?: string;
      formActions?: FormAction[];
      defaultFormActions?: UseAppLayoutOptions['defaultFormActions'];
      /** Server-rendered markup for the details column. */
      metadataHtml?: string;
      /** Controls for the details column, submitted alongside `ui`. */
      sidebarUi?: UiPayload;
      /**
       * Caps and centers the content column. Unset, a page is centered unless
       * it's a lone admin table, which spans the pane. See
       * `ScreenProps.contentMaxWidth`.
       */
      contentMaxWidth?: boolean;
      formAdditionalButtons?: ActionItemButton[];
      /**
       * Leaves the form actions out of the footer, for a page that renders its
       * own Save button in the `sidebar-actions` slot, which receives them.
       */
      formActionsInSidebar?: boolean;
      /** See `ScreenProps.hideFooter`. */
      hideFooter?: boolean;
    }>(),
    // Without a default, Vue casts an absent boolean prop to `false`, which
    // would read as the page opting out.
    {contentMaxWidth: undefined}
  );
  const emit = defineEmits<{
    (event: 'change', change: UiChange, values: UiPayload['values']): void;
  }>();
  const page = usePage();
  const shownUi = shownPayload(() => props.ui);
  const shownSidebarUi = shownPayload(() => props.sidebarUi);
  const inertiaForm = useForm({});
  const {advanceBaseline, errors, onMutation, renderer} = useInertiaUiRenderer(
    inertiaForm,
    () => shownUi.value
  );
  const {
    advanceBaseline: advanceSidebarBaseline,
    errors: sidebarErrors,
    onMutation: onSidebarMutation,
    renderer: sidebarRenderer,
  } = useInertiaUiRenderer(inertiaForm, () => shownSidebarUi.value ?? null);
  const elevatedBaseline = shallowRef(structuredClone(currentValues()));

  const save = props.submit
    ? useSettingsSave(inertiaForm, () => props.submit!, {
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
            save?.({redirect: false});
          }
        },
        hasShiftSaveShortcut: () =>
          submissionActions.value.some(
            (item) =>
              item.shortcut && item.shift && !item.hidden && !item.disabled
          ),
        transform: currentValues,
        onSuccess: () => {
          elevatedBaseline.value = structuredClone(currentValues());
          advanceBaseline();
          advanceSidebarBaseline();
        },
        passwordConfirmation: {
          required: () => {
            const elevatedFields = props.elevatedFields;

            if (!elevatedFields) {
              return false;
            }

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
        },
      }).save
    : undefined;

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
      !save ||
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

  const isBareTable = computed(() => {
    const [node, ...rest] = shownUi.value.nodes;
    return (
      rest.length === 0 &&
      node?.component === 'craft:admin-table' &&
      // Unset means the page decides, which is flush for a lone table.
      (node.props.appearance ?? 'flush') === 'flush'
    );
  });
  const contentSlot = computed(() => (isBareTable.value ? 'full' : 'default'));
  provide(UiTableFlush, isBareTable);

  useAppLayout(() => ({
    form: props.submit ? inertiaForm : null,
    defaultFormActions: props.formActionsInSidebar
      ? []
      : submissionActions.value.some(
            (action) =>
              !action.hidden &&
              ((action.shortcut && !action.shift) ||
                action.label === t('Save and continue editing'))
          )
        ? []
        : props.defaultFormActions,
    formActions: props.formActionsInSidebar ? [] : translatedFormActions.value,
    formAdditionalButtons: props.formAdditionalButtons,
    hideFooter: props.hideFooter,
    contentMaxWidth: props.contentMaxWidth ?? !isBareTable.value,
    onSave: save,
  }));

  function setValue(
    path: string[],
    value: UiValue,
    kind: UiChangeKind = 'discrete'
  ): void {
    renderer.value?.setValue(path, value, kind);
  }

  function onChange(change: UiChange, values: UiPayload['values']): void {
    emit('change', change, values);
  }

  defineExpose({save, setValue});

  async function refresh(
    values: UiPayload['values'],
    scope: string[] = []
  ): Promise<UiPayload> {
    const {data} = await actionClient.post(props.refreshUrl!, {values, scope});

    if (!data.ui) {
      throw new Error('The refresh endpoint did not return a UI payload.');
    }

    return data.ui;
  }

  function currentValues(): UiPayload['values'] {
    return {
      ...toRaw(renderer.value?.currentValues() ?? shownUi.value.values),
      ...toRaw(
        sidebarRenderer.value?.currentValues() ?? shownSidebarUi.value?.values
      ),
    };
  }

  /**
   * The payload to render, which passes over the one a failed save sends
   * back. That page is built from the stored values, while the form keeps the
   * submitted ones, which the nodes on screen were refreshed for as they
   * changed.
   */
  function shownPayload<T extends UiPayload | undefined>(source: () => T) {
    const shown = shallowRef(source());

    watch(source, (payload) => {
      if (Object.keys(page.props.errors ?? {}).length === 0) {
        shown.value = payload;
      }
    });

    return shown;
  }

  function normalize(value: UiValue): string {
    return (
      JSON.stringify(Array.isArray(value) ? [...value].sort() : value) ?? ''
    );
  }
</script>

<template>
  <component :is="submit ? 'form' : 'div'" @submit.prevent="save?.()">
    <CpContainer>
      <template #[contentSlot]>
        <component
          :is="isBareTable ? 'div' : 'craft-field-group'"
          :class="isBareTable ? undefined : 'ui-page__fields py-lg'"
        >
          <UiRenderer
            ref="renderer"
            :payload="shownUi"
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
          </UiRenderer>
        </component>
      </template>
    </CpContainer>
    <MetadataDetails :html="metadataHtml">
      <template v-if="shownSidebarUi" #default>
        <craft-field-group>
          <UiRenderer
            ref="sidebarRenderer"
            :payload="shownSidebarUi"
            :errors="sidebarErrors"
            @update:mutation="onSidebarMutation"
          />
        </craft-field-group>
        <slot
          name="sidebar-actions"
          :form="inertiaForm"
          :form-action-items="translatedFormActions"
        />
      </template>
      <!-- Without sidebar controls, the actions follow the metadata instead. -->
      <template v-else-if="$slots['sidebar-actions']" #actions>
        <slot
          name="sidebar-actions"
          :form="inertiaForm"
          :form-action-items="translatedFormActions"
        />
      </template>
    </MetadataDetails>
  </component>
</template>

<style scoped>
  /* The tab strip spaces itself from the panels, so the group's gap would double it. */
  .ui-page__fields > :deep(.ui-node-list__tabs) {
    margin-block-end: calc(-1 * var(--gap, calc(var(--c-spacing) * 6)));
  }
</style>
