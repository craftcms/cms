<template>
  <meta-stat v-if="issueStats" class="tw-pt-4" :border="false">
    <template #title>
      <span v-html="githubActivityTitle"></span>
    </template>
    <template #content>
      <div
        class="tw-mt-4 tw-border tw-border-solid tw-border-gray-200 tw-rounded-lg"
      >
        <div class="tw-grid tw-grid-cols-2">
          <activity-stat class="tw-border-r tw-border-solid tw-border-gray-200">
            <template #icon>
              <svg
                aria-hidden="true"
                height="16"
                viewBox="0 0 16 16"
                version="1.1"
                width="16"
                data-view-component="true"
                class="tw-text-purple-500"
                fill="currentColor"
              >
                <path
                  d="M11.28 6.78a.75.75 0 00-1.06-1.06L7.25 8.69 5.78 7.22a.75.75 0 00-1.06 1.06l2 2a.75.75 0 001.06 0l3.5-3.5z"
                />
                <path
                  fill-rule="evenodd"
                  d="M16 8A8 8 0 110 8a8 8 0 0116 0zm-1.5 0a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"
                />
              </svg>
            </template>
            <template #count>
              {{ issueStats.closedIssues }}
            </template>
            <template #title>
              {{ 'Closed Issues' | t('app') }}
            </template>
          </activity-stat>
          <activity-stat>
            <template #icon>
              <svg
                aria-hidden="true"
                height="16"
                viewBox="0 0 16 16"
                version="1.1"
                width="16"
                data-view-component="true"
                class="tw-text-green-500"
                fill="currentColor"
              >
                <path d="M8 9.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                <path
                  fill-rule="evenodd"
                  d="M8 0a8 8 0 100 16A8 8 0 008 0zM1.5 8a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0z"
                />
              </svg>
            </template>
            <template #count>
              {{ issueStats.openIssues }}
            </template>
            <template #title>
              {{ 'New Issues' | t('app') }}
            </template>
          </activity-stat>
          <activity-stat
            class="tw-border-t tw-border-r tw-border-solid tw-border-gray-200"
          >
            <template #icon>
              <svg
                aria-hidden="true"
                height="16"
                viewBox="0 0 16 16"
                version="1.1"
                width="16"
                data-view-component="true"
                class="tw-text-purple-500"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M5 3.254V3.25v.005a.75.75 0 110-.005v.004zm.45 1.9a2.25 2.25 0 10-1.95.218v5.256a2.25 2.25 0 101.5 0V7.123A5.735 5.735 0 009.25 9h1.378a2.251 2.251 0 100-1.5H9.25a4.25 4.25 0 01-3.8-2.346zM12.75 9a.75.75 0 100-1.5.75.75 0 000 1.5zm-8.5 4.5a.75.75 0 100-1.5.75.75 0 000 1.5z"
                />
              </svg>
            </template>
            <template #count>
              {{ issueStats.mergedPulls }}
            </template>
            <template #title>
              {{ 'Merged PRs' | t('app') }}
            </template>
          </activity-stat>
          <activity-stat class="tw-border-t tw-border-solid tw-border-gray-200">
            <template #icon>
              <svg
                aria-hidden="true"
                height="16"
                viewBox="0 0 16 16"
                version="1.1"
                width="16"
                data-view-component="true"
                class="tw-text-green-500"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.177 3.073L9.573.677A.25.25 0 0110 .854v4.792a.25.25 0 01-.427.177L7.177 3.427a.25.25 0 010-.354zM3.75 2.5a.75.75 0 100 1.5.75.75 0 000-1.5zm-2.25.75a2.25 2.25 0 113 2.122v5.256a2.251 2.251 0 11-1.5 0V5.372A2.25 2.25 0 011.5 3.25zM11 2.5h-1V4h1a1 1 0 011 1v5.628a2.251 2.251 0 101.5 0V5A2.5 2.5 0 0011 2.5zm1 10.25a.75.75 0 111.5 0 .75.75 0 01-1.5 0zM3.75 12a.75.75 0 100 1.5.75.75 0 000-1.5z"
                />
              </svg>
            </template>
            <template #count>
              {{ issueStats.openPulls }}
            </template>
            <template #title>
              {{ 'Open PRs' | t('app') }}
            </template>
          </activity-stat>
        </div>
      </div>
    </template>
  </meta-stat>
</template>

<script>
  import MetaStat from '../MetaStat';
  import ActivityStat from './ActivityStat';

  export default {
    components: {ActivityStat, MetaStat},
    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },

    computed: {
      githubActivityTitle() {
        return this.$options.filters.t(
          'Activity <small>({period} days)</small>',
          'app',
          {
            period: this.issueStats.period,
          }
        );
      },

      issueStats() {
        if (!this.plugin) {
          return null;
        }

        if (!this.plugin.issueStats) {
          return null;
        }

        return this.plugin.issueStats.find((s) => s.period === 30);
      },
    },
  };
</script>
