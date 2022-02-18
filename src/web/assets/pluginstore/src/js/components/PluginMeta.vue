<template>
  <div>
    <ul class="tw-list-reset tw-space-y-2">
      <!-- Buy button -->
      <plugin-meta-buy-button :plugin="plugin" />

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
            <c-icon
              class="tw-mr-2"
              icon="book"
            />
            Documentation

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
          <c-icon
            class="tw-mr-2"
            icon="github"
          />
          Repository

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
      <!-- Install with composer -->
      <meta-stat>
        <template #title>
          Install with Composer
        </template>
        <template #content>
          <composer-install :plugin="plugin" />
        </template>
      </meta-stat>

      <!-- Active Installs -->
      <meta-stat>
        <template #title>
          Active Installs
        </template>
        <template #content>
          {{ plugin.activeInstalls|formatNumber }}
        </template>
      </meta-stat>

      <div class="tw-grid tw-grid-cols-2">
        <!-- Version -->
        <meta-stat>
          <template #title>
            Version
          </template>
          <template #content>
            {{ plugin.version }}
          </template>
        </meta-stat>

        <!-- License -->
        <meta-stat>
          <template #title>
            License
          </template>
          <template #content>
            {{ licenseLabel }}
          </template>
        </meta-stat>
      </div>

      <div class="tw-grid tw-grid-cols-2">
        <!-- Compatibility -->
        <meta-stat>
          <template #title>
            Compatibility
          </template>
          <template #content>
            {{ plugin.compatibility }}
          </template>
        </meta-stat>

        <!-- Total releases -->
        <meta-stat>
          <template #title>
            Total releases
          </template>
          <template #content>
            X
          </template>
        </meta-stat>
      </div>

      <!-- Last release -->
      <meta-stat>
        <template #title>
          Last release
        </template>
        <template #content>

        </template>
      </meta-stat>

      <!-- Categories -->
      <meta-stat>
        <template #title>
          Categories
        </template>
        <template #content>
          <ul v-if="pluginCategories.length > 0">
            <template v-for="(category, key) in pluginCategories">
              <li
                :key="key"
                class="tw-inline-block tw-mr-2 tw-my-1.5"
              >
                <nuxt-link
                  :key="key"
                  class="tw-px-4 tw-py-1.5 tw-text-xs tw-font-medium tw-rounded-full"
                  :class="{
                    'tw-bg-blue-50 hover:tw-bg-blue-600': true,
                    'hover:tw-text-white': true,
                  }"
                  :to="'/categories/'+category.slug"
                  :title="category.title + ' plugins for Craft CMS'"
                >
                  {{ category.title }}
                </nuxt-link>
              </li>
            </template>
          </ul>
        </template>
      </meta-stat>

      <!-- Activity -->
      <github-activity
        :closed-issues="plugin.githubStats.closedIssues"
        :new-issues="plugin.githubStats.newIssues"
        :merged-pull-requests="plugin.githubStats.mergedPullRequests"
        :open-pull-requests="plugin.githubStats.openPullRequests"
      />

      <!-- Report an issue -->
      <ul class="list-reset space-y-2 mt-8">
        <li>
          <c-btn
            kind="danger"
            :href="'mailto:issues@craftcms.com?subject=' + encodeURIComponent('Issue with ' + plugin.name) + '&body=' + encodeURIComponent('I would like to report the following issue with '+plugin.name+' (https://plugins.craftcms.com/' + plugin.handle + '):\n\n')"
          >
            <c-icon
              class="tw-mr-1.5"
              icon="flag"
              set="solid"
            />
            Report plugin
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
import ComposerInstall from './ComposerInstall';
import GithubActivity from './github-activity/GithubActivity';

export default {
  components: {GithubActivity, ComposerInstall, PluginMetaBuyButton, MetaStat},
  props: {
    plugin: {
      type: Object,
      required: true
    }
  },
  computed: {
    ...mapState({
      categories: state => state.pluginStore.categories,
    }),
    licenseLabel() {
      switch (this.plugin.license) {
        case 'craft':
          return 'Craft'

        case 'mit':
          return 'MIT'
        default:
          return null
      }
    },

    pluginCategories() {
      return this.categories.filter(c => {
        return this.plugin.categoryIds.find(pc => pc == c.id)
      })
    },
  }
}
</script>