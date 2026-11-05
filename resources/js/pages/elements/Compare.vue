<script setup lang="ts">
  /**
   * Two versions of an entry as two read-only editor panes side by side: a
   * draft and the current entry, two revisions, or the entry in two sites.
   * Both forms list their fields in the same order, and each field is pushed
   * down to line up with the same field on the other side. When the newer
   * version can be edited, its pane is its editor.
   */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import {computed, nextTick, shallowRef, useTemplateRef, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {ActionItems} from '@/common/types';
  import CompareEditor from '@/modules/elements/components/CompareEditor.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import NestedEntryLinks from '@/modules/ui/NestedEntryLinks.vue';
  import type {NestedEntryScope} from '@/modules/ui/nestedEntryLinks';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiPreview} from '@/modules/ui/uiPreview';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
  import type {UiNodePayload, UiPayload, UiValues} from '@/modules/ui/types';

  interface Side {
    label: string;
    /** Set when the entry is in more than one site the user can edit. */
    siteName: string | null;
    versionMenu: ActionItems;
    siteMenu: ActionItems;
    ui: UiPayload;
    /** The edit screen's payload, when this side can be edited. */
    editor?: (ElementEditPayload & {saveParams: UiValues}) | null;
  }

  type TabStrip = HTMLElement & {selectedIndex: number};

  interface Row {
    fromUid: string | null;
    toUid: string | null;
    /** `null` when it can’t be told, such as for Matrix fields shown as cards. */
    changed: boolean | null;
  }

  const props = defineProps<{
    from: Side;
    to: Side;
    rows: Row[];
    editUrl: string | null;
    /** The same comparison with the two sides swapped. */
    reverseUrl: string | null;
    title: string;
  }>();

  // Each pane’s values are its own, not the page’s.
  provideUiValueGroup();
  provideUiPreview();

  /** Whether the editable side has unsaved edits, as the editor's “Edited” badge shows. */
  const edited = shallowRef(false);

  /** Whether a form starts with a tab strip, which sits flush with its top, as in the editor. */
  const hasTabStrip = (ui: UiPayload) =>
    ui.nodes.filter((node) => node.component === 'craft:tab').length > 1;

  const hasUntabbedFields = (ui: UiPayload) =>
    ui.nodes.some((node) => node.component !== 'craft:tab');

  const panes = computed(() => [
    {key: 'from' as const, side: props.from, ui: props.from.ui},
    {key: 'to' as const, side: props.to, ui: props.to.ui},
  ]);

  const editorUids = computed(() =>
    props.rows.flatMap((row) => (row.toUid ? [row.toUid] : []))
  );

  const changedByUid = computed(() =>
    Object.fromEntries(
      props.rows.flatMap((row) => (row.toUid ? [[row.toUid, row.changed]] : []))
    )
  );

  const paneElements = useTemplateRef<HTMLElement[]>('pane');

  /**
   * Pushes the higher of each pair of fields down to the lower one, in order,
   * so every field starts level with the same field on the other side. Panes
   * that are stacked rather than side by side are left alone.
   */
  function field(pane: HTMLElement, uid: string | null): HTMLElement | null {
    const element = uid
      ? pane.querySelector<HTMLElement>(
          `[data-layout-element="${CSS.escape(uid)}"]`
        )
      : null;

    // A field in a tab that isn't selected has nothing to line up or connect.
    return element?.getClientRects().length ? element : null;
  }

  /** A pane's own tab strip, not one in a nested entry's form. */
  function tabStrip(pane: HTMLElement): TabStrip | null {
    return (
      [...pane.querySelectorAll<TabStrip>('craft-tabs')].find(
        (strip) => !strip.parentElement?.closest('[data-layout-element]')
      ) ?? null
    );
  }

  /** Identifies a tab across versions: its layout tab's UID, or failing that, its label. */
  function tabKeys(tab: Element): string[] {
    const panel = document.getElementById(tab.getAttribute('controls') ?? '');
    const uid = (
      panel?.closest<HTMLElement>('[data-layout-tab]') ??
      panel?.querySelector<HTMLElement>('[data-layout-tab]')
    )?.dataset.layoutTab;

    return [uid ? `uid:${uid}` : '', `label:${tab.textContent?.trim()}`];
  }

  /**
   * Selects the same tab on the other side as the one just selected, so both
   * sides show the same fields, side by side.
   */
  function syncTabs(event: Event): void {
    const source = event.target as TabStrip;
    const panes = paneElements.value ?? [];
    const sourcePane = panes.find((pane) => tabStrip(pane) === source);
    const selected = sourcePane
      ? [...source.querySelectorAll(':scope > craft-tab')][source.selectedIndex]
      : undefined;

    if (!selected) {
      return;
    }

    const [uid, label] = tabKeys(selected);

    for (const pane of panes) {
      const strip = pane === sourcePane ? null : tabStrip(pane);
      const tabs = strip
        ? [...strip.querySelectorAll(':scope > craft-tab')].map(tabKeys)
        : [];
      const index = [uid, label].reduce(
        (found, key) =>
          found >= 0 || !key
            ? found
            : tabs.findIndex((keys) => keys.includes(key)),
        -1
      );

      if (strip && index >= 0 && strip.selectedIndex !== index) {
        strip.selectedIndex = index;
      }
    }

    // After both panes have rendered the tab they switched to.
    requestAnimationFrame(alignFields);
  }

  /** Each field on both sides, for connecting their nested entries. */
  function linkScopes(): NestedEntryScope[] {
    const [from, to] = paneElements.value ?? [];

    return from && to
      ? props.rows.map((row) => [
          field(from, row.fromUid),
          field(to, row.toUid),
        ])
      : [];
  }

  function alignFields(): void {
    const [from, to] = paneElements.value ?? [];

    if (!from || !to) {
      return;
    }

    for (const element of [from, to].flatMap((pane) => [
      ...pane.querySelectorAll<HTMLElement>('[data-compare-pushed]'),
    ])) {
      element.style.marginBlockStart = '';
      delete element.dataset.comparePushed;
    }

    if (from.offsetTop !== to.offsetTop) {
      return;
    }

    for (const row of props.rows) {
      const a = field(from, row.fromUid);
      const b = field(to, row.toUid);

      if (!a || !b) {
        continue;
      }

      const offset =
        b.getBoundingClientRect().top - a.getBoundingClientRect().top;
      const higher = offset > 0 ? a : b;

      if (Math.abs(offset) >= 1) {
        higher.style.marginBlockStart = `${
          parseFloat(getComputedStyle(higher).marginBlockStart) +
          Math.abs(offset)
        }px`;
        higher.dataset.comparePushed = '';
      }
    }
  }

  useResizeObserver(
    () => paneElements.value ?? [],
    () => alignFields()
  );

  watch(
    () => props.rows,
    () => nextTick(alignFields),
    {flush: 'post'}
  );
</script>

<template>
  <div class="compare">
    <LayoutSlot name="title">
      <div class="flex gap-lg items-center min-w-0">
        <CpButtonLink
          v-if="editUrl"
          :href="editUrl"
          size="small"
          icon="arrow-left"
          flush="inline-start"
          :variant="ButtonVariant.Plain"
        >
          {{ t('Back') }}
        </CpButtonLink>
        <h1 class="text-xl">{{ title }}</h1>
      </div>
    </LayoutSlot>

    <div class="compare__panes" @craft-tab-show.capture="syncTabs">
      <section
        v-for="{key, side, ui} in panes"
        :key="key"
        ref="pane"
        class="compare__pane"
        :aria-labelledby="`compare-${key}-title`"
      >
        <div class="compare__header">
          <template v-if="key === 'to' && reverseUrl">
            <CpButtonLink
              id="compare-reverse"
              :href="reverseUrl"
              icon=""
              size="small"
              :variant="ButtonVariant.Outline"
              class="compare__reverse"
            >
              <craft-icon
                name="arrow-right-arrow-left"
                :label="t('Reverse comparison')"
              ></craft-icon>
            </CpButtonLink>
            <craft-tooltip for="compare-reverse">
              {{ t('Reverse comparison') }}
            </craft-tooltip>
          </template>
          <div class="compare__versions">
            <template v-if="side.siteMenu.length && side.siteName">
              <ActionMenu
                :key="`${key}-site-${side.siteName}`"
                :label="side.siteName"
                :actions="side.siteMenu"
              >
                <template #invoker>
                  <craft-button
                    slot="invoker"
                    type="button"
                    variant="plain"
                    size="small"
                    icon="world"
                    flush="inline-start"
                  >
                    {{ side.siteName }}
                    <span class="sr-only">{{ t('Choose a site') }}</span>
                    <craft-icon slot="suffix" name="chevron-down"></craft-icon>
                  </craft-button>
                </template>
              </ActionMenu>
              <span class="compare__separator" aria-hidden="true">/</span>
            </template>
            <h2 :id="`compare-${key}-title`" class="compare__title">
              <span class="sr-only">{{
                key === 'from' ? t('Compared with:') : t('Showing:')
              }}</span>
              <ActionMenu
                :key="`${key}-${side.label}`"
                :label="side.label"
                :actions="side.versionMenu"
              >
                <template #invoker>
                  <craft-button
                    slot="invoker"
                    type="button"
                    variant="plain"
                    size="small"
                    :flush="
                      side.siteMenu.length && side.siteName
                        ? null
                        : 'inline-start'
                    "
                  >
                    {{ side.label }}
                    <span class="sr-only">{{ t('Choose a version') }}</span>
                    <craft-icon slot="suffix" name="chevron-down"></craft-icon>
                  </craft-button>
                </template>
              </ActionMenu>
              <craft-badge
                v-if="key === 'to' && edited"
                fill="violet"
                class="compare__edited"
              >
                <craft-icon name="pen-circle" slot="prefix"></craft-icon>
                {{ t('Edited') }}
              </craft-badge>
            </h2>
          </div>
        </div>

        <div class="compare__body" :class="hasTabStrip(ui) ? 'pb-lg' : 'py-lg'">
          <CpContainer>
            <CompareEditor
              v-if="key === 'to' && side.editor"
              :editor="side.editor"
              :uids="editorUids"
              :changed="changedByUid"
              @edited="edited = $event"
            />
            <component
              :is="hasUntabbedFields(ui) ? 'craft-field-group' : 'div'"
              v-else-if="ui.nodes.length"
            >
              <UiRenderer :payload="ui" />
            </component>
          </CpContainer>
        </div>
      </section>
      <NestedEntryLinks :scopes="linkScopes" />
    </div>
  </div>
</template>

<style scoped>
  .compare {
    display: flex;
    flex-direction: column;
  }

  .compare__panes {
    container: compare-panes / inline-size;
    position: relative;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 24rem), 1fr));
  }

  .compare__pane {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .compare__body {
    flex: 1;
  }

  .compare__pane + .compare__pane .compare__header {
    position: relative;
  }

  .compare__reverse {
    position: absolute;
    inset-inline-start: 0;
    inset-block-start: 50%;
    translate: -50% -50%;
    z-index: 2;
  }

  :dir(rtl) .compare__reverse {
    translate: 50% -50%;
  }

  @container compare-panes (width < 48rem) {
    .compare__reverse {
      position: static;
      translate: none;
    }
  }

  @container compare-panes (width >= 48rem) {
    .compare__pane {
      --compare-gutter: calc(var(--c-size-control-sm) / 2);
    }

    .compare__pane:first-child .compare__body {
      padding-inline-end: var(--compare-gutter);
    }

    .compare__pane:first-child .compare__header {
      padding-inline-end: calc(
        var(--cp-container-padding, var(--c-spacing-lg)) + var(--compare-gutter)
      );
    }

    .compare__pane + .compare__pane .compare__body {
      padding-inline-start: var(--compare-gutter);
    }

    .compare__pane + .compare__pane .compare__header {
      padding-inline-start: calc(
        var(--cp-container-padding, var(--c-spacing-lg)) + var(--compare-gutter)
      );
    }
  }

  .compare__pane + .compare__pane .compare__body {
    border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__header {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    align-items: center;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--cp-container-padding, var(--c-spacing-lg));
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__versions {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-xs);
    min-width: 0;
  }

  .compare__separator {
    color: var(--c-text-quiet);
  }

  .compare__title {
    margin: 0;
    font-size: inherit;
    font-weight: inherit;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    align-items: center;
  }

  .compare__edited {
    font-size: var(--c-text-sm);
    font-weight: normal;
  }
</style>

<style>
  /* The footer is the page shell's, so it's reached from here. */
  .cp-content__main:has(.compare__panes) {
    container: compare-page / inline-size;
  }

  @container compare-page (width >= 48rem) {
    .cp-content__main:has(.compare__panes) > .sticky::before {
      content: '';
      position: absolute;
      inset-block: calc(var(--c-spacing-lg) * -1) 0;
      inset-inline-start: 50%;
      border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
      pointer-events: none;
    }

    .cp-content__main:has(.compare__panes) .cp-content__footer {
      padding-inline-start: calc(
        50% + 1px + var(--cp-container-padding) + var(--c-size-control-sm) / 2
      );
    }
  }
</style>
