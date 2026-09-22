<script setup lang="ts">
  /**
   * The page's screen-reader plumbing: the live region, the anchor focus
   * returns to after an Inertia visit, and the skip links.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed} from 'vue';
  import LiveRegion from '@/common/components/LiveRegion.vue';

  const props = defineProps<{
    hasSidebar: boolean;
    additionalSkipLinks?: Array<{label: string; url: string}>;
  }>();

  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(props.hasSidebar
      ? [{label: t('Skip to secondary navigation'), url: '#secondary-nav'}]
      : []),
    ...(props.additionalSkipLinks ?? []),
  ]);
</script>

<template>
  <div>
    <LiveRegion />
    <!-- Focus lands here on Inertia navigation. See `handleAccessibleRouting`. -->
    <span id="route-focus-anchor" tabindex="-1" class="sr-only"></span>
    <a
      v-for="link in skipLinks"
      :key="link.url"
      :href="link.url"
      class="skip-link skip-link--global"
      >{{ link.label }}</a
    >
  </div>
</template>
