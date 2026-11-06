<script setup lang="ts">
  /**
   * One side's header on the Compare screen: menus for the site and the
   * version it shows, under a heading that names its pane. Where there isn't
   * room for both, they're one menu, with a section for each. An element in
   * one site has no site menu, and one without drafts or revisions has no
   * version menu; with neither, the header is just its label.
   */
  import '@craftcms/ui/components/tooltip/tooltip';
  import {t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {
    ActionItemButton,
    ActionItemLink,
    ActionItems,
  } from '@/common/types';
  import {sideIcons, type CompareSide} from '@/modules/elements/compareLayout';

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
     * Whether the site and the version are the same on both sides. A label
     * that is gives way to the other when they can't both fit, since it tells
     * the sides apart less.
     */
    shared?: {site: boolean; version: boolean};
  }>();

  const hasSiteMenu = computed(() =>
    Boolean(props.siteMenu.length && props.siteName)
  );

  const hasVersionMenu = computed(() => props.versionMenu.length > 0);

  /**
   * The menus there are, in one: the sites, then the versions, each choice in
   * its own section when there are both.
   */
  const combinedMenu = computed<ActionItems>(() => {
    if (!hasSiteMenu.value || !hasVersionMenu.value) {
      return hasSiteMenu.value ? props.siteMenu : props.versionMenu;
    }

    return [
      {
        type: 'group',
        heading: t('Sites'),
        items: props.siteMenu.filter(
          (item): item is ActionItemButton | ActionItemLink =>
            item.type === undefined ||
            item.type === 'button' ||
            item.type === 'link'
        ),
      },
      {type: 'hr'},
      ...props.versionMenu.map((item, index) =>
        index === 0 && item.type === 'group' && !item.heading
          ? {...item, heading: t('Versions')}
          : item
      ),
    ];
  });

  const combinedMenuName = computed(() => {
    if (hasSiteMenu.value && hasVersionMenu.value) {
      return t('Choose a site or version');
    }

    return hasSiteMenu.value ? t('Choose a site') : t('Choose a version');
  });

  type ButtonKey = 'site' | 'version' | 'combined' | 'label' | 'narrowLabel';

  const buttons: Record<
    ButtonKey,
    Readonly<ReturnType<typeof useTemplateRef<HTMLElement>>>
  > = {
    site: useTemplateRef<HTMLElement>('siteButton'),
    version: useTemplateRef<HTMLElement>('versionButton'),
    combined: useTemplateRef<HTMLElement>('combinedButton'),
    label: useTemplateRef<HTMLElement>('label'),
    narrowLabel: useTemplateRef<HTMLElement>('narrowLabel'),
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
      (button?.classList.contains('compare-version-header__part') &&
        clipped(button)) ||
      clipped(
        button?.shadowRoot?.querySelector<HTMLElement>('[part="label"]')
      ) ||
      [
        ...(button?.querySelectorAll<HTMLElement>(
          '.compare-version-header__part'
        ) ?? []),
      ].some(clipped)
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
  <div
    class="compare-version-header"
    :class="{
      'compare-version-header--shared-site': shared?.site && !shared.version,
      'compare-version-header--shared-version': shared?.version && !shared.site,
    }"
  >
    <span v-if="hasSiteMenu" class="compare-version-header__wide">
      <ActionMenu
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
            icon="world"
            flush="inline-start"
          >
            {{ siteName }}
            <span class="sr-only">{{ t('Choose a site') }}</span>
            <craft-icon slot="suffix" name="chevron-down"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
      <span class="compare-version-header__separator" aria-hidden="true"
        >/</span
      >
    </span>
    <h2 :id="headingId" class="compare-version-header__title">
      <span class="sr-only">{{
        side === 'from' ? t('Compared with:') : t('Showing:')
      }}</span>
      <span class="compare-version-header__wide">
        <!-- Keyed on everything its button shows: the action menu renders
             its invoker once, so a change means a new menu. -->
        <ActionMenu
          v-if="hasVersionMenu"
          :key="`${side}-${label}-${edited}`"
          :label="label"
          :actions="versionMenu"
        >
          <template #invoker>
            <craft-button
              ref="versionButton"
              slot="invoker"
              type="button"
              variant="plain"
              size="small"
              :flush="hasSiteMenu ? null : 'inline-start'"
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
          v-else
          :id="`${headingId}-label`"
          ref="label"
          class="compare-version-header__part compare-version-header__label"
          :class="{'compare-version-header__label--after-site': hasSiteMenu}"
          >{{ label }}</span
        >
      </span>
      <span class="compare-version-header__narrow">
        <ActionMenu
          v-if="hasSiteMenu || hasVersionMenu"
          :key="`${side}-${siteName}-${label}-${edited}`"
          :label="hasSiteMenu ? `${siteName} / ${label}` : label"
          :actions="combinedMenu"
        >
          <template #invoker>
            <craft-button
              ref="combinedButton"
              slot="invoker"
              class="compare-version-header__combined"
              type="button"
              variant="plain"
              size="small"
              :icon="sideIcons[side]"
              flush="inline"
            >
              <span class="compare-version-header__parts">
                <template v-if="hasSiteMenu">
                  <span
                    class="compare-version-header__part compare-version-header__part--site"
                    >{{ siteName }}</span
                  >
                  <span
                    class="compare-version-header__slash"
                    aria-hidden="true"
                    v-text="' / '"
                  ></span>
                </template>
                <span
                  class="compare-version-header__part compare-version-header__part--version"
                  >{{ label }}</span
                >
              </span>
              <span class="sr-only">{{ combinedMenuName }}</span>
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
        <span v-else class="compare-version-header__label-with-icon">
          <craft-icon :name="sideIcons[side]" aria-hidden="true"></craft-icon>
          <span
            :id="`${headingId}-narrow-label`"
            ref="narrowLabel"
            class="compare-version-header__part compare-version-header__label"
            >{{ label }}</span
          >
        </span>
      </span>
    </h2>
    <craft-tooltip v-if="truncatedIds.site" :for="truncatedIds.site">{{
      siteName
    }}</craft-tooltip>
    <craft-tooltip v-if="truncatedIds.version" :for="truncatedIds.version">{{
      label
    }}</craft-tooltip>
    <craft-tooltip v-if="truncatedIds.combined" :for="truncatedIds.combined">{{
      hasSiteMenu ? `${siteName} / ${label}` : label
    }}</craft-tooltip>
    <craft-tooltip v-if="truncatedIds.label" :for="truncatedIds.label">{{
      label
    }}</craft-tooltip>
    <craft-tooltip
      v-if="truncatedIds.narrowLabel"
      :for="truncatedIds.narrowLabel"
      >{{ label }}</craft-tooltip
    >
  </div>
</template>

<style scoped>
  .compare-version-header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    min-width: 0;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--cp-container-padding, var(--c-spacing-lg));
  }

  .compare-version-header__wide {
    display: contents;
  }

  .compare-version-header__narrow {
    display: none;
  }

  @container compare (width < 48rem) {
    .compare-version-header__wide {
      display: none;
    }

    .compare-version-header__narrow {
      display: contents;
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

  .compare-version-header__combined::part(prefix),
  .compare-version-header__label-with-icon craft-icon {
    font-size: var(--c-text-base);
  }

  .compare-version-header__label {
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-medium);
  }

  .compare-version-header__label--after-site {
    padding-inline-start: var(--c-spacing-xs);
  }

  .compare-version-header__label-with-icon {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    min-width: 0;
  }

  .compare-version-header__label-with-icon craft-icon {
    flex: none;
  }

  .compare-version-header__parts {
    display: flex;
    min-width: 0;
  }

  .compare-version-header__part {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .compare-version-header--shared-site
    :is(.compare-version-header__site, .compare-version-header__part--site),
  .compare-version-header--shared-version
    :is(
      .compare-version-header__title,
      .compare-version-header__part--version
    ) {
    flex-shrink: 1000;
    min-width: 2em;
  }

  /* Decorative, so lighter than any text. */
  .compare-version-header__slash,
  .compare-version-header__separator {
    flex: none;
    color: color-mix(in srgb, var(--c-text-quiet) 50%, transparent);
  }

  .compare-version-header__slash {
    white-space: pre;
  }

  .compare-version-header__title {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
    margin: 0;
    font-size: inherit;
    font-weight: inherit;
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
