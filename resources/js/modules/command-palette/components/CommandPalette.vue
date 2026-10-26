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
   * The search box is a plain search field. Each group of results is a
   * `craft-grid` after it, which Tab moves into and the arrow keys move
   * around; choosing a result is always a press on the result itself.
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
  import {resetResultGrids} from '../grids';
  import {redirectAfter, runHttpAction} from '../run';
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
  } from '../types';
  import {useCommandPaletteSearch} from '../useCommandPaletteSearch';
  import ConfirmView from './ConfirmView.vue';
  import DocsSearchView from './DocsSearchView.vue';
  import NotificationsView from './NotificationsView.vue';
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

  /** A key in the keyboard instructions: shown as its symbol, read by its name. */
  interface HintKey {
    symbol: string;
    name: string;
  }

  /** What the key that opens an item is called on this platform. */
  const returnKey = /Mac|iPhone|iPad/.test(navigator.platform)
    ? t('Return')
    : t('Enter');

  const KEYS = {
    tab: {symbol: 'Tab', name: t('Tab')},
    up: {symbol: '↑', name: t('Up arrow')},
    down: {symbol: '↓', name: t('Down arrow')},
    left: {symbol: '←', name: t('Left arrow')},
    right: {symbol: '→', name: t('Right arrow')},
    return: {symbol: returnKey, name: returnKey},
    escape: {symbol: 'Esc', name: t('Escape')},
  } satisfies Record<string, HintKey>;
  const results = useTemplateRef<HTMLElement>('results');
  /** Where focus was when the palette opened, for when closing leaves it nowhere. */
  let opener: HTMLElement | null = null;
  const viewHeading = useTemplateRef<HTMLElement>('viewHeading');
  const viewBody = useTemplateRef<HTMLElement>('viewBody');

  const query = shallowRef('');
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

  // New results start from the top, whether they come from a new query or
  // from a provider answering late — unless focus is among them already.
  watch(sections, async () => {
    await nextTick();

    if (!results.value?.contains(document.activeElement)) {
      resetResultGrids(results.value);
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

  /**
   * The keyboard instructions in the footer. Each section is one Tab stop,
   * which the Tab and arrow lines say when there's more than one. Left and
   * Right only reach a result's own menu, so they're listed only while a
   * result has one.
   */
  const hints = computed(() => [
    ...(sections.value.length > 1
      ? [
          {keys: [KEYS.tab], label: t('Next section')},
          {keys: [KEYS.up, KEYS.down], label: t('Move within a section')},
        ]
      : [
          {keys: [KEYS.tab], label: t('Go to results')},
          {keys: [KEYS.up, KEYS.down], label: t('Move between results')},
        ]),
    ...(sections.value.some((section) => section.items.some(hasActions))
      ? [
          {
            keys: [KEYS.left, KEYS.right],
            label: t('Move to a result’s actions'),
          },
        ]
      : []),
    {keys: [KEYS.return], label: t('Open')},
    {keys: [KEYS.escape], label: query.value ? t('Clear search') : t('Close')},
  ]);

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

    opener =
      document.activeElement instanceof HTMLElement &&
      document.activeElement !== document.body
        ? document.activeElement
        : null;
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

    // An item that was meant to move focus, but didn't — copying to the
    // clipboard, say — would otherwise leave it on the page itself.
    if (
      (!document.activeElement || document.activeElement === document.body) &&
      opener?.isConnected
    ) {
      opener.focus();
    }

    opener = null;
  }

  /** Back to an empty search, as the palette was first opened. */
  function reset(): void {
    query.value = '';
    activeView.value = null;
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
    await nextTick();

    if (current) {
      // A view can start somewhere other than its heading, such as its own
      // search box.
      (
        viewBody.value?.querySelector<HTMLElement>('[data-autofocus]') ??
        viewHeading.value
      )?.focus();
    } else if (previous && isCommandPaletteOpen.value) {
      // Back to the result that opened the view, as a dialog returns focus
      // to its opener.
      (
        results.value?.querySelector<HTMLElement>(
          `[data-command-palette-item="${CSS.escape(previous.item.id)}"]`
        ) ?? input.value
      )?.focus();
    }
  });

  async function runServerAction(item: CommandPaletteItem): Promise<void> {
    const action = item.action;

    if (!action) {
      return;
    }

    if (action.type !== 'http') {
      closeThen(async () => {
        await runAction(action);

        if (action.type === 'clipboard') {
          Craft.cp?.displayNotice?.(t('Copied!'));
        }
      });

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

  /**
   * Chooses an item. `trigger` is the click that chose it — by pointer, or by
   * Return on the focused result. A link does the navigating itself, so a
   * modified click can still open it in a new tab.
   */
  function activate(item: CommandPaletteItem, trigger: MouseEvent): void {
    if (busy.value) {
      trigger.preventDefault();

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
      const modified =
        trigger.metaKey ||
        trigger.ctrlKey ||
        trigger.shiftKey ||
        trigger.altKey;

      if (!modified) {
        reset();
        close();
      }
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
      input.value?.focus();
    } else {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
  }

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
    @closed="onClosed"
  >
    <div class="command-palette" @keydown="onKeydown">
      <div v-show="!activeView" class="command-palette__search">
        <div class="command-palette__field" @mousedown="onFieldMousedown">
          <CraftInputSearch
            v-model="query"
            class="command-palette__search-field"
            :placeholder="t('Search for pages, actions, and content')"
          >
            <input
              ref="input"
              slot="input"
              type="text"
              class="command-palette__input"
              autocapitalize="off"
              spellcheck="false"
            />
          </CraftInputSearch>
        </div>

        <div
          ref="results"
          class="command-palette__results"
          :aria-busy="search.searching.value ? 'true' : 'false'"
        >
          <div
            v-if="
              busy ||
              (search.searching.value &&
                !sections.some((section) => section.items.length))
            "
            class="command-palette__loading"
          >
            <craft-spinner
              :label="busy ? t('Working') : t('Searching…')"
            ></craft-spinner>
          </div>
          <ResultSection
            v-for="section in sections"
            :key="section.key"
            :grid-id="`${id}-${section.key}`"
            :heading="section.heading"
            :items="section.items"
            :description-id="`${id}-grid-description`"
            @activate="activate"
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

        <!-- Hidden, as it's only ever read as the grids' description. -->
        <p :id="`${id}-grid-description`" hidden>
          {{
            t(
              'Instructions for moving between results are in the Keyboard instructions section.'
            )
          }}
        </p>
        <section
          class="command-palette__hint"
          :aria-labelledby="`${id}-keys-heading`"
        >
          <h2 :id="`${id}-keys-heading`" class="sr-only">
            {{ t('Keyboard instructions') }}
          </h2>
          <!-- `role="list"` keeps the list's semantics in Safari, which drops them without list styling. -->
          <ul role="list" class="command-palette__hint-list">
            <li
              v-for="hint in hints"
              :key="hint.label"
              class="command-palette__hint-item"
            >
              <span class="command-palette__hint-keys">
                <kbd v-for="key in hint.keys" :key="key.name">
                  <span aria-hidden="true">{{ key.symbol }}</span>
                  <span class="sr-only">{{ key.name }}</span>
                </kbd>
              </span>
              {{ hint.label }}
            </li>
          </ul>
        </section>
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
        />
      </div>
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
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-modal-padding);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .command-palette__hint-list {
    display: flex;
    flex-wrap: wrap;
    column-gap: var(--c-spacing-lg);
    row-gap: var(--c-spacing-sm);
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
