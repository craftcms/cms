<template>
  <li v-if="!isPluginFree(plugin)">
    <template v-if="plugin.editions.length === 1">
      <c-btn
        kind="primary"
        :href="'#add-to-cart'"
        class="text-center"
        block
      >
        Buy {{ getPriceRangeLabel(plugin) }}
      </c-btn>

      <small class="text-gray-500 dark:text-gray-400 text-sm leading-snug mt-2 mb-6 block">
        Price includes 1 year of updates.<br>
        {{ plugin.editions[0].renewalPrice|currency }}/year per site for updates after that.
      </small>
    </template>

    <template v-else>
      <c-btn
        kind="primary"
        :to="'/' + plugin.handle + '/pricing'"
        class="text-center"
        block
      >
        Buy {{ getPriceRangeLabel(plugin) }}
      </c-btn>
    </template>
  </li>
</template>

<script>
import {isPluginFree, getPriceRangeLabel} from '../utils/plugins';

export default {
  props: {
    plugin: {
      type: Object,
      required: true,
    },
  },

  methods: {
    isPluginFree,
    getPriceRangeLabel,
  }
}
</script>