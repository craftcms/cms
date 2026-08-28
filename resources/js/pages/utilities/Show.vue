<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  interface UtilityItem {
    id: string;
    url: string;
    iconSvg: string;
    displayName: string;
    iconPath: string;
    badgeCount: number;
  }

  const props = defineProps<{
    id: string;
    title: string;
    contentHtml?: string;
    toolbarHtml?: string;
    footerHtml?: string;
    viewData?: unknown;
    utilities: Array<UtilityItem>;
  }>();

  useAppLayout(() => ({title: props.title}));
</script>

<template>
  <LayoutSlot name="actions">
    <DynamicHtmlRenderer
      v-if="toolbarHtml"
      :html="toolbarHtml"
    ></DynamicHtmlRenderer>
  </LayoutSlot>
  <LayoutSlot name="sidebar">
    <SecondaryNav>
      <craft-nav-list>
        <template v-for="utility in utilities" :key="utility.id">
          <CpLink
            as="craft-nav-item"
            :icon="utility.iconPath"
            :href="utility.url"
            :active="utility.id === id"
            :indicator="!!utility.badgeCount"
            block
            flush
          >
            {{ utility.displayName }}
          </CpLink>
        </template>
      </craft-nav-list>
    </SecondaryNav>
  </LayoutSlot>

  <craft-pane appearance="raised" padding="0" class="cp:@container">
    <div class="content-pane">
      <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
      <DynamicHtmlRenderer v-if="footerHtml" :html="footerHtml" />
    </div>
  </craft-pane>
</template>
