<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {type JobInfo, JobStatus} from '@/modules/queue/types';
  import {jobStatusFill} from '@/modules/queue/status-fill';
  import CodeHighlight from '@/common/components/CodeHighlight.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import RetryJobButton from '@/modules/utilities/components/queue-manager/RetryJobButton.vue';
  import ReleaseJobButton from '@/modules/utilities/components/queue-manager/ReleaseJobButton.vue';
  import {show} from '@routes/cp/utilities';
  import {computed} from 'vue';

  const props = defineProps<{
    job: JobInfo;
  }>();

  const isRetryable = computed(
    () =>
      props.job.status.value === JobStatus.Reserved ||
      props.job.status.value === JobStatus.Failed
  );

  /** A released job is gone, so its page goes with it. */
  function released(): void {
    router.visit(show.url({id: 'queue-manager'}));
  }

  const hiddenProperties = ['delay', 'description', 'progressLabel', 'job'];

  const jobRecord = computed(() =>
    Object.fromEntries(
      Object.entries(props.job).filter(
        ([name, value]) => value !== null && !hiddenProperties.includes(name)
      )
    )
  );

  /**
   * Formats a TTR value.
   */
  function ttrValue(value: string) {
    return t('{num, number} {num, plural, =1{second} other{seconds}}', {
      num: value,
    });
  }

  /**
   * Returns a job attribute name.
   */
  function jobAttributeName(name: string) {
    switch (name) {
      case 'uid':
        return t('UID');
      case 'class':
        return t('Class');
      case 'status':
        return t('Status');
      case 'progress':
        return t('Progress');
      case 'description':
        return t('Description');
      case 'label':
        return t('Label');
      case 'dateCompleted':
        return t('Completed');
      case 'dateFailed':
        return t('Failed');
      case 'dateCreated':
        return t('Created');
      case 'dateUpdated':
        return t('Updated');
      case 'ttr':
        return t('Time to reserve');
      case 'error':
        return t('Error');
      case 'delay':
        return t('Delay');
      default:
        return name;
    }
  }
</script>

<template>
  <div>
    <LayoutSlot name="additional-buttons">
      <RetryJobButton v-if="isRetryable" :job="job" size="default" />
      <ReleaseJobButton
        v-if="job.status.value !== JobStatus.Done"
        :job="job"
        size="default"
        @done="released"
      />
    </LayoutSlot>

    <table class="table-fixed border-collapse w-full">
      <tbody>
        <tr v-for="(value, name) in jobRecord" :key="name">
          <th
            :class="{
              'text-start': true,
              'py-2': true,
              'text-red-600': name === 'error',
            }"
          >
            {{ jobAttributeName(name) }}
          </th>
          <td
            :class="{
              'py-2': true,
              'text-red-600': name === 'error',
            }"
          >
            <template v-if="name == 'status'">
              <craft-badge :fill="jobStatusFill(job.status.value)">
                {{ job.status.label }}
              </craft-badge>
            </template>
            <template v-else-if="name == 'progress'">
              {{ job.progress }}%
              <span v-if="job.progressLabel">({{ job.progressLabel }})</span>
            </template>
            <template v-else-if="name == 'ttr'">
              {{ ttrValue(value) }}
            </template>
            <template v-else-if="name == 'class'">
              <code>{{ value }}</code>
            </template>
            <template v-else>
              <template v-if="typeof value === 'string'">{{ value }}</template>
              <template v-else>
                <code>
                  {{ JSON.stringify(value, null, 2) }}
                </code>
              </template>
            </template>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-6">
      <h2 class="text-lg">{{ t('Job Data') }}</h2>
      <!-- craft-pane makes its own scroll container the tab stop -->
      <craft-pane variant="code" class="my-2" :aria-label="t('Job Data')">
        <CodeHighlight language="json" :value="job" />
      </craft-pane>
    </div>
  </div>
</template>

<style scoped lang="scss">
  tr {
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }
</style>
