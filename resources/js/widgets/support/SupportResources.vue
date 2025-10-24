<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import VarDump from '@/components/VarDump.vue';
  import {inject} from 'vue';

  const bundleUrl = inject('support-widget:bundle-url');
  const resources = [
    {
      url: 'https://craftcms.com/partners',
      iconPath: '/logos/craft-partners.svg',
      title: t('app', 'Craft Partners'),
      description: t('app', 'Find an official Craft Partner'),
    },
    {
      url: 'https://craftcms.com/discord',
      iconPath: '/logos/discord.svg',
      title: t('app', 'Discord'),
      description: t('app', 'Meet the Craft community'),
    },
    {
      url: 'https://craftquest.io',
      iconPath: '/logos/craftquest.svg',
      title: t('app', 'CraftQuest'),
      description: t('app', 'Unlimited video training'),
    },
  ];

  const links = [
    {
      icon: 'book',
      url: 'https://craftcms.com/docs/5.x/',
      title: t('app', 'Documentation'),
    },
    {
      icon: 'magnifying-glass',
      url: 'https://craftcms.com/knowledge-base',
      title: t('app', 'Knowledge Base'),
    },
  ];
</script>

<template>
  <div class="tw:mt-6 tw:py-5 tw:border-t tw:border-t-subtle">
    <div class="tw:text-center tw:mb-3">{{ t('app', 'More Resources') }}</div>
    <div class="major-resources">
      <template v-for="resource in resources" :key="resource.url">
        <a :href="resource.url" target="_blank" class="major-resource">
          <img
            :src="`${bundleUrl}${resource.iconPath}`"
            :alt="resource.title"
            class="resource-logo"
          />
          <p class="tw:text-sm">{{ resource.description }}</p>

          <craft-icon
            name="arrow-up-right-from-square"
            class="tw:absolute tw:top-2 tw:right-2"
            style="font-size: 0.8em"
          ></craft-icon>
        </a>
      </template>
    </div>

    <div
      class="tw:flex tw:justify-center tw:items-center tw:flex-wrap tw:gap-6 tw:mt-6"
    >
      <template v-for="(link, index) in links">
        <a
          :href="link.url"
          class="minor-resource"
          target="_blank"
          rel="noopener"
        >
          <craft-icon :name="link.icon"></craft-icon>
          {{ link.title }}
          <craft-icon
            name="arrow-up-right-from-square"
            style="font-size: 0.8em"
          ></craft-icon>
        </a>
        <span class="separator" v-if="index < links.length - 1"></span>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .resource-logo {
    max-width: 75%;
    margin: 0 auto;
    aspect-ratio: 120/24;
  }

  .major-resources {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
    justify-content: center;
    align-items: center;
  }

  .major-resource {
    border: 1px solid var(--c-border-subtle);
    border-radius: var(--c-radius-md);
    padding: var(--c-spacing-lg);
    text-align: center;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    aspect-ratio: 16/9;
    gap: var(--c-spacing-md);
    color: var(--c-fg-muted);
    flex: 0 1 calc(50% - var(--c-spacing-md));

    &:hover {
      background-color: var(--color-blue-50);
    }
  }

  .minor-resource {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);

    &:hover {
      text-decoration: underline;
    }
  }

  .separator {
    display: inline-block;
    width: 1px;
    height: 1em;
    background-color: var(--c-border-subtle);
  }
</style>
