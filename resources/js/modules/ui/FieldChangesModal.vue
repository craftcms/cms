<script setup lang="ts">
  /**
   * Shows how a field’s value on a draft, revision, or nested element differs
   * from the value it was based on: a word diff for text, and both values for
   * anything else, such as relations.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import {actionClient, isHttpError, t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import CompareFieldChangesController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CompareFieldChangesController';
  import Modal from '@/common/components/Modal.vue';
  import ChangeComparison from './ChangeComparison.vue';
  import type {UiPayload} from './types';

  /** The `craft:compare-field-changes` field action’s detail. */
  export interface CompareFieldChangesDetail {
    trigger?: unknown;
    elementType: string;
    elementId: number;
    siteId: number;
    ownerId: number | null;
    fieldId: number | null;
    layoutElementUid: string;
    label?: string | null;
  }

  interface FieldChangeComparison {
    fromLabel: string;
    toLabel: string;
    hasBase: boolean;
    unchanged: boolean;
    diff: {current: string; proposed: string} | null;
    preview: {current: UiPayload | null; proposed: UiPayload} | null;
  }

  const props = defineProps<{
    detail?: CompareFieldChangesDetail;
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
  }>();

  const comparison = shallowRef<FieldChangeComparison>();
  const error = shallowRef<string>();
  let request = 0;

  /** Whether both versions are previewed side by side, split by a hairline. */
  const ruled = computed(() =>
    Boolean(
      !error.value &&
      comparison.value?.preview?.current &&
      !comparison.value.diff &&
      !comparison.value.unchanged
    )
  );

  const title = computed(() =>
    props.detail?.label
      ? t('Compare “{name}”', {name: props.detail.label})
      : t('Compare changes')
  );

  /**
   * Announced when the comparison loads. The dialog makes the page behind it
   * inert, so this has its own live region rather than the page’s announcer.
   */
  const status = computed(() => {
    if (!comparison.value) {
      return '';
    }

    return comparison.value.unchanged
      ? t('{from} and {to} have the same value.', {
          from: comparison.value.fromLabel,
          to: comparison.value.toLabel,
        })
      : t('Changes loaded.');
  });

  watch(
    () => props.detail,
    async (detail) => {
      const current = ++request;
      comparison.value = undefined;
      error.value = undefined;

      if (!detail) {
        return;
      }

      try {
        const {data} = await actionClient.post<FieldChangeComparison>(
          CompareFieldChangesController.url(),
          {
            elementType: detail.elementType,
            elementId: detail.elementId,
            siteId: detail.siteId,
            ownerId: detail.ownerId,
            fieldId: detail.fieldId,
            layoutElementUid: detail.layoutElementUid,
          }
        );

        if (current === request) {
          comparison.value = data;
        }
      } catch (e) {
        if (current === request) {
          error.value =
            (isHttpError<{message?: string}>(e)
              ? e.response?.data?.message
              : undefined) ?? t('Couldn’t load the changes.');
        }
      }
    }
  );
</script>

<template>
  <Modal
    :is-active="Boolean(detail)"
    :label="title"
    width="2xl"
    @close="emit('close')"
  >
    <craft-pane :label="title" :class="{'field-changes--ruled': ruled}">
      <craft-button
        slot="header-actions"
        type="button"
        icon="xmark-large"
        :aria-label="t('Close')"
        variant="plain"
        size="small"
        flush="inline-end"
        @click="emit('close')"
      ></craft-button>

      <div class="field-changes" data-field-changes-modal>
        <p class="sr-only" role="status">{{ status }}</p>
        <p v-if="error" class="field-changes__message" role="alert">
          {{ error }}
        </p>
        <div v-else-if="!comparison" class="field-changes__loading">
          <craft-spinner visible :label="t('Loading')"></craft-spinner>
        </div>
        <template v-else>
          <div v-if="ruled" class="field-changes__heads" aria-hidden="true">
            <span>{{ comparison.fromLabel }}</span>
            <span>{{ comparison.toLabel }}</span>
          </div>
          <p v-else-if="comparison.hasBase" class="field-changes__versions">
            <span class="field-changes__from" aria-hidden="true">{{
              comparison.fromLabel
            }}</span>
            <span aria-hidden="true">→</span>
            <span class="field-changes__to" aria-hidden="true">{{
              comparison.toLabel
            }}</span>
            <span class="sr-only">{{
              t('{from} compared with {to}', {
                from: comparison.fromLabel,
                to: comparison.toLabel,
              })
            }}</span>
          </p>
          <p v-if="comparison.unchanged" class="field-changes__message">
            {{
              t('{from} and {to} have the same value.', {
                from: comparison.fromLabel,
                to: comparison.toLabel,
              })
            }}
          </p>
          <template v-else>
            <p v-if="!comparison.hasBase" class="field-changes__message">
              {{ comparison.fromLabel }}
            </p>
            <ChangeComparison
              :diff="comparison.diff"
              :from="
                comparison.preview?.current && {
                  payload: comparison.preview.current,
                }
              "
              :to="comparison.preview && {payload: comparison.preview.proposed}"
              :from-label="comparison.fromLabel"
              :to-label="comparison.toLabel"
              :label="detail?.label ?? undefined"
              collapse
              :ruled="ruled"
            />
          </template>
        </template>
      </div>
    </craft-pane>
  </Modal>
</template>

<style scoped>
  .field-changes {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    overflow-wrap: anywhere;
  }

  /* Matches the Stack Trace modal's loading height. */
  .field-changes__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    block-size: 20rem;
  }

  .field-changes__versions,
  .field-changes__message {
    margin: 0;
  }

  .field-changes__versions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs);
    align-items: baseline;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  .field-changes__from {
    text-decoration: line-through;
  }

  .field-changes__to {
    text-decoration: underline;
  }

  .field-changes__heads {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: calc(var(--cp-container-padding) * 2 + 1px);
    margin-block-start: calc(var(--c-spacing-lg) * -1);
    margin-inline: calc(var(--c-spacing-lg) * -1);
    box-sizing: content-box;
    min-block-size: var(--c-size-control-sm);
    align-items: center;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-lg);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    background: var(--c-surface-raised);
    font-weight: 600;
  }

  .field-changes--ruled::part(body) {
    background: linear-gradient(
        var(--c-color-neutral-border-quiet),
        var(--c-color-neutral-border-quiet)
      )
      center / 1px 100% no-repeat;
  }
</style>
