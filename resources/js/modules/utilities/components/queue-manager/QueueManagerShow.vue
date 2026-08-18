<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {type JobInfo} from '@/modules/queue/types';
  import VarDump from '@/common/components/VarDump.vue';
  import Badge from '@/common/components/Badge.vue';
  import {computed} from 'vue';

  const props = defineProps<{
    job: JobInfo;
  }>();

  // Cast to Record for generic iteration in template (job may have extra server-side fields)
  const jobRecord = computed(() => {
    const record = props.job as Record<string, any>;
    return Object.fromEntries(
      Object.entries(record).filter(([, value]) => value !== null)
    ) as Record<string, any>;
  });

  const hiddenProperties = ['delay', 'description', 'progressLabel', 'job'];

  function getStatusVariant(value: number) {
    if (value === 2 || value === 3) {
      return 'success';
    }

    if (value === 4) {
      return 'danger';
    }

    if (value === 5) {
      return 'warning';
    }

    return 'default';
  }

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
  <div class="p-4">
    <h2 class="mb-3">{{ job.description }}</h2>

    <table class="table-fixed border-collapse w-full">
      <tbody>
        <tr v-for="(value, name) in jobRecord" :key="name">
          <template v-if="!hiddenProperties.includes(name)">
            <th
              :class="{
                'text-left': true,
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
                <Badge :variant="getStatusVariant(job.status.value)">
                  {{ job.status.label }}
                </Badge>
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
              <template v-else-if="name.startsWith('date')">
                {{
                  new Date(value).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZoneName: 'short',
                  })
                }}
              </template>
              <template v-else>
                <template v-if="typeof value === 'string'">{{
                  value
                }}</template>
                <template v-else>
                  <code>
                    {{ JSON.stringify(value, null, 2) }}
                  </code>
                </template>
              </template>
            </td>
          </template>
        </tr>
      </tbody>
    </table>

    <div class="mt-6">
      <h4 class="text-lg">{{ t('Job Data') }}</h4>
      <div class="my-2">
        <VarDump :data="job" style="font-size: 0.8rem" />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  tr {
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
  }
</style>
