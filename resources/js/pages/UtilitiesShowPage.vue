<script setup lang="ts">
  import IndexLayout from '@/layout/IndexLayout.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import CpLink from '@/components/CpLink.vue';

  interface UtilityItem {
    id: string;
    url: string;
    iconSvg: string;
    displayName: string;
    iconPath: string;
    badgeCount: number;
  }

  defineProps<{
    id: string;
    title: string;
    contentHtml?: string;
    toolbarHtml?: string;
    footerHtml?: string;
    viewData?: unknown;
    utilities: Array<UtilityItem>;
    bridgedHeadHtml?: string;
    bridgedBodyHtml?: string;
  }>();
</script>

<template>
  <IndexLayout :title="title" :debug="$props">
    <template #actions>
      <DynamicHtmlRenderer
        v-if="toolbarHtml"
        :html="toolbarHtml"
      ></DynamicHtmlRenderer>
    </template>
    <template #interior-nav>
      <craft-nav-list>
        <template v-for="utility in utilities" :key="utility.id">
          <CpLink
            as="craft-nav-item"
            :icon="utility.iconPath"
            :href="utility.url"
            :active="utility.id === id"
            :indicator="!!utility.badgeCount"
            block
          >
            {{ utility.displayName }}
          </CpLink>
        </template>
      </craft-nav-list>
    </template>

    <div class="content-pane">
      <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
      <DynamicHtmlRenderer v-if="footerHtml" :html="footerHtml" />
    </div>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
