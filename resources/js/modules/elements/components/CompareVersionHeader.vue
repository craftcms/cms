<script setup lang="ts">
  /**
   * One side's header on the Compare screen: a menu for what tells the two
   * sides apart, the site or the version, under a heading that names its pane.
   * When both do, there's a menu for each, which wrap onto lines of their own
   * beside the side's A/B icon where there isn't room for them side by side.
   * An element in one site has no site menu, and one without drafts or
   * revisions has no version menu; with neither, the header is just its label.
   */
  import '@craftcms/ui/components/tooltip/tooltip';
  import {t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';
  import {
    sideIcons,
    sideLetters,
    type CompareSide,
  } from '@/modules/elements/compareLayout';

  const props = defineProps<{
    side: CompareSide;
    /** The heading's id, which names the side's pane. */
    headingId: string;
    label: string;
    /** Set when the element is in more than one site the user can edit. */
    siteName: string | null;
    /** Empty when the element type has no drafts or revisions. */
    versionMenu: ActionItems;
    siteMenu: ActionItems;
    /** Whether the version has unsaved edits, as the editor's “Edited” badge shows. */
    edited?: boolean;
    /**
     * Whether the site and the version are the same on both sides. One that is
     * has no menu, since it doesn't tell the sides apart.
     */
    shared?: {site: boolean; version: boolean};
    /**
     * Whether the screen names the sides by their letters, when nothing else
     * tells them apart, so the side's A/B icon shows at every width.
     */
    lettered?: boolean;
  }>();

  const hasSiteMenu = computed(() =>
    Boolean(props.siteMenu.length && props.siteName)
  );

  const hasVersionMenu = computed(() => props.versionMenu.length > 0);

  /**
   * Whether the header shows the site menu, which it does when the two sides'
   * sites differ, or when it's the only menu.
   */
  const showsSite = computed(
    () => hasSiteMenu.value && (!props.shared?.site || !hasVersionMenu.value)
  );

  /**
   * Whether the header shows the version menu, which it does when the two
   * sides' versions differ, or when the site doesn't tell them apart.
   */
  const showsVersion = computed(
    () => hasVersionMenu.value && (!props.shared?.version || !showsSite.value)
  );

  type ButtonKey = 'site' | 'version' | 'label';

  const buttons: Record<
    ButtonKey,
    Readonly<ReturnType<typeof useTemplateRef<HTMLElement>>>
  > = {
    site: useTemplateRef<HTMLElement>('siteButton'),
    version: useTemplateRef<HTMLElement>('versionButton'),
    label: useTemplateRef<HTMLElement>('label'),
  };

  /**
   * The id of each button or label that's cut short to fit, which then gets
   * its full text in a tooltip. Only then: the label is already the button's
   * name. The action menu gives its invoker its own id.
   */
  const truncatedIds = shallowRef<Partial<Record<ButtonKey, string>>>({});

  function isTruncated(button: HTMLElement | null): boolean {
    const clipped = (element: HTMLElement | null | undefined) =>
      Boolean(element && element.scrollWidth > element.clientWidth);

    return (
      (button?.classList.contains('compare-version-header__label') &&
        clipped(button)) ||
      clipped(button?.shadowRoot?.querySelector<HTMLElement>('[part="label"]'))
    );
  }

  useResizeObserver(
    () =>
      Object.values(buttons)
        .map((button) => button.value)
        .filter((button): button is HTMLElement => Boolean(button)),
    () => {
      truncatedIds.value = Object.fromEntries(
        (Object.keys(buttons) as ButtonKey[]).flatMap((key) => {
          const button = buttons[key].value;

          return button?.id && isTruncated(button) ? [[key, button.id]] : [];
        })
      );
    }
  );
</script>

<template>
  <div class="compare-version-header">
    <craft-icon
      :name="sideIcons[side]"
      class="compare-version-header__side-icon"
      :class="{'compare-version-header__side-icon--shown': lettered}"
      aria-hidden="true"
    ></craft-icon>
    <h2 :id="headingId" class="compare-version-header__title">
      <!-- Names the side by its letter, which its icon shows. -->
      <span class="sr-only">{{
        side === 'from'
          ? t('{letter}, compared with:', {letter: sideLetters[side]})
          : t('{letter}, showing:', {letter: sideLetters[side]})
      }}</span>
      <ActionMenu
        v-if="showsSite"
        :key="`${side}-site-${siteName}`"
        :label="siteName!"
        :actions="siteMenu"
      >
        <template #invoker>
          <craft-button
            ref="siteButton"
            slot="invoker"
            class="compare-version-header__site"
            type="button"
            variant="plain"
            size="small"
            flush="inline-start"
          >
            {{ siteName }}
            <span class="sr-only">{{ t('Choose a site') }}</span>
            <craft-icon slot="suffix" name="chevron-down"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
      <!-- Keyed on everything its button shows: the action menu renders
           its invoker once, so a change means a new menu. -->
      <ActionMenu
        v-if="showsVersion"
        :key="`${side}-${label}-${edited}`"
        :label="label"
        :actions="versionMenu"
      >
        <template #invoker>
          <craft-button
            ref="versionButton"
            slot="invoker"
            class="compare-version-header__version"
            type="button"
            variant="plain"
            size="small"
            flush="inline-start"
          >
            {{ label }}
            <span class="sr-only">{{ t('Choose a version') }}</span>
            <craft-badge
              v-if="edited"
              slot="suffix"
              fill="violet"
              size="small"
              class="compare-version-header__edited"
            >
              <craft-icon name="pen-circle" slot="prefix"></craft-icon>
              {{ t('Edited') }}
            </craft-badge>
            <craft-icon slot="suffix" name="chevron-down"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
      <span
        v-if="!hasSiteMenu && !hasVersionMenu"
        :id="`${headingId}-label`"
        ref="label"
        class="compare-version-header__label"
        >{{ label }}</span
      >
    </h2>
    <craft-tooltip v-if="truncatedIds.site" :for="truncatedIds.site">{{
      siteName
    }}</craft-tooltip>
    <craft-tooltip v-if="truncatedIds.version" :for="truncatedIds.version">{{
      label
    }}</craft-tooltip>
    <craft-tooltip v-if="truncatedIds.label" :for="truncatedIds.label">{{
      label
    }}</craft-tooltip>
  </div>
</template>

<style scoped>
  .compare-version-header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--cp-container-padding, var(--c-spacing-lg));
  }

  .compare-version-header__side-icon {
    display: none;
    flex: none;
    font-size: var(--c-text-base);
  }

  .compare-version-header__side-icon--shown {
    display: inline-flex;
  }

  .compare-version-header__title {
    display: flex;
    align-items: center;
    column-gap: var(--c-spacing-md);
    min-width: 0;
    margin: 0;
    font-size: inherit;
    font-weight: inherit;
  }

  @container compare (width < 48rem) {
    .compare-version-header__side-icon {
      display: inline-flex;
      font-size: var(--c-size-icon-md);
    }

    .compare-version-header__title {
      flex: 1 1 auto;
      flex-direction: column;
      align-items: start;
    }

    .compare-version-header craft-action-menu,
    .compare-version-header craft-button {
      max-width: 100%;
    }
  }

  .compare-version-header craft-action-menu {
    min-width: 0;
  }

  .compare-version-header craft-button {
    --c-button-width: 0;

    min-width: 0;
  }

  .compare-version-header craft-button::part(content) {
    min-width: 0;
  }

  .compare-version-header craft-button::part(label) {
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .compare-version-header__label {
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-medium);
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* The suffix stacks what's in it; the badge goes beside the chevron. */
  .compare-version-header craft-button::part(suffix) {
    flex-direction: row;
    gap: var(--c-spacing-xs);
  }

  .compare-version-header__edited {
    flex: none;
    font-weight: normal;
  }
</style>
