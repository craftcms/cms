<template>
  <li v-if="!isPluginFree(plugin)">
    <template v-if="plugin.editions.length === 1">
      <plugin-edition
        :plugin="plugin"
        :edition="plugin.editions[0]"
        context="meta"
      />
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
import PluginEdition from './PluginEdition';

export default {
  components: {PluginEdition},
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