<template>
  <div
    class="cms-editions"
    :class="{
      'tw-py-6': true,
      'md:tw-flex md:tw--mx-4 md:tw-justify-center': true,
    }"
  >
    <cms-edition
      class="md:tw-flex-1 md:tw-mx-4 md:tw-max-w-xs"
      v-for="(edition, key) in cmsEditions"
      :edition="edition"
      :key="key"
    ></cms-edition>
  </div>
</template>

<script>
  import {mapState} from 'vuex';
  import CmsEdition from './CmsEdition';

  export default {
    components: {
      CmsEdition,
    },

    data() {
      return {
        loading: false,
      };
    },

    computed: {
      ...mapState({
        cmsEditions: (state) => state.pluginStore.cmsEditions,
      }),
    },

    beforeDestroy() {
      this.$store.dispatch('pluginStore/cancelRequests');
    },
  };
</script>
