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
            <div class="avatar tw-w-24 tw-h-24 tw-inline-block tw-overflow-hidden tw-rounded-full tw-bg-grey tw-mr-6 tw-no-line-height">
              <img
                :src="developer.photoUrl"
                class="tw-w-full tw-h-full"
              />
            </div>

            <div class="tw-flex-1">
              <h1 class="tw-text-lg tw-font-bold tw-mb-2">
                {{ developer.developerName }}</h1>

              <p
                class="tw-mb-1"
                v-if="developer.location">{{ developer.location }}</p>

              <ul v-if="developer.developerUrl">
                <li class="tw-mr-4 tw-inline-block">
                  <c-btn
                    :href="developer.developerUrl"
                    block>{{ "Website"|t('app') }}
                  </c-btn>
                </li>
              </ul>
            </div>
          </div>
        </template>
      </plugin-index>
    </template>
    <template v-else>
      <c-spinner/>
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
