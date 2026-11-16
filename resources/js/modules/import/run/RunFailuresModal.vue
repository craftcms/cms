<script setup lang="ts">
  /**
   * Why a step's items couldn't be imported in its last run, item by item, with each
   * validation error on its own row next to where it is in the element.
   */
  import '@craftcms/ui/components/button/button';
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import Modal from '@/common/components/Modal.vue';
  import type {RunFailure} from './run-state';

  const {failures, failureCount} = defineProps<{
    isActive: boolean;
    /** What the step imports, e.g. “1. Entries — people.csv”. */
    title: string;
    failures: RunFailure[];
    /** How many items failed, including those past `failures`. */
    failureCount: number;
  }>();

  const emit = defineEmits<{
    close: [];
  }>();

  const summary = computed(() =>
    t(
      '{count, plural, =1 {# item} other {# items}} couldn’t be imported in the last run.',
      {count: failureCount}
    )
  );

  const more = computed(() =>
    t(
      '{count, plural, =1 {# more item} other {# more items}} couldn’t be imported. The import log has them all.',
      {count: failureCount - failures.length}
    )
  );
</script>

<template>
  <Modal
    :is-active="isActive"
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

      <div class="run-errors">
        <p class="m-0">{{ summary }}</p>

        <section
          v-for="failure in failures"
          :key="failure.item"
          class="run-errors__item"
        >
          <h3 class="run-errors__heading">
            {{ t('Item {number, number}', {number: failure.item}) }}
            <span v-if="failure.elementId" class="run-errors__element">
              {{ t('Element {id}', {id: failure.elementId}) }}
            </span>
          </h3>

          <table v-if="failure.problems.length" class="run-errors__problems">
            <thead class="sr-only">
              <tr>
                <th scope="col">{{ t('Where') }}</th>
                <th scope="col">{{ t('Problem') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(problem, i) in failure.problems" :key="i">
                <td class="run-errors__path">
                  <template v-if="problem.path.length">{{
                    problem.path.join(' › ')
                  }}</template>
                  <span v-else class="sr-only">{{ t('The element') }}</span>
                </td>
                <td>{{ problem.message }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="m-0">{{ failure.message }}</p>
        </section>

        <p v-if="failureCount > failures.length" class="m-0 text-quiet">
          {{ more }}
        </p>
      </div>
    </craft-pane>
  </Modal>
</template>

<style scoped>
  .run-errors {
    display: grid;
    gap: var(--c-spacing-lg);
  }

  .run-errors__item {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .run-errors__heading {
    display: flex;
    align-items: baseline;
    gap: var(--c-spacing-sm);
    margin: 0;
    font-size: var(--c-text-base);
  }

  .run-errors__element {
    color: var(--c-text-quiet);
    font-weight: normal;
    font-size: var(--c-text-sm);
  }

  .run-errors__problems {
    inline-size: 100%;
    border-collapse: collapse;
  }

  .run-errors__problems td {
    padding-block: var(--c-spacing-sm);
    padding-inline: 0 var(--c-spacing-lg);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    vertical-align: top;
  }

  .run-errors__problems tr:last-child td {
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  /* where it is reads as a label, so the problems line up beside it */
  .run-errors__path {
    inline-size: 40%;
    color: var(--c-text-quiet);
    overflow-wrap: anywhere;
  }
</style>
