<template>
  <div>
    <!-- CMS edition -->
    <template v-if="item.lineItem.purchasable.type === 'cms-edition'">
      <strong>Craft CMS</strong>
      <edition-badge :name="item.lineItem.purchasable.name"></edition-badge>
    </template>

    <!-- Plugin edition -->
    <template v-else-if="item.lineItem.purchasable.type === 'plugin-edition'">
      <strong>{{ item.plugin.name }}</strong>
      <edition-badge
        v-if="item.plugin.editions > 1"
        :name="item.lineItem.purchasable.name"
      ></edition-badge>
    </template>
  </div>
</template>

<script>
  import EditionBadge from '../../../EditionBadge';
  export default {
    components: {EditionBadge},
    props: {
      item: Object,
    },
  };
</script>
