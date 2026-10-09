<script setup lang="ts">
  /**
   * The row below the content: whatever the page puts in `content-footer`
   * (pagination, meta info), then the form's save controls.
   */
  import {computed} from 'vue';
  import type {InertiaForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui/utilities/translate';
  import CpContainer from '@/common/components/CpContainer.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import PrimaryActionButton from '@/common/components/PrimaryActionButton.vue';
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
        | 'fullPageForm'
      > & {
        readOnly: boolean;
        form: InertiaForm<any> | null;
        defaultFormActions: Array<DefaultFormAction>;
        /** The response's `primaryAction()` button, rendered server-side. */
        primaryActionHtml?: string | null;
        /** Shows the plain submit button's spinner, for shells that submit natively. */
        submitting?: boolean;
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

  defineSlots<
    Pick<
      ScreenSlots,
      'content-footer' | 'footer-meta' | 'additional-buttons' | 'primary-action'
    > & {
      /** The shell's own buttons at the end of the row, e.g. a slideout's Cancel. */
      'shell-actions'?: () => any;
    }
  >();

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
    <div class="flex gap-2 items-center">
      <FormActions
        v-if="form"
        :form="form"
        :action-items="formActionItems"
        :additional-actions="formAdditionalActions"
        :additional-buttons="formAdditionalButtons"
        :submit-label="submitButtonLabel"
        :read-only="readOnly"
        :save-disabled="saveDisabled"
      >
        <template #primary-action>
          <LayoutSlotOutlet name="primary-action">
            <slot name="primary-action">
              <DynamicHtmlRenderer
                v-if="primaryActionHtml"
                :html="primaryActionHtml"
              />
              <PrimaryActionButton
                v-else
                :form="form!"
                :label="submitButtonLabel"
              />
            </slot>
          </LayoutSlotOutlet>
        </template>
      </FormActions>

      <!-- A bridged legacy screen has no Inertia form to drive `FormActions`.
           It posts natively instead, so it gets a plain submit button — the
           same contract as Craft 5's page form. `craft-button` extends
           `LionButtonSubmit`, so `type="submit"` submits the enclosing form. -->
      <LayoutSlotOutlet
        v-else-if="fullPageForm && !readOnly"
        name="primary-action"
      >
        <slot name="primary-action">
          <DynamicHtmlRenderer
            v-if="primaryActionHtml"
            :html="primaryActionHtml"
          />
          <craft-button
            v-else
            type="submit"
            variant="primary"
            :loading="submitting || undefined"
          >
            {{ submitButtonLabel || t('Save') }}
          </craft-button>
        </slot>
      </LayoutSlotOutlet>

      <LayoutSlotOutlet name="footer-meta">
        <slot name="footer-meta"></slot>
      </LayoutSlotOutlet>

      <div class="flex-1"></div>
      <div class="content-footer__actions">
        <LayoutSlotOutlet name="additional-buttons">
          <slot name="additional-buttons"></slot>
        </LayoutSlotOutlet>

        <slot name="shell-actions"></slot>
      </div>
    </div>

    <div>
      <LayoutSlotOutlet name="content-footer">
        <slot name="content-footer"></slot>
      </LayoutSlotOutlet>
    </div>
  </div>
</template>

<style scoped>
  .content-footer__actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

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
