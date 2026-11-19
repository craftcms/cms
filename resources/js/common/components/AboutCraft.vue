<script setup lang="ts">
  /**
   * Craft’s version and edition, any trials of Craft or plugins that are
   * installed, and, for users who can update Craft, whether updates are
   * available, as the “About Craft CMS” dashboard widget and command palette
   * view show them.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {ref, watch} from 'vue';
  import AboutCraftController from '@actions/App/AboutCraftController';
  import type {AboutCraftInfo} from './about-craft';
  import AboutCraftRow from './AboutCraftRow.vue';
  import AboutCraftUpdates from './AboutCraftUpdates.vue';
  import CraftCmsMark from './CraftCmsMark.vue';
  import EditionBadge from './EditionBadge.vue';

  const props = defineProps<{
    info: AboutCraftInfo;
  }>();

  /**
   * The trials, which are only known while Craft's license info is cached.
   * That's fetched along with the updates, so they're fetched again after
   * the updates are checked.
   */
  const trial = ref(props.info.trial);

  watch(
    () => props.info.trial,
    (value) => (trial.value = value)
  );

  async function refreshTrial() {
    try {
      const {data} = await actionClient.get<AboutCraftInfo>(
        AboutCraftController.url()
      );
      trial.value = data.trial;
    } catch {
      // The trials stay as they were.
    }
  }
</script>

<template>
  <div class="about-craft">
    <div class="about-craft__identity">
      <CraftCmsMark class="about-craft__mark" />
      <div>
        <p class="about-craft__app cp-h3">
          <span lang="en">Craft CMS</span>{{ ' ' }}
          <EditionBadge :edition="info.edition" class="about-craft__edition" />
        </p>
        <p class="about-craft__version">{{ info.version }}</p>
      </div>
    </div>
    <div v-if="info.updates" class="about-craft__section">
      <AboutCraftUpdates :updates="info.updates" @checked="refreshTrial" />
    </div>
    <div v-if="trial" class="about-craft__section">
      <AboutCraftRow class="about-craft__trial">
        <template #icon><craft-icon name="flask"></craft-icon></template>
        <span class="about-craft-row__title">{{ trial.message }}</span>
        <template #action>
          <!-- Leads off-site, so it opens in a new tab, and says so -->
          <craft-button
            variant="plain"
            inherit
            flush="inline block"
            icon="circle-right"
            icon-position="suffix"
            :href="trial.cartUrl"
            class="about-craft-row__link about-craft__buy"
            >{{ t('Buy now') }}</craft-button
          >
        </template>
      </AboutCraftRow>
    </div>
  </div>
</template>

<style scoped>
  /* Its content can be kept to --about-craft-max-inline-size, centered, while
     the lines between sections still reach its edges. */
  .about-craft {
    display: flex;
    flex-direction: column;
    /* Resolved against this element's width, where it's used */
    --_inset: max(0px, (100% - var(--about-craft-max-inline-size, 100%)) / 2);
  }

  .about-craft__identity {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    padding-inline: var(--_inset);
  }

  .about-craft__mark {
    flex: none;
    inline-size: calc(44rem / 16);
    block-size: calc(44rem / 16);
  }

  /* Styled like widget titles (.cp-h3). Centered as flex items: in a taller
     line, vertical-align leaves the badge low. */
  .about-craft__app {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs) calc(var(--c-spacing-sm) + 2px);
  }

  .about-craft__edition {
    color: var(--c-text-quiet);
  }

  .about-craft__version {
    margin: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }

  /* Each section has a line above it, with even space on either side. */
  .about-craft__section {
    margin-block-start: var(--c-spacing-lg);
    /* With its row's own padding, it matches the space above the line. */
    padding-block-start: calc(var(--c-spacing-lg) - var(--c-spacing-sm));
    /* The line reaches past the container's padding, given as --about-craft-bleed, to its edges. */
    margin-inline: calc(var(--about-craft-bleed, 0px) * -1);
    padding-inline: calc(var(--about-craft-bleed, 0px) + var(--_inset));
    /* Softer than the card's and palette's own dividers, as it only groups */
    border-block-start: 1px solid
      color-mix(in srgb, var(--c-color-neutral-border-quiet) 50%, transparent);
  }

  /* The section above ends with its row's padding, which counts toward the space. */
  .about-craft__section + .about-craft__section {
    margin-block-start: calc(var(--c-spacing-lg) - var(--c-spacing-sm));
  }

  /* Its row's padding would add to the container's below it. */
  .about-craft__section:last-child {
    margin-block-end: calc(var(--c-spacing-sm) * -1);
  }

  /* Available updates fill their section, from line to line and edge to edge,
     rather than just their row. */
  .about-craft__section:has(.about-craft-row[data-tone='accent']) {
    /* Below the line above it, rather than tinting it */
    background-clip: padding-box;
    background-color: var(
      --about-craft-tint-fill,
      var(--c-color-accent-fill-quiet)
    );
    /* The space below it, which the next section would otherwise leave above its line */
    padding-block-end: calc(var(--c-spacing-lg) - var(--c-spacing-sm));

    & + .about-craft__section {
      margin-block-start: 0;
    }

    /* Last, it reaches the container's bottom edge too. */
    &:last-child {
      margin-block-end: calc(var(--about-craft-bleed, 0px) * -1);
      padding-block-end: calc(
        var(--about-craft-bleed, 0px) - var(--c-spacing-sm)
      );
    }

    /* The section's fill stands in for the row's own. */
    & :deep(.about-craft-row) {
      background: none;
    }
  }
</style>
