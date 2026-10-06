<script setup lang="ts">
  /**
   * The row below the content: whatever the page puts in `content-footer`
   * (pagination, meta info), then the form's save controls.
   */
  import {computed} from 'vue';
  import type {InertiaForm} from '@inertiajs/vue3';
  import CpContainer from '@/common/components/CpContainer.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import type {FormSaveOptions} from '@/common/types';
  import {formActionItems as buildFormActionItems} from '../formActionItems';
  import type {DefaultFormAction, ScreenProps, ScreenSlots} from '../types';

  const props = withDefaults(
    defineProps<
      Pick<
        ScreenProps,
        | 'formActions'
        | 'formAdditionalActions'
        | 'formAdditionalButtons'
        | 'saveDisabled'
        | 'submitButtonLabel'
      > & {
        readOnly: boolean;
        form: InertiaForm<any> | null;
        defaultFormActions: Array<DefaultFormAction>;
        /**
         * Keeps the row within the content's column, for a constrained content
         * view: the footer's rule still spans the pane, but the save controls
         * line up with the content above them.
         */
        contained?: boolean;
      }
    >(),
    {formAdditionalButtons: () => [], contained: false}
  );

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  defineSlots<Pick<ScreenSlots, 'content-footer' | 'additional-buttons'>>();

  const formActionItems = computed(() =>
    buildFormActionItems(
      props.defaultFormActions,
      props.formActions,
      (options) => emit('save', options)
    )
  );
</script>

<template>
  <div
    :class="{
      'content-footer': true,
      'content-footer--contained': contained,
    }"
  >
    <div class="flex gap-2 items-center justify-between">
      <FormActions
        v-if="form"
        :form="form"
        :action-items="formActionItems"
        :additional-actions="formAdditionalActions"
        :additional-buttons="formAdditionalButtons"
        :submit-label="submitButtonLabel"
        :read-only="readOnly"
        :save-disabled="saveDisabled"
      />

      <LayoutSlotOutlet name="additional-buttons">
        <slot name="additional-buttons"></slot>
      </LayoutSlotOutlet>
    </div>

    <div>
      <LayoutSlotOutlet name="content-footer">
        <slot name="content-footer"></slot>
      </LayoutSlotOutlet>
    </div>
  </div>
</template>

<style scoped>
  /*
   * The footer pads itself by the container padding, so the row's share of the
   * content's max width is that width less the padding on both sides.
   */
  .content-footer--contained {
    inline-size: 100%;
    max-inline-size: calc(
      var(--cp-content-max-width) - var(--cp-container-padding) * 2
    );
    margin-inline: auto;
  }
</style>
