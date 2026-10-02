<template>
  <div class="cms-editions tw-py-6">
    <cms-edition
      v-for="(edition, key) in cmsEditions"
      :edition="edition"
      :key="key"
      :previousEdition="cmsEditions[key - 1]"
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

<style lang="scss">
  .cms-editions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(17rem, 100%), 1fr));
    @apply tw-gap-4 tw-justify-center;

    .cms-editions-edition {
      display: grid;
      grid-template-rows: subgrid;
      grid-row: span 4;
      @apply tw-gap-8;
    }
  }
</style>
