<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import VarDump from '@/components/VarDump.vue';

  const {nav} = useCraftData();
</script>

<template>
  <craft-nav-list label="Primary">
    <craft-nav-item
      v-for="item in nav"
      :key="item.url"
      :icon="item.icon"
      :url="item.url"
      :active="item.sel"
      :indicator="!!item.badgeCount"
    >
      {{ item.label }}

      <template v-if="item.subnav">
        <craft-nav-list slot="subnav" v-if="item.subnav">
          <craft-nav-item
            v-for="subnavItem in item.subnav"
            :key="subnavItem.url"
            :icon="subnavItem.icon"
            :active="subnavItem.sel"
            :url="subnavItem.url"
            :indicator="subnavItem.badgeCount"
          >
            {{ subnavItem.label }}
          </craft-nav-item>
        </craft-nav-list>
      </template>
    </craft-nav-item>
  </craft-nav-list>
</template>

<style scoped lang="scss"></style>
