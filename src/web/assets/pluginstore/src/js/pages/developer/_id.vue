<template>
  <div class="ps-container">
    <template v-if="!loading">
      <plugin-index
        action="pluginStore/getPluginsByDeveloperId"
        :requestData="requestData"
        :plugins="plugins"
      >
        <template v-slot:header>
          <div
            v-if="developer"
            class="developer-card tw-flex tw-pb-2 tw-items-center">
            <div class="avatar tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-grey tw-mr-6 tw-no-line-height">
              <img
                :src="developer.photoUrl"
                width="120"
                height="120" />
            </div>

            <div class="tw-flex-1">
              <h1 class="tw-text-lg tw-font-bold tw-mb-2">
                {{ developer.developerName }}</h1>

              <p
                class="tw-mb-1"
                v-if="developer.location">{{ developer.location }}</p>

              <ul v-if="developer.developerUrl">
                <li class="tw-mr-4 tw-inline-block">
                  <btn
                    :href="developer.developerUrl"
                    block>{{ "Website"|t('app') }}
                  </btn>
                </li>
              </ul>
            </div>
          </div>
        </template>
      </plugin-index>
    </template>
    <template v-else>
      <spinner></spinner>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import PluginIndex from '../../components/PluginIndex'

export default {
  data() {
    return {
      loading: true,
    }
  },

  components: {
    PluginIndex,
  },

  computed: {
    ...mapState({
      developer: state => state.pluginStore.developer,
      plugins: state => state.pluginStore.plugins,
    }),

    requestData() {
      return {
        developerId: this.$route.params.id,
      }
    },
  },

  mounted() {
    const developerId = this.$route.params.id

    // load developer details
    this.$store.dispatch('pluginStore/getDeveloper', developerId)
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
      })
  },
}
</script>

<style
  lang="scss"
  scoped>
.developer-card {
  .avatar {
    width: 120px;
    height: 120px;
  }

  h1 {
    border-bottom: 0;
  }
}
</style>
