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

  // `#main` holds the secondary navigation before the content, so with a
  // sidebar, the main section link skips past it to the content itself.
  const skipLinks = computed(() => [
    {
      label: t('Skip to main section'),
      url: props.hasSidebar ? '#content-main' : '#main',
    },
    ...(props.hasSidebar
      ? [{label: t('Skip to secondary navigation'), url: '#content-sidebar'}]
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
    <!-- Shown only while there are messages; see messages.css. -->
    <a
      href="#messages"
      class="skip-link skip-link--global"
      data-messages-skip-link
      >{{ t('Skip to messages') }}</a
    >
  </div>
</template>
