<template>
  <div class="tw-w-14 tw-h-14">
    <!-- CMS edition -->
    <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
      <img :src="craftLogo" class="tw-w-full" />
    </template>

    <!-- Plugin edition -->
    <template v-else-if="item.lineItem.purchasable.type === 'plugin-edition'">
      <img
        v-if="item.plugin.iconUrl"
        :src="item.plugin.iconUrl"
        class="tw-w-full"
      />
    </template>
  </div>
</template>

<script>
  import {mapState} from 'vuex';

  export default {
    props: {
      item: Object,
    },

    computed: {
      ...mapState({
        craftLogo: (state) => state.craft.craftLogo,
      }),
    },
  };
</script>
