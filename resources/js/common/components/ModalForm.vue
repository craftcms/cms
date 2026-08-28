<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import Modal, {type ModalProps} from '@/common/components/Modal.vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
  }>();
  withDefaults(
    defineProps<
      ModalProps & {
        loading?: boolean;
        title?: string;
        resetLabel?: string;
        submitLabel?: string;
      }
    >(),
    {
      title: undefined,
      overlay: true,
      loading: false,
      resetLabel: t('Cancel'),
      submitLabel: t('Save'),
    }
  );

  function submitHandler() {
    emit('submit');
  }
</script>

<template>
  <Modal
    :is-active="isActive"
    :overlay="overlay"
    @close="emit('close')"
    :width="width"
    :height="height"
    :max-height="maxHeight"
  >
    <form @submit.prevent="submitHandler">
      <!--
        `craft-pane` uses native slots, which can't be filled by a `v-for` over
        `$slots` the way Vue slots could — a native `slot=` assignment has to
        sit on a real element that is a direct child of the pane. So each pane
        region is forwarded explicitly, through a `display: contents` wrapper
        that keeps the pane's header/footer flex layout intact. The wrappers
        are only rendered when the parent actually fills the slot, since the
        pane decides whether to render its header/footer from the presence of
        slotted children.
      -->
      <craft-pane :label="title">
        <div v-if="$slots.header" slot="header" class="cp:contents">
          <slot name="header"></slot>
        </div>
        <div v-if="$slots.title" slot="title" class="cp:contents">
          <slot name="title"></slot>
        </div>
        <div
          v-if="$slots['header-actions']"
          slot="header-actions"
          class="cp:contents"
        >
          <slot name="header-actions"></slot>
        </div>
        <div v-if="$slots.body" slot="body" class="cp:contents">
          <slot name="body"></slot>
        </div>

        <slot></slot>

        <div v-if="$slots.footer" slot="footer" class="cp:contents">
          <slot name="footer"></slot>
        </div>
        <div
          v-if="$slots['footer-content']"
          slot="footer-content"
          class="cp:contents"
        >
          <slot name="footer-content"></slot>
        </div>
        <div v-if="$slots.feedback" slot="feedback" class="cp:contents">
          <slot name="feedback"></slot>
        </div>
        <div v-if="$slots.actions" slot="actions" class="cp:contents">
          <slot name="actions"></slot>
        </div>

        <div
          v-if="$slots['secondary-action']"
          slot="secondary-action"
          class="cp:contents"
        >
          <slot name="secondary-action"></slot>
        </div>
        <craft-button
          v-else
          slot="secondary-action"
          type="reset"
          @click="emit('close')"
          variant="plain"
        >
          {{ resetLabel }}
        </craft-button>

        <div
          v-if="$slots['primary-action']"
          slot="primary-action"
          class="cp:contents"
        >
          <slot name="primary-action"></slot>
        </div>
        <craft-button
          v-else
          slot="primary-action"
          type="submit"
          variant="accent"
          :loading="loading"
        >
          {{ submitLabel }}
        </craft-button>
      </craft-pane>
    </form>
  </Modal>
</template>

<style scoped lang="scss"></style>
