<script setup lang="ts">
  /**
   * The header's edition badge, which opens the “About Craft CMS” view in a
   * popover. Its trials and updates are fetched each time it opens. A dot
   * marks it while there are updates or trials to act on.
   */
  import {t} from '@craftcms/ui';
  import AboutCraft from '@/common/components/AboutCraft.vue';
  import EditionBadge from '@/common/components/EditionBadge.vue';
  import useAboutCraftInfo from '@/common/composables/useAboutCraftInfo';
  import useCraftData from '@/common/composables/useCraftData';

  const {app} = useCraftData();
  const {info, load} = useAboutCraftInfo();
</script>

<template>
  <craft-popover placement="bottom-end" .distance="8" @craft-show.self="load">
    <!-- Named by the badge, “Pro edition”, and the dot's text, if it has
      one; the tooltip says what it opens -->
    <craft-button
      slot="invoker"
      id="about-craft-trigger"
      class="about-craft-trigger"
      type="button"
      size="small"
      variant="plain"
    >
      <span class="about-craft-trigger__content">
        <EditionBadge
          :edition="info.edition"
          class="about-craft-trigger__badge"
        />
        <craft-badge-indicator
          v-if="app.needsAttention"
          class="about-craft-trigger__dot"
          :alt-text="t('Needs attention')"
        ></craft-badge-indicator>
      </span>
    </craft-button>
    <div slot="content" class="about-craft-menu">
      <AboutCraft :info="info" />
    </div>
  </craft-popover>
  <craft-tooltip for="about-craft-trigger" invoker-relation="description">
    {{ t('About Craft CMS') }}
  </craft-tooltip>
</template>

<style scoped>
  .about-craft-trigger {
    --c-color-neutral-on-quiet: var(--cp-chrome-text-color);
    padding-inline: 2px;
    margin-inline: -3px;
  }

  .about-craft-trigger__badge {
    color: var(--c-text-quiet);
  }

  .about-craft-trigger__content {
    position: relative;
    display: inline-flex;
  }

  .about-craft-trigger__dot {
    --badge-border-width: 2px;
    position: absolute;
    inset-block-start: -4.5px;
    inset-inline-end: -4.5px;
  }

  craft-popover::part(popup) {
    width: min(24rem, calc(100vw - 2rem));
    max-width: none;
  }

  .about-craft-menu {
    --about-craft-bleed: var(--c-spacing-lg);
    padding: var(--c-spacing-lg);
  }
</style>
