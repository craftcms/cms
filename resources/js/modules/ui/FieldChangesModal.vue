<script setup lang="ts">
  /**
   * Shows how a field’s value on a draft, revision, or nested element differs
   * from the value it was based on: a word diff for text, and both values for
   * anything else, such as relations.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import '@craftcms/ui/components/text-diff/text-diff';
  import {actionClient, isHttpError, t} from '@craftcms/ui';
  import {computed, shallowRef, watch} from 'vue';
  import CompareFieldValuesController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CompareFieldValuesController';
  import Modal from '@/common/components/Modal.vue';
  import ChangeValue from './ChangeValue.vue';

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

  interface FieldValueComparison {
    from: {label: string; value: unknown};
    to: {label: string; value: unknown};
    hasBase: boolean;
    unchanged: boolean;
  }

  const props = defineProps<{
    detail?: CompareFieldChangesDetail;
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
  }>();

  const comparison = shallowRef<FieldValueComparison>();
  const error = shallowRef<string>();
  let request = 0;

  const title = computed(() =>
    props.detail?.label
      ? t('Compare “{name}”', {name: props.detail.label})
      : t('Compare changes')
  );

  /** Whether both values are text, which show as one word diff. */
  const isTextChange = computed(() => {
    const from = comparison.value?.from.value;
    const to = comparison.value?.to.value;

    return (
      typeof from === 'string' &&
      typeof to === 'string' &&
      from !== '' &&
      to !== ''
    );
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
        const {data} = await actionClient.post<FieldValueComparison>(
          CompareFieldValuesController.url(),
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
    width="lg"
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
            <del>{{ comparison.from.label }}</del>
            <span aria-hidden="true">→</span>
            <ins>{{ comparison.to.label }}</ins>
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
                from: comparison.from.label,
                to: comparison.to.label,
              })
            }}
          </p>
          <p v-else-if="!comparison.hasBase" class="field-changes__message">
            {{ comparison.from.label }}
          </p>

          <craft-text-diff
            v-if="!comparison.unchanged && isTextChange"
            :current="comparison.from.value as string"
            :proposed="comparison.to.value as string"
            :label="detail?.label ?? undefined"
            collapse
          ></craft-text-diff>
          <dl
            v-else-if="!comparison.unchanged"
            class="field-changes__comparison"
          >
            <div v-if="comparison.hasBase">
              <dt>{{ comparison.from.label }}</dt>
              <dd>
                <ChangeValue :value="comparison.from.value" />
              </dd>
            </div>
            <div>
              <dt>{{ comparison.to.label }}</dt>
              <dd>
                <ChangeValue :value="comparison.to.value" />
              </dd>
            </div>
          </dl>
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

  .field-changes__comparison {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
    gap: var(--c-spacing-md);
    margin: 0;
  }

  .field-changes__comparison div {
    min-width: 0;
  }

  .field-changes__comparison dt {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
    font-weight: 600;
  }

  .field-changes__comparison dd {
    margin-block: var(--c-spacing-xs) 0;
    margin-inline: 0;
  }
</style>
