<script setup lang="ts">
  /**
   * Two versions of an entry side by side: a draft and the current entry, two
   * revisions, or the entry in two sites. The screen lays out a row for each
   * field, within the newer version's tabs. Each side still renders its own
   * form, out of sight, and each of its fields renders in the field's row, so
   * the page reads and tabs through the rows in order. When the newer version
   * can be edited, its form is its editor. Like Live Preview, the screen
   * takes over the whole window until it's closed.
   */
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/indicator/indicator';
  import {router} from '@inertiajs/vue3';
  import {useElementSize} from '@vueuse/core';
  import {ButtonVariant, create, t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    shallowReactive,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {ActionItems} from '@/common/types';
  import CompareEditor from '@/modules/elements/components/CompareEditor.vue';
  import CompareVersionHeader from '@/modules/elements/components/CompareVersionHeader.vue';
  import {useFullScreenPanel} from '@/modules/elements/composables/useFullScreenPanel';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import {
    compareTabs,
    copyableValue,
    fieldNodes,
    layoutUid,
    rememberValues,
    liveRows,
    tabsWithChanges,
    tabsWithErrors,
    withServerTabFields,
    withoutCompareActions,
    sideIcons,
    type CompareRow,
    type CompareSide,
    type CompareTab,
  } from '@/modules/elements/compareLayout';
  import {showNotice, type CpNotification} from '@/modules/messages/messages';
  import type {UiFieldOutlets} from '@/modules/ui/fieldOutlets';
  import type {UiValueSnapshot} from '@/modules/ui/valueAccess';
  import NestedEntryLinks from '@/modules/ui/NestedEntryLinks.vue';
  import type {NestedEntryScope} from '@/modules/ui/nestedEntryLinks';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {provideUiPreview} from '@/modules/ui/uiPreview';
  import {provideUiValueGroup} from '@/modules/ui/uiValueGroup';
  import type {UiPayload, UiValue, UiValues} from '@/modules/ui/types';

  interface Side {
    label: string;
    /** Whether it's the user's unsaved edits to the current version. */
    edited: boolean;
    /** Set when the entry is in more than one site the user can edit. */
    siteName: string | null;
    versionMenu: ActionItems;
    siteMenu: ActionItems;
    ui: UiPayload;
    /** The edit screen's payload, when this side can be edited. */
    editor?: (ElementEditPayload & {saveParams: UiValues}) | null;
  }

  type TabStrip = HTMLElement & {selectedIndex: number};

  const props = defineProps<{
    from: Side;
    to: Side;
    rows: CompareRow[];
    /**
     * The sides that badge their changed fields: the one that made the
     * changes, when one did, and otherwise both.
     */
    badged: Record<CompareSide, boolean>;
    editUrl: string | null;
    /** The element's chip, as server-rendered HTML, for the heading. */
    elementChip: string;
    /** The same comparison with the two sides swapped. */
    reverseUrl: string | null;
    title: string;
  }>();

  // Each side's values are its own, not the page's.
  provideUiValueGroup();
  provideUiPreview();

  /** Whether the editable side has unsaved edits, as the editor's “Edited” badge shows. */
  const edited = shallowRef(false);

  /** The layout UIDs of the editable side's fields with errors. */
  const erroredUids = shallowRef<string[]>([]);
  /** The fields edited on this screen: each one's value, as a canonical string, by layout UID. */
  const editedValues = shallowRef<Record<string, string>>({});

  /** Each of a side's fields' values as the page loaded them, as canonical strings, by layout UID. */
  function loadedValues(ui: UiPayload): Map<string, string> {
    const values = new Map<string, string>();
    rememberValues(ui.nodes, ui.values, values);

    return values;
  }

  /** The older version's value of each of its fields. */
  const fromValues = computed(() => loadedValues(props.from.ui));

  /**
   * The newer version's value of each of its fields as the page loaded it,
   * which is what a field holds when it isn't among the `editedValues`. The
   * editor's own form moves on as it autosaves, so it can't say that.
   */
  const toLoadedValues = computed(() => loadedValues(props.to.ui));

  /**
   * Whether each field edited on this screen differs from the older version's,
   * whatever the server found when the page loaded: a field edited to match,
   * as by copying it across, no longer counts as changed.
   */
  const editedChanged = computed(
    () =>
      new Map(
        serverRows().flatMap((row) =>
          row.toUid !== null && row.toUid in editedValues.value
            ? [
                [
                  row.toUid,
                  row.fromUid === null ||
                    editedValues.value[row.toUid] !==
                      fromValues.value.get(row.fromUid),
                ] as [string, boolean],
              ]
            : []
        )
      )
  );

  const sides = computed(() => [
    {key: 'from' as const, side: props.from},
    {key: 'to' as const, side: props.to},
  ]);

  /** The editable side's form as it stands, once its editor reports it. */
  const liveToUi = shallowRef<UiPayload | null>(null);
  /** Whether the newer version can't be edited for now, as while it's in review. */
  const editorLocked = shallowRef(false);
  /** The newer version's editor, in a list as it's rendered in a loop over the sides. */
  const editor =
    useTemplateRef<Array<InstanceType<typeof CompareEditor>>>('editor');

  /** The newer version's form: the editor's as it stands, or the server's. */
  const toUi = computed(() =>
    liveToUi.value
      ? withServerTabFields(liveToUi.value, props.to.ui)
      : props.to.ui
  );

  /** The comparison's rows, following conditions as the editable side changes. */
  /** The rows as the server compared them, following the newer version's form as it changes. */
  function serverRows(): CompareRow[] {
    return liveToUi.value ? liveRows(props.rows, toUi.value) : props.rows;
  }

  const compareRows = computed(() =>
    serverRows().map((row) =>
      row.toUid !== null && editedChanged.value.has(row.toUid)
        ? {...row, changed: editedChanged.value.get(row.toUid)!}
        : row
    )
  );

  /** Which of the two labels each side shows is the same on both, which give way first when space is short. */
  const sharedLabels = computed(() => ({
    site: props.from.siteName === props.to.siteName,
    version: props.from.label === props.to.label,
  }));

  /** Each read-only side's form, without the fields' “Compare changes” action. */
  const sideForms = computed(() => ({
    from: withoutCompareActions(props.from.ui),
    to: withoutCompareActions(props.to.ui),
  }));

  const tabs = computed(() =>
    compareTabs(props.from.ui, toUi.value, compareRows.value)
  );

  /** The tab the screen shows, by key, which stays selected as tabs come and go. */
  const selectedKey = shallowRef<string | null>(null);
  const selectedTab = computed(
    () =>
      tabs.value.find((tab) => tab.key === selectedKey.value) ?? tabs.value[0]
  );

  /** The first of the sidebar tabs, after the others, which starts their group at the end of each strip. */
  const firstSidebarTabKey = computed(
    () =>
      tabs.value.find(
        (tab, index) =>
          index > 0 && tab.sidebar && !tabs.value[index - 1]!.sidebar
      )?.key
  );

  /** The tabs holding a field with errors, which are marked as the editor's tabs are. */
  const erroredTabs = computed(() =>
    tabsWithErrors(tabs.value, compareRows.value, erroredUids.value)
  );

  /**
   * The older version's value for each changed row whose field the newer
   * version can take it as it is, by row index, while the newer one is being
   * edited here.
   */
  const copyValues = computed(
    () =>
      new Map(
        props.to.editor && !editorLocked.value
          ? compareRows.value.flatMap((row, index) => {
              const value = copyableValue(
                row,
                props.from.ui,
                toUi.value,
                row.toUid === null
                  ? undefined
                  : (editedValues.value[row.toUid] ??
                      toLoadedValues.value.get(row.toUid))
              );

              return value === undefined
                ? []
                : [[index, value] as [number, UiValueSnapshot]];
            })
          : []
      )
  );

  function fieldLabel(rowIndex: number): string {
    const uid = compareRows.value[rowIndex]?.toUid;
    const label = fieldNodes(toUi.value.nodes).find(
      (node) => layoutUid(node) === uid
    )?.props.label;

    return typeof label === 'string' ? label : '';
  }

  /** Copies the older version's value for a row into the newer version's field. */
  function copyFromOlder(rowIndex: number): void {
    const uid = compareRows.value[rowIndex]?.toUid;

    if (!uid || !copyValues.value.has(rowIndex)) {
      return;
    }

    const copied = editor.value?.[0]?.copyValue(
      uid,
      copyValues.value.get(rowIndex)!
    );

    if (copied) {
      announceCopy(uid, copied.previous);
    }
  }

  /**
   * The notice of the last copy, offering to undo it while it still can: it's
   * closed by another copy, or once the field changes again, so Undo never
   * throws away anything entered after the copy.
   */
  let undoOffer: {
    uid: string;
    value: string | undefined;
    notice: CpNotification | null;
  } | null = null;

  function closeUndoOffer(): void {
    undoOffer?.notice?.close();
    undoOffer = null;
  }

  /**
   * Says a field's value was copied, offering to put back what it had before,
   * when that could be read.
   */
  function announceCopy(uid: string, previous: UiValueSnapshot | null): void {
    closeUndoOffer();

    if (!previous) {
      showNotice(t('Field value copied.'));
      return;
    }

    const undoButton = create('craft-button', {type: 'button'}, t('Undo'));
    const offer = {
      uid,
      value: editedValues.value[uid],
      notice: showNotice(t('Field value copied.'), {details: undoButton}),
    };
    undoOffer = offer;

    undoButton.addEventListener('click', () => {
      if (undoOffer !== offer) {
        return;
      }

      closeUndoOffer();
      editor.value?.[0]?.copyValue(uid, previous);
    });
  }

  watch(editedValues, (values) => {
    if (undoOffer && values[undoOffer.uid] !== undoOffer.value) {
      closeUndoOffer();
    }
  });

  onBeforeUnmount(closeUndoOffer);

  /** The tabs holding a changed field, which are marked on the sides that badge their fields. */
  const changedTabs = computed(() =>
    tabsWithChanges(tabs.value, compareRows.value)
  );

  /**
   * The tabs holding a field edited on this screen. Edits are the newer
   * side's own, so they're marked there whichever side the rest are.
   */
  const editedTabs = computed(() =>
    tabsWithChanges(
      tabs.value,
      compareRows.value.map((row) => ({
        ...row,
        changed:
          row.toUid !== null && editedChanged.value.get(row.toUid) === true,
      }))
    )
  );

  /** The newer side's badged fields: the changes when it's badged, and its own edits either way. */
  const toBadges = computed(() =>
    props.badged.to
      ? changedByUid.value
      : Object.fromEntries(
          [...editedChanged.value].filter(([, changed]) => changed)
        )
  );

  const changedByUid = computed(() =>
    Object.fromEntries(
      compareRows.value.flatMap((row) =>
        row.toUid ? [[row.toUid, row.changed]] : []
      )
    )
  );

  /** Each row's cell for each side, which the side's field renders into. */
  const cells = shallowReactive(new Map<string, HTMLElement>());
  const cellKey = (side: CompareSide, uid: string) => `${side}:${uid}`;
  const rowUid = (rowIndex: number, side: CompareSide) =>
    compareRows.value[rowIndex]?.[side === 'from' ? 'fromUid' : 'toUid'] ??
    null;

  /** Identifies a row by its fields, so its cells stay its own as rows come and go. */
  const rowKey = (rowIndex: number) =>
    `${rowUid(rowIndex, 'from') ?? ''}|${rowUid(rowIndex, 'to') ?? ''}`;

  const cellRefs = new Map<string, (element: unknown) => void>();

  /** The ref for a row's cell on one side, the same function each render. */
  function cellRef(side: CompareSide, uid: string | null) {
    if (!uid) {
      return undefined;
    }

    const key = cellKey(side, uid);
    let ref = cellRefs.get(key);

    if (!ref) {
      let current: HTMLElement | null = null;

      ref = (element: unknown) => {
        if (element instanceof HTMLElement) {
          current = element;
          cells.set(key, element);
        } else if (current && cells.get(key) === current) {
          cells.delete(key);
          current = null;
        }
      };
      cellRefs.set(key, ref);
    }

    return ref;
  }

  const fieldOutlets: Record<CompareSide, UiFieldOutlets> = {
    from: (uid) => cells.get(cellKey('from', uid)),
    to: (uid) => cells.get(cellKey('to', uid)),
  };

  const root = useTemplateRef<HTMLElement>('root');
  const panels = useTemplateRef<HTMLElement>('panels');
  /** Where the editable version's footer goes, under its side. */
  const footerNotices = useTemplateRef<HTMLElement>('footerNotices');
  const footerActions = useTemplateRef<HTMLElement>('footerActions');
  const footerTargets = computed(() => ({
    notices: footerNotices.value,
    actions: footerActions.value,
  }));

  useFullScreenPanel(root, () => {
    if (props.editUrl) {
      router.visit(props.editUrl);
    }
  });
  const headers = useTemplateRef<HTMLElement>('headers');
  const {height: headersHeight} = useElementSize(headers, undefined, {
    box: 'border-box',
  });

  /**
   * Keeps fields that focus scrolls to clear of the version menus, while
   * they're stuck to the top of the panel (WCAG 2.4.11).
   */
  watch(headersHeight, (height) => {
    const panel = root.value?.closest<HTMLElement>('.cp-body__panel');
    const sticky =
      headers.value && getComputedStyle(headers.value).position === 'sticky';

    panel?.style.setProperty(
      'scroll-padding-block-start',
      sticky ? `${height}px` : ''
    );
  });

  onBeforeUnmount(() =>
    root.value
      ?.closest<HTMLElement>('.cp-body__panel')
      ?.style.removeProperty('scroll-padding-block-start')
  );

  const forms = useTemplateRef<HTMLElement>('forms');
  const tabStrips = useTemplateRef<TabStrip[]>('tabStrips');

  /** Each side's tab strip on the screen, by side. */
  function tabStrip(side: CompareSide): TabStrip | null {
    return (
      tabStrips.value?.find((strip) => strip.dataset.compareSide === side) ??
      null
    );
  }

  const panelId = (tab: CompareTab) => `compare-tab-${tab.key}`;

  /** A side's own tab strip, out of sight with its form. */
  function formTabStrip(side: CompareSide): TabStrip | null {
    return (
      forms.value?.querySelector<TabStrip>(
        `[data-compare-side="${side}"] craft-tabs`
      ) ?? null
    );
  }

  /**
   * Selects the tab in each side's own strip that matches the screen's, so a
   * side whose tabs load when first shown loads that tab's fields.
   */
  function syncFormTabs(): void {
    const tab = selectedTab.value;

    if (!tab) {
      return;
    }

    for (const side of ['from', 'to'] as const) {
      const strip = formTabStrip(side);
      const formTabs = strip
        ? [...strip.querySelectorAll(':scope > craft-tab')]
        : [];
      const match = [
        formTabs.findIndex(
          (formTab) =>
            document.getElementById(formTab.getAttribute('controls') ?? '')
              ?.dataset.layoutTab === tab.key
        ),
        formTabs.findIndex(
          (formTab) => formTab.textContent?.trim() === tab.label
        ),
      ].find((found) => found >= 0);

      if (strip && match !== undefined && strip.selectedIndex !== match) {
        strip.selectedIndex = match;
      }
    }
  }

  /**
   * Both strips list every tab, marking the ones their version doesn't have,
   * so selecting one in either strip selects the same one in the other.
   */
  function selectTab(index: number): void {
    const tab = tabs.value[index];
    selectedKey.value = tab?.key ?? null;

    for (const side of ['from', 'to'] as const) {
      const strip = tabStrip(side);

      if (strip && strip.selectedIndex !== index) {
        strip.selectedIndex = index;
      }
    }

    syncFormTabs();
  }

  function onTabShow(event: Event): void {
    selectTab((event.target as TabStrip).selectedIndex);
  }

  onMounted(() =>
    requestAnimationFrame(() =>
      selectTab((tabStrip('to') ?? tabStrip('from'))?.selectedIndex ?? 0)
    )
  );

  // A condition can add or remove a tab, which the strip then rewires, staying
  // on the tab it was showing.
  watch(
    () => tabs.value.map((tab) => tab.key).join(),
    async () => {
      await nextTick();

      for (const strip of tabStrips.value ?? []) {
        (strip as TabStrip & {refresh?(): void}).refresh?.();
      }

      selectTab(Math.max(tabs.value.indexOf(selectedTab.value!), 0));
    }
  );

  /** A side's field for a row, if it's showing. */
  function field(side: CompareSide, uid: string | null): HTMLElement | null {
    const element = uid
      ? cells
          .get(cellKey(side, uid))
          ?.querySelector<HTMLElement>('[data-layout-element]')
      : null;

    return element?.getClientRects().length ? element : null;
  }

  /** Each field on both sides, for connecting their nested entries. */
  function linkScopes(): NestedEntryScope[] {
    return compareRows.value.map((row) => [
      field('from', row.fromUid),
      field('to', row.toUid),
    ]);
  }
</script>

<template>
  <div ref="root" class="compare">
    <LayoutSlot name="title">
      <h1
        v-if="elementChip"
        class="compare__heading text-xl"
        v-html="t('Compare {element}', {element: elementChip})"
      ></h1>
      <h1 v-else class="text-xl">{{ title }}</h1>
    </LayoutSlot>

    <LayoutSlot v-if="editUrl" name="content-actions">
      <CpButtonLink
        id="compare-close"
        :href="editUrl"
        icon=""
        size="small"
        flush="inline-end"
        :variant="ButtonVariant.Plain"
      >
        <craft-icon name="xmark-large" :label="t('Close')"></craft-icon>
      </CpButtonLink>
      <craft-tooltip for="compare-close">{{ t('Close') }}</craft-tooltip>
    </LayoutSlot>

    <div ref="headers" class="compare__headers">
      <template v-for="{key, side} in sides" :key="key">
        <CompareVersionHeader
          :side="key"
          :heading-id="`compare-${key}-title`"
          :label="side.label"
          :site-name="side.siteName"
          :version-menu="side.versionMenu"
          :site-menu="side.siteMenu"
          :edited="side.edited || (key === 'to' && edited)"
          :shared="sharedLabels"
        />
        <template v-if="key === 'from' && reverseUrl">
          <CpButtonLink
            id="compare-reverse"
            :href="reverseUrl"
            icon=""
            size="small"
            :variant="ButtonVariant.Plain"
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
      </template>
    </div>

    <div v-if="tabs.length > 1" class="compare__tabs">
      <div
        v-for="{key, side} in sides"
        :key="key"
        class="compare__tabs-cell"
        :class="`compare__tabs-cell--${key}`"
      >
        <craft-tabs
          ref="tabStrips"
          :data-compare-side="key"
          :label="t('Tabs for {version}', {version: side.label})"
          @craft-tab-show="onTabShow"
        >
          <craft-tab
            v-for="tab in tabs"
            :key="tab.key"
            slot="tab"
            :controls="panelId(tab)"
            :class="{
              'compare__tab--absent': !tab.sides[key],
              'compare__tab--sidebar-start': tab.key === firstSidebarTabKey,
            }"
          >
            <craft-icon
              v-if="tab.icon"
              :name="tab.icon"
              slot="prefix"
            ></craft-icon>
            {{ tab.label }}
            <craft-icon
              v-if="!tab.sides[key]"
              name="eye-slash"
              :label="t('Not in this version')"
              slot="suffix"
            ></craft-icon>
            <span
              v-if="
                tab.sides[key] &&
                ((badged[key] && changedTabs.has(tab.key)) ||
                  (key === 'to' && editedTabs.has(tab.key)))
              "
              slot="suffix"
              class="compare__tab-changed"
            >
              <craft-indicator
                fill="var(--c-field-modified-indicator)"
                :label="t('Changed')"
              ></craft-indicator>
            </span>
            <craft-icon
              v-if="key === 'to' && erroredTabs.has(tab.key)"
              name="circle-exclamation"
              :label="t('Errors')"
              slot="suffix"
            ></craft-icon>
          </craft-tab>
        </craft-tabs>
      </div>
    </div>

    <div ref="panels" class="compare__panels">
      <section
        v-for="tab in tabs"
        :key="tab.key"
        :id="panelId(tab)"
        class="compare__panel"
        :class="{
          hidden: tabs.length > 1 && tab.key !== selectedTab?.key,
          'compare__panel--absent-from': !tab.sides.from,
          'compare__panel--absent-to': !tab.sides.to,
        }"
      >
        <template v-for="{key, side} in sides" :key="key">
          <div
            v-if="!tab.sides[key]"
            class="compare__absent"
            :class="`compare__absent--${key}`"
          >
            <craft-empty
              :label="
                t('{version} doesn’t have this tab.', {version: side.label})
              "
            ></craft-empty>
          </div>
        </template>
        <div
          v-for="rowIndex in tab.rows"
          :key="rowKey(rowIndex)"
          class="compare__row"
          :class="{'compare__row--changed': compareRows[rowIndex]?.changed}"
        >
          <div
            v-for="{key} in sides"
            :key="key"
            :ref="cellRef(key, rowUid(rowIndex, key))"
            class="compare__cell"
            :class="`compare__cell--${key}`"
            role="group"
            :aria-labelledby="`compare-${key}-title`"
          >
            <!-- Tells stacked sides apart. Decorative: the cell is named by
                 its side's heading. -->
            <craft-icon
              :name="sideIcons[key]"
              class="compare__side-icon"
              aria-hidden="true"
            ></craft-icon>
            <p v-if="!rowUid(rowIndex, key)" class="compare__missing">
              {{ t('Not in this version') }}
            </p>
          </div>
          <span v-if="copyValues.has(rowIndex)" class="compare__copy">
            <craft-button
              :id="`compare-copy-${rowIndex}`"
              type="button"
              variant="outline"
              size="xsmall"
              icon="arrow-right"
              :aria-label="
                t('Copy {field} from {version}', {
                  field: fieldLabel(rowIndex),
                  version: from.label,
                })
              "
              @click="copyFromOlder(rowIndex)"
            ></craft-button>
            <craft-tooltip :for="`compare-copy-${rowIndex}`">{{
              t('Copy {field} from {version}', {
                field: fieldLabel(rowIndex),
                version: from.label,
              })
            }}</craft-tooltip>
          </span>
        </div>
      </section>
      <NestedEntryLinks :scopes="linkScopes" />
    </div>

    <div class="compare__footer">
      <!-- Empty, it takes no room. The notices' region stays, so they're
           announced when they arrive, as the page's own notices are. They're
           the editable version's, so they keep to its side. -->
      <div class="compare__footer-notices">
        <div
          ref="footerNotices"
          class="compare__footer-notices-cell"
          role="status"
        ></div>
      </div>
      <div ref="footerActions" class="compare__footer-actions"></div>
    </div>

    <div ref="forms" hidden>
      <div v-for="{key, side} in sides" :key="key" :data-compare-side="key">
        <CompareEditor
          v-if="key === 'to' && side.editor"
          ref="editor"
          :editor="side.editor"
          :changed="toBadges"
          :field-outlets="fieldOutlets.to"
          :root="panels"
          :footer-targets="footerTargets"
          @edited="edited = $event"
          @errors="erroredUids = $event"
          @edits="editedValues = $event"
          @locked="editorLocked = $event"
          @layout="liveToUi = $event"
        />
        <UiRenderer
          v-else-if="side.ui.nodes.length"
          :payload="sideForms[key]"
          :field-outlets="fieldOutlets[key]"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
  .compare__heading :deep(.inline-chips) {
    display: inline-flex;
    vertical-align: middle;
  }

  .compare {
    flex: 1 0 auto;
    --compare-cell-padding-inline: var(
      --cp-container-padding,
      var(--c-spacing-lg)
    );
    --compare-cell-padding-block: var(--compare-cell-padding-inline);

    container: compare / inline-size;
    display: flex;
    flex-direction: column;
  }

  .compare__headers {
    position: sticky;
    inset-block-start: 0;
    z-index: var(--c-layer-sticky);
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-surface-default);
  }

  .compare__reverse {
    position: absolute;
    inset-inline-start: 50%;
    inset-block-start: 50%;
    translate: -50% -50%;
    z-index: 2;
  }

  :dir(rtl) .compare__reverse {
    translate: 50% -50%;
  }

  .compare__tabs {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
  }

  .compare__tabs-cell {
    min-width: 0;
    padding-inline: var(--compare-cell-padding-inline);
  }

  .compare__tabs-cell craft-tabs {
    --c-tabs-gap: 0;
  }

  .compare__tabs-cell--from {
    display: none;
  }

  .compare__tab--absent {
    color: var(--c-text-quiet);
  }

  .compare__tab--sidebar-start {
    margin-inline-start: auto;
  }

  /* Keeps the dot round, where the tab would stretch it to its height. */
  .compare__tab-changed {
    display: inline-flex;
    align-items: center;
  }

  .compare__panels {
    position: relative;
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }

  .compare__panel {
    position: relative;
    flex: 1 0 auto;
    padding-block: var(--c-spacing) 0;
  }

  .compare__row {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
  }

  .compare__copy {
    display: none;
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-default);
  }

  .compare__row--changed .compare__copy {
    background-color: inherit;
  }

  .compare__copy craft-button {
    --c-form-control-border-color: var(--compare-hairline);
    --c-color-fill-quiet: var(--c-color-violet-fill-quiet);
    --c-color-on-quiet: color-mix(
      in oklab,
      var(--c-text-quiet) 70%,
      var(--c-field-modified-indicator)
    );
  }

  .compare__footer {
    position: sticky;
    inset-block-end: 0;
    z-index: var(--c-layer-sticky);
  }

  /* Only the clicks on the editable side's notices are their own. */
  .compare__footer-notices {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    pointer-events: none;
  }

  .compare__footer-notices-cell {
    min-width: 0;
    pointer-events: auto;
  }

  .compare__row + .compare__row {
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
  }

  .compare__row--changed {
    background-color: light-dark(
      color-mix(
        in oklab,
        var(--c-color-violet-fill-quiet) 40%,
        var(--c-surface-default)
      ),
      color-mix(
        in oklab,
        var(--c-color-violet-fill-quiet) 30%,
        var(--c-surface-default)
      )
    );
  }

  @media (forced-colors: active) {
    .compare__row--changed {
      border-inline-start: 4px solid Highlight;
    }
  }

  .compare__cell {
    min-width: 0;
    padding-block: var(--compare-cell-padding-block);
    padding-inline: var(--compare-cell-padding-inline);
  }

  .compare__panel:is(.compare__panel--absent-from, .compare__panel--absent-to) {
    min-height: 16rem;
  }

  .compare__absent {
    padding-block: var(--compare-cell-padding-block);
    padding-inline: var(--compare-cell-padding-inline);
  }

  .compare__panel--absent-from .compare__cell--from,
  .compare__panel--absent-to .compare__cell--to {
    display: none;
  }

  .compare__side-icon {
    display: none;
  }

  .compare__missing {
    margin: 0;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-style: italic;
  }

  @container compare (width < 48rem) {
    .compare__row {
      /* A craft-icon is 1.25em wide, as the header's side icon is. */
      --compare-side-icon-column: calc(var(--c-size-icon-md) * 1.25);
      --compare-side-indent: calc(
        var(--compare-side-icon-column) + var(--c-spacing-sm)
      );
    }

    .compare__cell {
      display: grid;
      grid-template-columns: var(--compare-side-icon-column) minmax(0, 1fr);
      column-gap: var(--c-spacing-sm);
    }

    .compare__cell > * {
      grid-column: 2;
    }

    .compare__cell > .compare__side-icon {
      display: inline-flex;
      grid-column: 1;
      grid-row: 1 / span 99;
      align-self: start;
      margin-block-start: calc((1lh - 1em) / 2);
      font-size: var(--c-size-icon-md);
    }

    .compare__panel:not(
      .compare__panel--absent-from,
      .compare__panel--absent-to
    ) {
      .compare__cell--to {
        position: relative;
        grid-area: 2 / 1;
      }

      .compare__cell--to::before {
        content: '';
        position: absolute;
        grid-area: 1 / 2 / 2 / 3;
        inset-block-start: calc(var(--compare-cell-padding-block) * -1);
        inset-inline: 0;
        border-block-start: 1px solid var(--c-color-neutral-border-quiet);
      }
    }

    .compare__copy {
      position: relative;
      z-index: 3;
      grid-area: 2 / 1;
      align-self: start;
      justify-self: center;
      display: flex;
      margin-inline-start: var(--compare-side-indent);
      translate: 0 -50%;
    }

    .compare__copy craft-button::part(prefix) {
      rotate: 90deg;
    }
  }

  @container compare (width >= 48rem) {
    .compare__tabs {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .compare__tabs-cell--from {
      display: block;
    }

    .compare__tabs-cell--to {
      border-inline-start: 1px solid var(--compare-hairline);
    }

    .compare__row {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .compare__copy {
      position: absolute;
      z-index: 3;
      inset-block-start: 50%;
      inset-inline-start: 50%;
      display: flex;
      translate: -50% -50%;
    }

    :dir(rtl) .compare__copy {
      translate: 50% -50%;
      scale: -1 1;
    }

    .compare__footer-notices {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .compare__footer-notices-cell {
      grid-column: 2;
      border-inline-start: 1px solid var(--compare-hairline);
    }

    .compare__row + .compare__row {
      border-block-start: 0;
    }

    .compare__cell--to {
      grid-column: 2;
    }

    .compare__absent {
      position: absolute;
      z-index: 1;
      inset-block: 0;
      inline-size: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--c-surface-default);
    }

    .compare__absent--from {
      inset-inline-start: 0;
    }

    .compare__absent--to {
      inset-inline-start: 50%;
    }

    .compare__panel--absent-from .compare__cell--from,
    .compare__panel--absent-to .compare__cell--to {
      display: block;
      visibility: hidden;
    }

    .compare__panel::after {
      content: '';
      position: absolute;
      z-index: 2;
      inset-block: 0;
      inset-inline-start: 50%;
      border-inline-start: 1px solid var(--compare-hairline);
      pointer-events: none;
    }
  }
</style>

<style>
  /* The page's panel is the page shell's, so it's reached from here. */
  html:has(.compare__panels) {
    overflow: hidden;
  }

  .cp-body__panel:has(.compare__panels) {
    --compare-hairline: light-dark(
      color-mix(in srgb, black 18%, transparent),
      color-mix(in srgb, white 22%, transparent)
    );

    position: fixed;
    inset: 0;
    z-index: var(--c-layer-overlay);
    margin: 0;
    border: 0;
    border-radius: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .cp-content__main:has(.compare__panels) {
    container: compare-page / inline-size;
    display: flex;
    flex-direction: column;
  }

  .cp-content__main > .cp-content-view:has(.compare__panels) {
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }
</style>
