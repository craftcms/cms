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
          :class="{'about-craft-trigger__badge--indicated': app.needsAttention}"
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

  /* Mirrors badgeIndicatorCutoutStyles, which a scoped style can't import. */
  .about-craft-trigger__badge--indicated {
    --c-badge-indicator-cutout-x: calc(100% - 2px);
    --c-badge-indicator-cutout-y: 2px;
    mask-image: radial-gradient(
      circle at var(--c-badge-indicator-cutout-x)
        var(--c-badge-indicator-cutout-y),
      transparent 6px,
      #000 6.5px
    );

    &:dir(rtl) {
      --c-badge-indicator-cutout-x: 2px;
    }
  }

  .about-craft-trigger__dot {
    position: absolute;
    inset-block-start: -2px;
    inset-inline-end: -2px;
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
