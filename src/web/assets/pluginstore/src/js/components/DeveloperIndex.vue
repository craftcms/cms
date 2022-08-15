<template>
  <div>
    <div class="tw-border-b tw-border-solid tw-border-gray-200">
      <slot name="header" />
    </div>

    <div
      class="tw-grid-plugins tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-x-8"
    >
      <template v-for="(developer, developerKey) in developers">
        <div :key="developerKey" class="tw-grid-box tw-border-b">
          <div class="tw-flex tw-items-center tw-py-6">
            <div class="tw-bg-red-500 tw-rounded-full tw-w-16 tw-h-16 tw-mr-4">
              <!-- Developer icon -->
            </div>

            <div class="developer-card">
              <h3 class="font-bold">
                {{ developer.name }}
              </h3>

              <ul>
                <li>3 plugins</li>
              </ul>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
  import {mapState} from 'vuex';

  export default {
    props: {
      requestData: {
        type: Object,
        required: true,
      },
    },

    data() {
      return {
        nbDevelopers: 24,
      };
    },

    computed: {
      ...mapState({
        developers: (state) => state.developerIndex.developers,
      }),
    },

    mounted() {
      this.requestDevelopers();
    },

    methods: {
      requestDevelopers() {
        this.$store.dispatch('developerIndex/searchDevelopers', {
          ...this.requestData,
        });
      },
    },
  };
</script>
