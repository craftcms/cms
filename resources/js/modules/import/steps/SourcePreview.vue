<script setup lang="ts">
  /**
   * A step's data source as it reads: its first few rows, under the source field, so the
   * source can be checked before anything's mapped to it.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import {computed, onBeforeUnmount, ref, shallowRef, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {fetchSourcePreview, type SourcePreview} from './step-settings';

  /** More columns than this are left out, so the preview stays a glance. */
  const MAX_COLUMNS = 8;

  const props = defineProps<{
    step: ImportStep;
  }>();

  const emit = defineEmits<{
    /** Whether the source could be read. */
    'update:readable': [readable: boolean];
  }>();

  const preview = shallowRef<SourcePreview | null>(null);
  const loading = ref(false);
  const showLoading = useDelayedLoading(loading);
  let latest = 0;
  let timer: ReturnType<typeof setTimeout> | undefined;

  const columns = computed(
    () => preview.value?.columns.slice(0, MAX_COLUMNS) ?? []
  );

  async function load(): Promise<void> {
    const request = ++latest;

    try {
      const next = await fetchSourcePreview(props.step);

      if (request === latest) {
        preview.value = next;
        emit('update:readable', !next.error);
      }
    } catch {
      if (request === latest) {
        preview.value = null;
        emit('update:readable', false);
      }
    } finally {
      if (request === latest) {
        loading.value = false;
      }
    }
  }

  // the source is typed a character at a time, so it's read once typing pauses
  watch(
    () => [props.step.type, props.step.source] as const,
    ([type, source]) => {
      clearTimeout(timer);
      latest++;

      if (!type || !source) {
        preview.value = null;
        loading.value = false;
        emit('update:readable', false);

        return;
      }

      loading.value = true;
      timer = setTimeout(() => void load(), 400);
    },
    {immediate: true}
  );

  onBeforeUnmount(() => clearTimeout(timer));

  // kept out of the template, whose interpolation would end at the message's `}}`
  const hiddenColumns = computed(() => {
    const hidden = (preview.value?.columns.length ?? 0) - columns.value.length;

    return hidden > 0
      ? t(
          '{count, plural, =1{# more column isn’t shown} other{# more columns aren’t shown}}.',
          {count: hidden}
        )
      : '';
  });

  const summary = computed(() => {
    const value = preview.value;

    if (!value) {
      return '';
    }

    return t(
      '{total, plural, =1{# row} other{# rows}}, {columns, plural, =1{# column} other{# columns}}. {shown, plural, =1{Here’s the first} other{Here are the first #}}.',
      {
        total: value.total,
        columns: value.columns.length,
        shown: value.rows.length,
      }
    );
  });
</script>

<template>
  <div class="source-preview" :aria-busy="loading || undefined">
    <craft-spinner v-if="showLoading && !preview" role="status">
      {{ t('Reading the data source') }}
    </craft-spinner>
    <template v-else-if="preview && !preview.error">
      <p class="source-preview__summary m-0">{{ summary }}</p>
      <div v-if="preview.rows.length" class="source-preview__table">
        <table class="data fullwidth">
          <caption class="sr-only">
            {{
              t('The data source’s first rows')
            }}
          </caption>
          <thead>
            <tr>
              <th v-for="column in columns" :key="column" scope="col">
                <code>{{ column }}</code>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in preview.rows" :key="index">
              <td v-for="column in columns" :key="column">
                {{ row[column] ?? '' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="hiddenColumns" class="source-preview__summary m-0">
        {{ hiddenColumns }}
      </p>
    </template>
  </div>
</template>

<style scoped>
  .source-preview {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .source-preview__summary {
    color: var(--c-text-quiet);
  }

  .source-preview__table {
    overflow-x: auto;
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
  }

  .source-preview__table table {
    border-collapse: collapse;
    inline-size: 100%;
  }

  .source-preview__table th,
  .source-preview__table td {
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-sm);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    text-align: start;
    white-space: nowrap;
  }

  .source-preview__table tbody tr:last-child td {
    border-block-end: 0;
  }

  .source-preview__table td {
    max-inline-size: 24ch;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>
