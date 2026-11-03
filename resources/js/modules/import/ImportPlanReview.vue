<script setup lang="ts">
  /**
   * What running the plan would do with the first rows of each enabled step's data. It's
   * worked out from the steps as they stand in the form, so it follows unsaved changes.
   */
  import '@craftcms/ui/components/spinner/spinner';
  import {computed, ref, shallowRef, watch} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {reviewSteps} from '@actions/Import/ImportPlansController';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {dirtyState} from '@/modules/import/mapping/paths';
  import ImportReviewStep from './ImportReviewStep.vue';
  import ImportStepReview from './ImportStepReview.vue';
  import type {ReviewStep} from './review';

  const props = defineProps<{
    steps: ImportStep[];
    planUid: string | null;
  }>();

  const reviewed = shallowRef<ReviewStep[] | null>(null);
  const loading = ref(false);
  const showLoading = useDelayedLoading(loading);
  const loadError = ref<string | null>(null);
  let latest = 0;

  const enabledSteps = computed(() =>
    props.steps.filter((step) => step.enabled)
  );

  async function load(): Promise<void> {
    const request = ++latest;
    loading.value = true;
    loadError.value = null;

    try {
      const {data} = await actionClient.post(reviewSteps().url, {
        uid: props.planUid,
        steps: props.steps,
      });

      if (request === latest) {
        reviewed.value = data.steps as ReviewStep[];
      }
    } catch (error) {
      if (request === latest) {
        loadError.value =
          error instanceof Error && error.message
            ? error.message
            : t('Couldn’t review this import plan.');
      }
    } finally {
      if (request === latest) {
        loading.value = false;
      }
    }
  }

  // the steps change a whole card at a time, so each change is worth a fresh review
  watch(
    () => dirtyState(enabledSteps.value),
    () => {
      if (enabledSteps.value.length) {
        void load();
      } else {
        latest++;
        reviewed.value = [];
        loading.value = false;
      }
    },
    {immediate: true}
  );
</script>

<template>
  <div class="import-plan-review" :aria-busy="loading || undefined">
    <craft-spinner v-if="showLoading && !reviewed" role="status">
      {{ t('Loading') }}
    </craft-spinner>
    <p v-if="loadError" class="error m-0" role="alert">{{ loadError }}</p>
    <p v-else-if="!enabledSteps.length" class="import-plan-review__quiet m-0">
      {{ t('This import plan has no enabled steps.') }}
    </p>
    <template v-else-if="reviewed">
      <ImportReviewStep
        v-for="step in reviewed"
        :key="step.uid ?? step.number"
        :step="step"
        :class="{'import-plan-review__stale': loading}"
      >
        <ImportStepReview
          :review="step.preview"
          :label="t('Step {number} review', {number: step.number})"
        />
      </ImportReviewStep>
    </template>
  </div>
</template>

<style scoped>
  .import-plan-review {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xl);
  }

  .import-plan-review__quiet {
    color: var(--c-text-quiet);
  }

  .import-plan-review__stale {
    opacity: 0.5;
  }
</style>
