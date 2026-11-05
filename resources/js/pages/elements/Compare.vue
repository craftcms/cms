<script setup lang="ts">
  /**
   * Two versions of an entry as two read-only editor panes side by side: a
   * draft and the current entry, two revisions, or the entry in two sites.
   * Both forms list their fields in the same order, and each field is pushed
   * down to line up with the same field on the other side. When the newer
   * version can be edited, its pane is its editor.
   */
  import {t} from '@craftcms/ui';
  import {useResizeObserver} from '@vueuse/core';
  import {computed, nextTick, shallowRef, useTemplateRef, watch} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ActionItems} from '@/common/types';
  import CompareEditor from '@/modules/elements/components/CompareEditor.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import NestedEntryLinks from '@/modules/ui/NestedEntryLinks.vue';
  import type {NestedEntryScope} from '@/modules/ui/nestedEntryLinks';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiPreview} from '@/modules/ui/uiPreview';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
  import type {UiPayload, UiValues} from '@/modules/ui/types';

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
  }>();

  // Each pane’s values are its own, not the page’s.
  provideUiValueGroup();
  provideUiPreview();

  const hideUnchanged = shallowRef(false);

  /** Whether the editable side has unsaved edits, as the editor's “Edited” badge shows. */
  const edited = shallowRef(false);

  const visibleRows = computed(() =>
    hideUnchanged.value
      ? props.rows.filter((row) => row.changed !== false)
      : props.rows
  );

  const hiddenCount = computed(
    () => props.rows.length - visibleRows.value.length
  );

  /** Announced when fields are hidden or shown, as well as on load. */
  const summary = computed(() =>
    [
      t(
        '{count, plural, =0{No fields changed.} =1{# field changed.} other{# fields changed.}}',
        {count: props.rows.filter((row) => row.changed).length}
      ),
      hiddenCount.value
        ? t(
            '{count, plural, =1{# unchanged field is hidden.} other{# unchanged fields are hidden.}}',
            {count: hiddenCount.value}
          )
        : '',
    ]
      .filter(Boolean)
      .join(' ')
  );

  /** A side’s form, with only the fields that are showing. */
  function paneUi(side: Side, key: 'fromUid' | 'toUid'): UiPayload {
    const uids = new Set(visibleRows.value.map((row) => row[key]));

    return {
      ...side.ui,
      nodes: side.ui.nodes.filter((node) =>
        uids.has(String(node.props.layoutUid ?? node.uid))
      ),
    };
  }

  const panes = computed(() => [
    {key: 'from' as const, side: props.from, ui: paneUi(props.from, 'fromUid')},
    {key: 'to' as const, side: props.to, ui: paneUi(props.to, 'toUid')},
  ]);

  const editorUids = computed(() =>
    visibleRows.value.flatMap((row) => (row.toUid ? [row.toUid] : []))
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
    return uid
      ? pane.querySelector<HTMLElement>(
          `[data-layout-element="${CSS.escape(uid)}"]`
        )
      : null;
  }

  /** Each field on both sides, for connecting their nested entries. */
  function linkScopes(): NestedEntryScope[] {
    const [from, to] = paneElements.value ?? [];

    return from && to
      ? visibleRows.value.map((row) => [
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

    for (const row of visibleRows.value) {
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

  watch(visibleRows, () => nextTick(alignFields), {flush: 'post'});
</script>

<template>
  <div class="compare">
    <div class="compare__toolbar">
      <p class="compare__summary" role="status">{{ summary }}</p>
      <craft-switch
        :label="t('Hide unchanged fields')"
        .checked="hideUnchanged"
        @change="hideUnchanged = ($event.target as HTMLInputElement).checked"
      ></craft-switch>
      <CpLink v-if="editUrl" :href="editUrl">
        {{ t('Exit comparison') }}
      </CpLink>
    </div>

    <div class="compare__panes">
      <section
        v-for="{key, side, ui} in panes"
        :key="key"
        ref="pane"
        class="compare__pane"
        :aria-labelledby="`compare-${key}-title`"
      >
        <div class="compare__header">
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
                  flush="inline-start"
                  class="compare__version"
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
          <ActionMenu
            v-if="side.siteMenu.length && side.siteName"
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
                icon="globe"
              >
                {{ side.siteName }}
                <span class="sr-only">{{ t('Choose a site') }}</span>
                <craft-icon slot="suffix" name="chevron-down"></craft-icon>
              </craft-button>
            </template>
          </ActionMenu>
        </div>

        <div class="py-lg">
          <CpContainer>
            <CompareEditor
              v-if="key === 'to' && side.editor"
              :editor="side.editor"
              :uids="editorUids"
              :changed="changedByUid"
              @edited="edited = $event"
            >
              <template #empty>
                <p class="compare__empty">{{ t('No fields changed.') }}</p>
              </template>
            </CompareEditor>
            <craft-field-group v-else-if="ui.nodes.length">
              <UiRenderer :payload="ui" />
            </craft-field-group>
            <p v-else class="compare__empty">{{ t('No fields changed.') }}</p>
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

  .compare__toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm) var(--c-spacing-lg);
    align-items: center;
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding, var(--c-spacing-lg));
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__summary {
    margin: 0;
    margin-inline-end: auto;
    color: var(--c-text-quiet);
  }

  .compare__panes {
    position: relative;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 24rem), 1fr));
  }

  .compare__pane {
    min-width: 0;
  }

  .compare__pane + .compare__pane {
    border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__header {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    align-items: center;
    justify-content: space-between;
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--cp-container-padding, var(--c-spacing-lg));
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__title {
    margin: 0;
    font-size: var(--c-text-lg);
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

  .compare__version {
    font-size: inherit;
    font-weight: inherit;
  }

  .compare__empty {
    margin: 0;
    color: var(--c-text-quiet);
  }
</style>
