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
    status: 'modified' | 'outdated';
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

  const title = computed(() =>
    props.detail?.label
      ? t('Compare “{name}”', {name: props.detail.label})
      : t('Compare changes')
  );

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
            status: detail.status,
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
    <craft-pane :label="title">
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
        <p v-if="error" class="field-changes__message" role="alert">
          {{ error }}
        </p>
        <craft-spinner
          v-else-if="!comparison"
          visible
          :label="t('Loading')"
        ></craft-spinner>
        <template v-else>
          <p v-if="comparison.hasBase" class="field-changes__versions">
            <del>{{ comparison.fromLabel }}</del>
            <span aria-hidden="true">→</span>
            <ins>{{ comparison.toLabel }}</ins>
          </p>
          <p
            v-if="detail?.status === 'outdated'"
            class="field-changes__message"
          >
            {{
              t(
                'The current entry changed this field after the draft was created, and the change was merged into the draft.'
              )
            }}
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
</style>
