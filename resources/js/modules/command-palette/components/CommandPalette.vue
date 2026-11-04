<script setup lang="ts">
  /**
   * The command palette: a search box over the control panel's pages,
   * actions and content.
   *
   * Before anything is typed it lists recent pages and every action. Once
   * something is, it asks each search provider in parallel and lists what
   * they find in one ranked list. Choosing an item goes to it, runs it, or —
   * for an item with a view — swaps the results for that view until it's done
   * or the person goes back.
   *
   * The search box is a combobox whose popup is the results listbox, after
   * the APG's list autocomplete example: focus stays in the box, the arrow
   * keys move the active option, and Return chooses it. The options never
   * take focus. The active result's own actions, when it has any, are a
   * menu button after the listbox, which Tab moves to.
   */
  import {actionClient, ButtonVariant, t} from '@craftcms/ui';
  import {runAction} from '@craftcms/ui/actions.mjs';
  import {router, usePage} from '@inertiajs/vue3';
  import {index as indexUrl} from '@actions/CommandPaletteController';
  import {
    computed,
    nextTick,
    onMounted,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
    type Component,
  } from 'vue';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import Modal from '@/common/components/Modal.vue';
  import type {BreadcrumbItem} from '@/common/types';
  import useCraftData from '@/common/composables/useCraftData';
  import {useRecentPages} from '../recents';
  import {
    clientActions,
    clientProviders,
    closeCommandPalette,
    isCommandPaletteOpen,
    openedWithShortcut,
    viewComponent,
  } from '../registry';
  import {destructiveLast, matchesWords} from '../rank';
  import {redirectAfter, runHttpAction} from '../run';
  import {useResultsListbox} from '../useResultsListbox';
  import {
    createPageActionsProvider,
    onPageActions,
  } from '../sources/on-page-actions';
  import {
    fromServerItem,
    hasActions,
    type CommandPaletteItem,
    type CommandPaletteProvider,
    type CommandPaletteView,
    type CommandPaletteViewResult,
    type ResultRow,
  } from '../types';
  import {useCommandPaletteSearch} from '../useCommandPaletteSearch';
  import ConfirmView from './ConfirmView.vue';
  import DocsSearchView from './DocsSearchView.vue';
  import NotificationsView from './NotificationsView.vue';
  import ResultActions, {actionsHaveFocus} from './ResultActions.vue';
  import ResultSection from './ResultSection.vue';
  import ShortcutsView from './ShortcutsView.vue';
  import UiFormView from './UiFormView.vue';

  const BUILT_IN_VIEWS: Record<string, Component> = {
    confirm: ConfirmView,
    docs: DocsSearchView,
    notifications: NotificationsView,
    shortcuts: ShortcutsView,
    ui: UiFormView,
  };

  interface Section {
    key: string;
    heading: string;
    items: CommandPaletteItem[];
  }

  const page = usePage<{crumbs?: BreadcrumbItem[]; title?: string}>();
  const {siteCrumb} = useCraftData();
  const recents = useRecentPages();
  const id = useId();

  const input = useTemplateRef<HTMLInputElement>('input');
  const searchField = useTemplateRef<{$el: HTMLElement}>('searchField');
  const contextNote = useTemplateRef<HTMLElement>('contextNote');

  /** What the key that opens an item is called on this platform. */
  const returnKey = /Mac|iPhone|iPad/.test(navigator.platform)
    ? t('Return')
    : t('Enter');

  interface HintKey {
    /** What's shown, hidden from screen readers. */
    symbol: string;
    /** What screen readers say instead. */
    name: string;
  }

  const KEYS = {
    tab: {symbol: 'Tab', name: t('Tab')},
    up: {symbol: '↑', name: t('Up arrow')},
    down: {symbol: '↓', name: t('Down arrow')},
    return: {symbol: returnKey, name: returnKey},
    escape: {symbol: 'Esc', name: t('Escape')},
  } satisfies Record<string, HintKey>;
  const actions = useTemplateRef<{place: () => void}>('actions');
  const viewHeading = useTemplateRef<HTMLElement>('viewHeading');
  const viewBody = useTemplateRef<HTMLElement>('viewBody');

  const query = shallowRef('');
  const selectedIndex = shallowRef(0);
  /** Whether the selection was moved by hand, and so shouldn't follow new results to the top. */
  const selectionMoved = shallowRef(false);
  const busy = shallowRef(false);
  const restoreFocus = shallowRef(true);
  const activeView = shallowRef<{
    item: CommandPaletteItem;
    view: CommandPaletteView;
  } | null>(null);

  const serverProviders = shallowRef<CommandPaletteProvider[]>([]);
  const serverActions = shallowRef<CommandPaletteItem[]>([]);
  const pageActions = shallowRef<CommandPaletteItem[]>([]);
  const recentItems = shallowRef<CommandPaletteItem[]>([]);

  /** Called once the palette has finished closing, for an item that moves focus itself. */
  let afterClose: (() => void | Promise<void>) | null = null;

  const pageActionsProvider = createPageActionsProvider(
    () => pageActions.value
  );

  /**
   * The actions the palette opened with, searched right here so they show
   * straight away. The server's actions provider adds the search-only rest.
   */
  const loadedActionsProvider: CommandPaletteProvider = {
    handle: 'loaded-actions',
    label: t('Actions'),
    search: (term) =>
      serverActions.value.filter((action) => matchesWords(action, term)),
  };

  const providers = computed<readonly CommandPaletteProvider[]>(() => [
    ...clientProviders(),
    pageActionsProvider,
    loadedActionsProvider,
    ...serverProviders.value,
  ]);

  const site = () => new URL(window.location.href).searchParams.get('site');
  const search = useCommandPaletteSearch(query, providers, site);

  const isSearching = computed(() => query.value.trim() !== '');

  const sections = computed<Section[]>(() => {
    const all: Section[] = isSearching.value
      ? [{key: 'results', heading: t('Results'), items: search.results.value}]
      : [
          {key: 'recents', heading: t('Recents'), items: recentItems.value},
          {
            key: 'actions',
            heading: t('Actions'),
            items: destructiveLast([
              ...pageActions.value,
              ...serverActions.value,
            ]),
          },
        ];

    return all.filter((section) => section.items.length > 0);
  });

  const rowsBySection = computed(() => {
    let index = 0;

    return sections.value.map((section) => ({
      ...section,
      groupId: `${id}-${section.key}`,
      rows: section.items.map((item): ResultRow => {
        const row = {item, index, optionId: `${id}-option-${index}`};
        index++;

        return row;
      }),
    }));
  });

  const rows = computed(() =>
    rowsBySection.value.flatMap((section) => section.rows)
  );
  const {
    listboxId,
    selectedRow,
    comboboxAttributes,
    indexAfter,
    scrollSelectedIntoView,
  } = useResultsListbox(id, rows, selectedIndex);
  const selectedHasActions = computed(() =>
    Boolean(selectedRow.value && hasActions(selectedRow.value.item))
  );

  /**
   * The result under the pointer, by id. It's only highlighted, and shows its
   * actions: the active option changes with the keys or a click alone, so
   * moving the pointer doesn't make screen readers read each row it crosses.
   */
  const hoveredId = shallowRef<string | null>(null);
  const hoveredRow = computed(
    () => rows.value.find((row) => row.item.id === hoveredId.value) ?? null
  );

  /** Whose actions show: the result under the pointer's, or else the active one's. */
  const actionsRow = computed(
    () =>
      [hoveredRow.value, selectedRow.value].find(
        (row) => row && hasActions(row.item)
      ) ?? null
  );

  // The top result is selected until the person picks another, or moves to
  // its actions; once they have, theirs stays selected as other providers'
  // results arrive.
  watch(query, () => {
    selectionMoved.value = false;
    selectedIndex.value = 0;
    hoveredId.value = null;
  });

  watch(rows, (current, previous) => {
    if (!selectionMoved.value) {
      selectedIndex.value = 0;

      return;
    }

    const selectedId = previous?.[selectedIndex.value]?.item.id;
    const index = current.findIndex((row) => row.item.id === selectedId);
    selectedIndex.value = Math.max(index, 0);
  });

  // A result's actions go when it does, and focus on them would go with them.
  watch(actionsRow, (current, previous) => {
    if (current?.item.id !== previous?.item.id && actionsHaveFocus()) {
      input.value?.focus();
    }
  });

  /** Which providers couldn’t be searched, if any. */
  const failedMessage = computed(() =>
    search.failed.value.length
      ? t('Couldn’t search {providers}.', {
          providers: search.failed.value.join(', '),
        })
      : ''
  );

  const statusMessage = computed(() => {
    if (!isSearching.value || activeView.value) {
      return '';
    }

    if (search.searching.value) {
      return t('Searching…');
    }

    const count = search.results.value.length;

    return count
      ? t('{count, plural, =1{# result} other{# results}}', {count})
      : t('No results');
  });

  // Recent pages are recorded as they're visited, not when they're opened
  // from here, so pages reached any other way count too.
  watch(
    () => page.url,
    async () => {
      await nextTick();
      const title = page.props.title || document.title;
      recents.record(window.location.href, title);
    },
    {immediate: true}
  );

  async function loadServerData(): Promise<void> {
    try {
      const {data} = await actionClient.get<{
        providers: Array<{handle: string; label: string}>;
        actions: CraftCms.Cms.Cp.CommandPalette.CommandPaletteItem[];
      }>(indexUrl.url());
      serverProviders.value = data.providers;
      serverActions.value = data.actions.map(fromServerItem);
    } catch (error: any) {
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
    }
  }

  // This loads after the page, so the shortcut may have opened the palette
  // already. Starting closed and opening once mounted takes it through the
  // usual opening — the page's actions gathered, the search box focused.
  if (isCommandPaletteOpen.value) {
    isCommandPaletteOpen.value = false;
    onMounted(() => {
      isCommandPaletteOpen.value = true;
    });
  }

  watch(isCommandPaletteOpen, (open) => {
    if (!open) {
      return;
    }

    const crumbs = page.props.crumbs ?? [];
    pageActions.value = onPageActions(
      crumbs.some((crumb) => crumb.id === 'site-crumb') || !siteCrumb.value
        ? crumbs
        : [siteCrumb.value, ...crumbs]
    ).concat(clientActions());
    recentItems.value = recents.items(window.location.href);
    void loadServerData();
    void focusInputOnceShown();
  });

  /**
   * Focuses the search input as soon as the dialog has shown itself, which
   * moves focus into it, rather than once its transition ends — so what's
   * typed straight after the shortcut lands in the input.
   */
  async function focusInputOnceShown(): Promise<void> {
    await nextTick();
    await (
      input.value?.closest('craft-dialog') as
        | (HTMLElement & {updateComplete?: Promise<unknown>})
        | null
    )?.updateComplete;
    // Selected, so typing replaces a query left from last time.
    input.value?.focus();
    input.value?.select();
  }

  /**
   * A press on the field's own padding, beside the input and its icon, goes
   * to the input, so the whole bar acts as one text box.
   */
  function onFieldMousedown(event: MouseEvent): void {
    const target = event.target as Element | null;

    if (target !== input.value && !target?.closest('craft-button')) {
      event.preventDefault();
      input.value?.focus();
    }
  }

  function onOpened(): void {
    describeByContextNote(searchField.value?.$el);
  }

  /**
   * Describes a search input by what choosing a result does. The keys are
   * the usual combobox ones, so they're left to the keyboard instructions,
   * not read each time the input is focused. The search field manages its
   * input's `aria-describedby`, so the note joins through it rather than
   * being set on the input directly.
   */
  function describeByContextNote(field: Element | null | undefined): void {
    if (contextNote.value) {
      (
        field as
          | (Element & {addToAriaDescribedBy?: (element: HTMLElement) => void})
          | undefined
      )?.addToAriaDescribedBy?.(contextNote.value);
    }
  }

  function close(): void {
    closeCommandPalette();
  }

  /**
   * Escape inside a view goes back to the results wherever focus is — the
   * dialog itself, say, after a click on its padding; anything else closes.
   */
  function onModalClose(event: Event): void {
    if (activeView.value && event instanceof KeyboardEvent) {
      back();
    } else {
      close();
    }
  }

  async function onClosed(): Promise<void> {
    const callback = afterClose;
    afterClose = null;
    restoreFocus.value = true;
    openedWithShortcut.value = false;
    activeView.value = null;

    if (callback) {
      await callback();
    }
  }

  /** Back to an empty search, as the palette was first opened. */
  function reset(): void {
    query.value = '';
    activeView.value = null;
    selectedIndex.value = 0;
    selectionMoved.value = false;
    search.reset();
  }

  /** Closes the palette, then calls `callback`, leaving focus to it. */
  function closeThen(callback: () => void | Promise<void>): void {
    afterClose = callback;
    restoreFocus.value = false;
    reset();
    close();
  }

  function finish(result: CommandPaletteViewResult = {}): void {
    if (result.run) {
      closeThen(result.run);

      return;
    }

    reset();
    close();

    if (result.message) {
      Craft.cp?.displayNotice?.(result.message);
    }

    if (result.reload) {
      window.location.reload();
    } else if (result.redirect) {
      if (result.replace) {
        // The URL changes before the visit does, so a reload while it's
        // loading — such as the dev server's — can't land on the removed page.
        window.history.replaceState(window.history.state, '', result.redirect);
      }

      router.visit(result.redirect, {replace: result.replace});
    }
  }

  function openView(item: CommandPaletteItem, view: CommandPaletteView): void {
    if (!BUILT_IN_VIEWS[view.type] && !viewComponent(view.type)) {
      console.error(
        `No command palette view is registered for “${view.type}”.`
      );
      Craft.cp?.displayError?.(t('A server error occurred.'));

      return;
    }

    activeView.value = {item, view};
  }

  function back(): void {
    activeView.value = null;
  }

  watch(activeView, async (current, previous) => {
    viewQuery.value = '';
    await nextTick();

    if (current) {
      // A view can start somewhere other than its heading, such as its own
      // search box.
      (
        viewBody.value?.querySelector<HTMLElement>('[data-autofocus]') ??
        viewHeading.value
      )?.focus();

      if (current.view.type === 'docs') {
        describeByContextNote(
          viewBody.value?.querySelector('craft-input-search')
        );
      }
    } else if (previous && isCommandPaletteOpen.value) {
      input.value?.focus();
    }
  });

  async function runServerAction(item: CommandPaletteItem): Promise<void> {
    const action = item.action;

    if (!action) {
      return;
    }

    if (action.type !== 'http') {
      closeThen(() => runAction(action));

      return;
    }

    busy.value = true;

    try {
      const {redirect} = await runHttpAction(action);
      finish({...redirectAfter(item, redirect), reload: item.reload});
    } catch {
      // Already shown; the palette stays open so the person can try again.
    } finally {
      busy.value = false;
    }
  }

  function navigate(item: CommandPaletteItem, newTab: boolean): void {
    const href = item.href;

    if (!href) {
      return;
    }

    if (newTab) {
      window.open(href, '_blank', 'noopener');

      return;
    }

    reset();
    close();

    if (item.external) {
      window.location.assign(href);
    } else {
      router.visit(href);
    }
  }

  /**
   * Chooses an item. `trigger` is the click that chose it, when it was
   * clicked: Command or Control opens a link in a new tab, as Return does.
   */
  function activate(
    item: CommandPaletteItem,
    {newTab = false, trigger}: {newTab?: boolean; trigger?: MouseEvent} = {}
  ): void {
    if (busy.value) {
      trigger?.preventDefault();

      return;
    }

    if (item.view) {
      openView(item, item.view);
    } else if (item.action && 'confirm' in item.action && item.action.confirm) {
      openView(item, {type: 'confirm', message: item.action.confirm});
    } else if (item.run && item.confirm) {
      openView(item, {type: 'confirm', message: item.confirm});
    } else if (item.run) {
      closeThen(item.run);
    } else if (item.action) {
      void runServerAction(item);
    } else {
      navigate(item, trigger ? trigger.metaKey || trigger.ctrlKey : newTab);
    }
  }

  /**
   * Gets out of the way of a result's menu action, which may open a slideout
   * or modal of its own and take focus there.
   */
  function dismissForMenuAction(): void {
    restoreFocus.value = false;
    reset();
    close();
  }

  function select(index: number): void {
    selectedIndex.value = index;
    selectionMoved.value = true;
  }

  /**
   * Not while focus is on the actions, though: the menu, and the pointer on
   * its way to it, would lose its row.
   */
  function onOptionHover(index: number): void {
    if (!actionsHaveFocus()) {
      hoveredId.value = rows.value[index]?.item.id ?? null;
    }
  }

  function onResultsPointerLeave(): void {
    if (!actionsHaveFocus()) {
      hoveredId.value = null;
    }
  }

  // Its option can move as results come in, taking the space kept for the
  // actions with it.
  watch(rows, () => actions.value?.place(), {flush: 'post'});

  function moveSelection(step: number): void {
    const index = indexAfter(step);

    if (index === null) {
      return;
    }

    hoveredId.value = null;
    select(index);
    void scrollSelectedIntoView();
  }

  function onInputKeydown(event: KeyboardEvent): void {
    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp':
        event.preventDefault();
        moveSelection(event.key === 'ArrowDown' ? 1 : -1);
        break;
      case 'Enter':
        if (event.isComposing) {
          return;
        }

        event.preventDefault();

        if (selectedRow.value) {
          activate(selectedRow.value.item, {
            newTab: event.metaKey || event.ctrlKey,
          });
        }
        break;
      case 'Tab':
        if (
          !event.shiftKey &&
          actionsRow.value &&
          actionsRow.value !== selectedRow.value
        ) {
          tabPastHoveredActions(event);
        }
        break;
    }
  }

  /**
   * Tab reaches the active result's actions, as its option and the keyboard
   * instructions say, not those of the result under the pointer. Without
   * any, Tab moves on past the pointer's, which `inert` takes out of its way
   * until they go.
   */
  function tabPastHoveredActions(event: KeyboardEvent): void {
    document
      .querySelector('.command-palette-actions')
      ?.setAttribute('inert', '');
    hoveredId.value = null;

    if (selectedHasActions.value) {
      event.preventDefault();
      void nextTick(() =>
        document
          .querySelector<HTMLElement>(
            '.command-palette-actions [slot="invoker"]'
          )
          ?.focus()
      );
    }
  }

  /**
   * Escape closes a result's menu, or steps back out of a view to the
   * results. Otherwise it clears the search and returns to the search box,
   * wherever focus is, or once there's no search, closes the palette. The
   * search box clears itself when it has focus; the rest is stopped here
   * before the dialog sees it.
   */
  function onKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Escape') {
      return;
    }

    const target = event.target as HTMLElement | null;
    const menu = target?.closest<HTMLElement & {close?: () => void}>(
      'craft-action-menu'
    );

    // The menu closes itself on Escape, cancelling the event as it does, but
    // the dialog would still close too.
    if (menu && !target?.closest('[slot="invoker"]')) {
      event.preventDefault();
      event.stopPropagation();
      menu.close?.();
      menu.querySelector<HTMLElement>('[slot="invoker"]')?.focus();

      return;
    }

    if (event.defaultPrevented) {
      return;
    }

    if (activeView.value) {
      back();
    } else if (query.value) {
      query.value = '';
      selectedIndex.value = 0;
      selectionMoved.value = false;
      input.value?.focus();
    } else {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
  }

  /** The open view's own search, for one that has one, as docs search does. */
  const viewQuery = shallowRef('');

  /** The keyboard instructions apply to the search, and to the docs search view's own. */
  const showsHint = computed(
    () => !activeView.value || activeView.value.view.type === 'docs'
  );

  /**
   * Warns that choosing a result changes the context, before anyone does, per
   * WCAG 3.2.2 (On Input): it goes somewhere, runs something, or opens a tab.
   */
  const contextNoteText = computed(() =>
    activeView.value
      ? t('Choosing a result opens it in a new tab.')
      : t('Choosing an item opens its page or runs its action.')
  );

  /**
   * The keyboard instructions in the footer. Tab only reaches the active
   * result's actions, so it's listed only while that result has some, after
   * the instructions that always apply.
   */
  const hints = computed(() => [
    {keys: [KEYS.up, KEYS.down], label: t('Choose item')},
    {
      keys: [KEYS.return],
      label: activeView.value ? t('Open in new tab') : t('Open item'),
    },
    {keys: [KEYS.escape], label: escapeHint.value},
    ...(!activeView.value && selectedHasActions.value
      ? [{keys: [KEYS.tab], label: t('Actions')}]
      : []),
  ]);

  /** What Escape does where focus usually is: clear the search, go back, or close. */
  const escapeHint = computed(() => {
    if (activeView.value) {
      return viewQuery.value ? t('Clear search') : t('Back');
    }

    return query.value ? t('Clear search') : t('Close');
  });

  const activeViewComponent = computed(() =>
    activeView.value
      ? (BUILT_IN_VIEWS[activeView.value.view.type] ??
        viewComponent(activeView.value.view.type))
      : null
  );
</script>

<template>
  <Modal
    :is-active="isCommandPaletteOpen"
    :label="t('Command palette')"
    width="auto"
    :dismissible="!busy"
    :restore-focus="restoreFocus"
    :instant="openedWithShortcut"
    style="--c-modal-border-width: 0px"
    @close="onModalClose"
    @opened="onOpened"
    @closed="onClosed"
  >
    <div class="command-palette" @keydown="onKeydown">
      <div v-show="!activeView" class="command-palette__search">
        <div class="command-palette__field" @mousedown="onFieldMousedown">
          <CraftInputSearch
            ref="searchField"
            v-model="query"
            class="command-palette__search-field"
            :placeholder="t('Search for pages, actions, and content')"
          >
            <input
              ref="input"
              slot="input"
              type="text"
              class="command-palette__input"
              v-bind="comboboxAttributes"
              autocapitalize="off"
              spellcheck="false"
              @keydown="onInputKeydown"
            />
          </CraftInputSearch>
        </div>

        <div
          class="command-palette__results"
          :aria-busy="search.searching.value ? 'true' : 'false'"
          @pointerleave="onResultsPointerLeave"
        >
          <div
            v-if="busy || (search.searching.value && !rows.length)"
            class="command-palette__loading"
          >
            <craft-spinner
              :label="busy ? t('Working') : t('Searching…')"
            ></craft-spinner>
          </div>
          <div
            :id="listboxId"
            role="listbox"
            :aria-label="isSearching ? t('Results') : t('Suggestions')"
            :hidden="!rows.length"
          >
            <ResultSection
              v-for="section in rowsBySection"
              :key="section.key"
              :group-id="section.groupId"
              :heading="section.heading"
              :rows="section.rows"
              :selected-index="selectedIndex"
              :hovered-index="hoveredRow?.index ?? -1"
              :actions-index="actionsRow?.index ?? -1"
              @activate="(item, trigger) => activate(item, {trigger})"
              @hover="onOptionHover"
            />
          </div>
          <ResultActions
            v-if="actionsRow"
            ref="actions"
            :key="actionsRow.item.id"
            :item="actionsRow.item"
            :option-id="actionsRow.optionId"
            :active="actionsRow === selectedRow"
            @focusin="selectionMoved = true"
            @dismiss="dismissForMenuAction"
          />
          <craft-empty
            v-if="
              isSearching &&
              !search.searching.value &&
              !search.results.value.length
            "
            class="command-palette__empty"
            :label="t('No results')"
          >
            <p v-if="failedMessage" class="command-palette__failed">
              {{ failedMessage }}
            </p>
          </craft-empty>
          <p v-else-if="failedMessage" class="command-palette__failed">
            {{ failedMessage }}
          </p>
        </div>

        <div class="sr-only" role="status">{{ statusMessage }}</div>
      </div>

      <div v-if="activeView" ref="viewBody" class="command-palette__view">
        <div class="command-palette__view-header">
          <craft-button
            type="button"
            size="small"
            icon="arrow-left"
            flush="inline-start"
            :variant="ButtonVariant.Plain"
            @click="back"
          >
            {{ t('Back') }}
          </craft-button>
          <h2 ref="viewHeading" tabindex="-1" class="command-palette__title">
            {{ activeView.item.label }}
          </h2>
          <div
            :id="`${id}-view-actions`"
            class="command-palette__view-actions"
          ></div>
        </div>
        <component
          :is="activeViewComponent"
          :key="activeView.item.id"
          :item="activeView.item"
          :view="activeView.view"
          :actions-target="`#${id}-view-actions`"
          @done="finish"
          @back="back"
          @query="(value: string) => (viewQuery = value)"
        />
      </div>

      <p :id="`${id}-context`" ref="contextNote" class="sr-only">
        {{ contextNoteText }}
      </p>
      <!-- A region of its own, so the instructions can be found by heading or landmark. -->
      <section
        v-show="showsHint"
        class="command-palette__hint"
        :aria-labelledby="`${id}-keys-heading`"
      >
        <h2 :id="`${id}-keys-heading`" class="sr-only">
          {{ t('Keyboard instructions') }}
        </h2>
        <!-- `role="list"` keeps the list's semantics in Safari, which drops them without list styling. -->
        <ul role="list" class="command-palette__hint-list">
          <li
            v-for="item in hints"
            :key="item.label"
            class="command-palette__hint-item"
          >
            <span class="command-palette__hint-keys">
              <kbd v-for="key in item.keys" :key="key.name">
                <span aria-hidden="true">{{ key.symbol }}</span>
                <span class="sr-only">{{ key.name }}</span>
              </kbd>
            </span>
            {{ item.label }}
          </li>
        </ul>
      </section>
    </div>
  </Modal>
</template>

<style scoped>
  /* Draws the modal's border inside its clipping box, so the search field's focus ring can cover it. */
  .command-palette {
    /*
     * The search bar's height: a small control between the modal's padding,
     * which is what a view's heading bar comes to with its small Back button.
     */
    --_bar-block-size: calc(
      var(--c-size-control-sm) + var(--c-modal-padding) * 2
    );
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    border: 1px var(--c-modal-border-style) var(--c-modal-border-color);
    border-radius: var(--c-modal-radius);
    inline-size: min(42rem, calc(100vw - (var(--c-spacing-lg) * 2)));
    max-block-size: min(36rem, calc(100vh - (var(--c-spacing-lg) * 2)));
    min-block-size: 0;
  }

  .command-palette__search,
  .command-palette__view {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-block-size: 0;
  }

  .command-palette__field {
    display: flex;
    cursor: text;
    align-items: center;
    gap: var(--c-spacing-sm);
    padding-inline: calc(var(--c-modal-padding) - var(--c-spacing-sm));
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    border-start-start-radius: calc(var(--c-modal-radius) - 1px);
    border-start-end-radius: calc(var(--c-modal-radius) - 1px);
  }

  .command-palette__search-field {
    --c-input-border-width: 0px;
    --c-input-radius: 0px;
    --c-input-shadow: none;
    --c-input-fill: transparent;
    --c-input-height: var(--_bar-block-size);
    --c-input-spacing-inline: var(--c-spacing-sm);
    /* Overrides the field's private clear-button inset, so its icon lines up with the result types' right edge. */
    --_clear-inset: 0px;
    flex: 1;
    min-inline-size: 0;
    font-size: var(--c-text-lg);
  }

  /* The bar draws the input's ring, so the clear button keeps its own. */
  .command-palette__input {
    --c-focus-outline-style: none;
    font: inherit;
  }

  .command-palette__input::placeholder {
    color: var(--c-text-quiet);
    opacity: 1;
  }

  .command-palette__field:has(.command-palette__input:focus) {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: calc(var(--c-focus-outline-width) * -1);
  }

  .command-palette__results {
    position: relative;
    flex: 1;
    min-block-size: 0;
    overflow-y: auto;
    padding-block: var(--c-spacing-sm);
    padding-inline: calc(var(--c-modal-padding) - var(--c-spacing-md));
    scroll-padding-block: var(--c-spacing-sm);
  }

  /* Fills the results, like an empty table, so the message sits in their middle. */
  .command-palette__empty {
    position: absolute;
    inset: 0;
    display: grid;
  }

  .command-palette__failed {
    margin: 0;
    padding: var(--c-spacing-md);
    color: var(--c-text-quiet);
    text-align: center;
  }

  .command-palette__empty .command-palette__failed {
    padding: 0;
  }

  .command-palette__loading {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    pointer-events: none;
  }

  .command-palette__hint {
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--c-modal-padding);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .command-palette__hint-list {
    display: flex;
    flex-wrap: wrap;
    column-gap: var(--c-spacing-lg);
    row-gap: var(--c-spacing-xs);
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .command-palette__hint-item {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .command-palette__hint-keys {
    display: inline-flex;
    gap: var(--c-spacing-xs);
  }

  .command-palette__view {
    padding: var(--c-modal-padding);
    overflow-y: auto;
  }

  /* A hairline across the whole modal separates the view's heading from its content. */
  .command-palette__view-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-lg);
    margin-inline: calc(var(--c-modal-padding) * -1);
    margin-block-end: var(--cp-container-padding);
    padding-inline: var(--c-modal-padding);
    padding-block-end: var(--c-modal-padding);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .command-palette__view-actions {
    display: flex;
    gap: var(--c-spacing-sm);
    margin-inline-start: auto;

    &:empty {
      display: none;
    }
  }

  .command-palette__title {
    margin: 0;
    font-size: var(--c-text-lg);
    font-weight: var(--font-weight-semibold);
    overflow-wrap: anywhere;
  }

  @media (forced-colors: active) {
    .command-palette__field:has(.command-palette__input:focus) {
      outline-color: Highlight;
    }
  }
</style>
