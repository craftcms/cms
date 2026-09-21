<script setup lang="ts">
  /**
   * The row below the content: whatever the page puts in `content-footer`
   * (pagination, meta info), then the form's save controls.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed} from 'vue';
  import type {InertiaForm} from '@inertiajs/vue3';
  import CpContainer from '@/common/components/CpContainer.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import type {ActionItem, FormSaveOptions} from '@/common/types';
  import type {DefaultFormAction, ScreenProps, ScreenSlots} from '../types';
  import {useScreenRegions} from '../useScreenRegions';

  const props = withDefaults(
    defineProps<
      Pick<
        ScreenProps,
        | 'formActions'
        | 'formAdditionalActions'
        | 'formAdditionalButtons'
        | 'submitButtonLabel'
      > & {
        readOnly: boolean;
        form: InertiaForm<any> | null;
        defaultFormActions: Array<DefaultFormAction>;
        /**
         * Keeps the row, rule included, within the content's container, for a
         * centered column where a full-width rule would overshoot the content.
         */
        contained?: boolean;
      }
    >(),
    {formAdditionalButtons: () => [], contained: false}
  );

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const slots =
    defineSlots<
      Pick<
        ScreenSlots,
        'content-footer' | 'additional-buttons' | 'submit-button'
      >
    >();

  const regions = useScreenRegions(slots);

  // Hidden rather than removed, so the outlets stay in the DOM for page-side
  // content to teleport into.
  const visible = computed(
    () =>
      Boolean(props.form) ||
      regions.has('content-footer') ||
      regions.has('additional-buttons')
  );

  const formActionItems = computed(() => [
    ...props.defaultFormActions.map(defaultFormActionItem),
    ...(props.formActions ?? []),
  ]);

  function defaultFormActionItem(action: DefaultFormAction): ActionItem {
    if (action === 'saveAndContinueEditing') {
      return {
        label: t('Save and continue editing'),
        onClick: () => emit('save', {redirect: false}),
        shortcut: 'S',
      };
    }

    throw new Error(`Unknown default form action: ${action}`);
  }
</script>

<template>
  <CpContainer class="content-footer" v-show="visible">
    <div
      class="flex gap-2 items-center justify-between border-t border-t-quiet py-md"
    >
      <FormActions
        v-if="form"
        :form="form"
        :action-items="formActionItems"
        :additional-actions="formAdditionalActions"
        :additional-buttons="formAdditionalButtons"
        :submit-label="submitButtonLabel"
        :read-only="readOnly"
      >
        <template v-if="slots['submit-button']" #submit-button>
          <slot name="submit-button"></slot>
        </template>
      </FormActions>

      <LayoutSlotOutlet name="additional-buttons">
        <slot name="additional-buttons"></slot>
      </LayoutSlotOutlet>
    </div>

    <div>
      <LayoutSlotOutlet name="content-footer">
        <slot name="content-footer"></slot>
      </LayoutSlotOutlet>
    </div>
  </CpContainer>
</template>

<style scoped lang="css">
  .content-footer {
    min-height: var(--cp-footer-height);
  }
</style>
