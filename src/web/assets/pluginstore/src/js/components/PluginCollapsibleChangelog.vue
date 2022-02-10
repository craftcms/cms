<template>
  <div
    class="plugin-changelog"
    :class="{collapsed: !showMore}">
    <h2>{{ "Changelog"|t('app') }}</h2>

    <template v-if="loading">
      <c-spinner class="tw-mt-4"/>
    </template>

    <template v-else>
      <div class="releases">
        <template v-for="(release, key) in pluginChangelog">
          <changelog-release
            :key="key"
            :release="release"></changelog-release>
        </template>
      </div>

      <div class="more">
        <a
          v-if="showMore === false"
          @click.prevent="showMore = true"
          class="c-btn">{{ "More"|t('app') }}</a>
        <a
          v-if="showMore === true"
          @click.prevent="showMore = false"
          class="c-btn">{{ "Less"|t('app') }}</a>
      </div>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'

import ChangelogRelease from './ChangelogRelease'

export default {
  props: ['pluginId'],

  data() {
    return {
      showMore: false,
      loading: false,
    }
  },

  components: {
    ChangelogRelease
  },

  computed: {
    ...mapState({
      pluginChangelog: state => state.pluginStore.pluginChangelog,
    }),
  },

  mounted() {
    this.loading = true

    this.$store.dispatch('pluginStore/getPluginChangelog', this.pluginId)
      .then(() => {
        this.loading = false
      })
  },
}
</script>


<style lang="scss">
.plugin-changelog {
  @apply tw-mb-8;

  &.collapsed {
    @apply tw-relative tw-overflow-hidden;
    height: 400px;

    .more {
      @apply tw-block tw-absolute tw-bottom-0 tw-w-full tw-pb-4;
      padding-top: 200px;
      background: rgb(255, 255, 255);
      background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 1) 80%);
    }
  }

  .more {
    @apply tw-text-center;

    a {
      @apply tw-inline-block;
    }
  }

  .changelog-release:last-child {
    @apply tw-border-b-0;
  }
}
</style>