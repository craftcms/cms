<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CpContainer from '@/common/components/CpContainer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import PluginSearch from './PluginSearch.vue';
  import {useCoreData} from '../composables/usePluginStoreData';

  const {failed} = useCoreData();
</script>

<template>
  <LayoutSlot name="content-actions">
    <PluginSearch />
  </LayoutSlot>
  <CpContainer class="py-lg">
    <craft-callout v-if="failed" variant="danger" icon="triangle-exclamation">
      {{ t('The Plugin Store is not available, please try again later.') }}
    </craft-callout>
    <slot v-else></slot>
  </CpContainer>
</template>
