<template>
  <div>
    <ul class="tw-list-reset tw-space-y-2">
      <!-- Buy button -->
      <li>
        <plugin-meta-buy-button :plugin="plugin" />
      </li>

      <!-- Documentation Button -->
      <template v-if="plugin.documentationUrl">
        <li>
          <c-btn
            block
            target="_blank"
            rel="noopener"
            :href="plugin.documentationUrl"
            :title="plugin.name + ' Documentation'"
          >
            <c-icon class="tw-mr-2" icon="book" />
            {{ 'Documentation' | t('app') }}

            <svg
              class="tw-inline-block tw-w-3 tw-text-grey tw-ml-1"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
          </c-btn>
        </li>
      </template>

      <!-- Repository Button -->
      <li>
        <c-btn
          block
          rel="noopener"
          target="_blank"
          :href="plugin.repository"
          :title="plugin.name + ' Repository'"
        >
          <c-icon class="tw-mr-2" icon="github" />
          {{ 'Repository' | t('app') }}

          <svg
            class="tw-inline-block tw-w-3 tw-text-grey tw-ml-1"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
            />
          </svg>
        </c-btn>
      </li>
    </ul>

    <!-- Meta data -->
    <dl class="tw-mt-2">
      <install-plugin :plugin="plugin" />

      <active-installs :plugin="plugin" />

      <div class="tw-grid tw-grid-cols-2">
        <!-- Version -->
        <meta-stat>
          <template #title>
            {{ 'Version' | t('app') }}
          </template>
          <template #content>
            {{ plugin.version }}
          </template>
        </meta-stat>

        <!-- License -->
        <meta-stat>
          <template #title>
            {{ 'License' | t('app') }}
          </template>
          <template #content>
            {{ licenseLabel }}
          </template>
        </meta-stat>
      </div>

      <div
        :class="{
          'tw-grid tw-grid-cols-2': plugin.totalReleases,
        }"
      >
        <!-- Compatibility -->
        <meta-stat>
          <template #title>
            {{ 'Compatibility' | t('app') }}
          </template>
          <template #content>
            {{ plugin.compatibility }}
          </template>
        </meta-stat>

        <!-- Total releases -->
        <template v-if="plugin.totalReleases">
          <meta-stat>
            <template #title>
              {{ 'Total releases' | t('app') }}
            </template>
            <template #content>
              {{ plugin.totalReleases }}
            </template>
          </meta-stat>
        </template>
      </div>

      <!-- Last release -->
      <meta-stat>
        <template #title>
          {{ 'Last release' | t('app') }}
        </template>
        <template #content>
          {{ plugin.lastUpdate | formatDate }}
        </template>
      </meta-stat>

      <!-- Categories -->
      <meta-stat class="meta-categories">
        <template #title>
          {{ 'Categories' | t('app') }}
        </template>
        <template #content>
          <ul v-if="pluginCategories.length > 0">
            <template v-for="(category, key) in pluginCategories">
              <li :key="key" class="tw-inline-block tw-mr-2 tw-my-1.5">
                <router-link
                  :key="key"
                  class="tw-px-4 tw-py-1.5 tw-text-xs tw-font-medium tw-rounded-full tw-cursor-pointer hover:tw-no-underline"
                  :class="{
                    'tw-bg-blue-50 hover:tw-bg-blue-600': true,
                    'hover:tw-text-white': true,
                  }"
                  :to="'/categories/' + category.id"
                  :title="category.title + ' plugins for Craft CMS'"
                >
                  {{ category.title }}
                </router-link>
              </li>
            </template>
          </ul>
        </template>
      </meta-stat>

      <github-activity :plugin="plugin" />

      <!-- Report an issue -->
      <ul class="tw-list-reset tw-space-y-2 tw-mt-8">
        <li>
          <c-btn
            kind="danger"
            :href="
              'mailto:issues@craftcms.com?subject=' +
              encodeURIComponent('Issue with ' + plugin.name) +
              '&body=' +
              encodeURIComponent(
                'I would like to report the following issue with ' +
                  plugin.name +
                  ' (https://plugins.craftcms.com/' +
                  plugin.handle +
                  '):\n\n'
              )
            "
          >
            <c-icon class="tw-mr-1.5" icon="flag" set="solid" />
            {{ 'Report plugin' | t('app') }}
          </c-btn>
        </li>
      </ul>
    </dl>
  </div>
</template>

<script>
  import MetaStat from './MetaStat';
  import PluginMetaBuyButton from './PluginMetaBuyButton';
  import {mapState} from 'vuex';
  import InstallPlugin from './InstallPlugin';
  import GithubActivity from './github-activity/GithubActivity';
  import ActiveInstalls from './ActiveInstalls';

  export default {
    components: {
      InstallPlugin,
      ActiveInstalls,
      GithubActivity,
      PluginMetaBuyButton,
      MetaStat,
    },
    props: {
      plugin: {
        type: Object,
        required: true,
      },
    },
    computed: {
      ...mapState({
        categories: (state) => state.pluginStore.categories,
      }),
      licenseLabel() {
        switch (this.plugin.license) {
          case 'craft':
            return 'Craft';

          case 'mit':
            return 'MIT';
          default:
            return null;
        }
      },

      pluginCategories() {
        return this.categories.filter((c) => {
          return this.plugin.categoryIds.find((pc) => pc == c.id);
        });
      },
    },
  };
</script>
