<script setup lang="ts">
  /**
   * The "Customize sources" modal: a source list on the left and the selected
   * source's settings on the right.
   *
   * Each source's settings are a UI payload built by
   * `ElementSourcesController::show()` and namespaced at `sources.<key>`, so a
   * UiRenderer per source produces exactly the shape `store()` reads back.
   */
  import {computed, nextTick, ref, shallowRef, watch} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import ElementSourcesController from '@actions/Elements/ElementSourcesController';
  import ModalForm from '@/common/components/ModalForm.vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiChange, UiPayload} from '@/modules/ui/types';
  import {pathsMatch, valueAt, visitControls} from '@/modules/ui/runtime';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useSourceOrdering} from '@/modules/elements/index/composables/useSourceOrdering';
  import {useSourcePages} from '@/modules/elements/index/composables/useSourcePages';
  import {
    FOCUSABLE,
    focusElement,
    useSourceScreens,
  } from '@/modules/elements/index/composables/useSourceScreens';
  import CustomizeSourcesColumn from './CustomizeSourcesColumn.vue';
  import CustomSourceList from './CustomSourceList.vue';
  import PageSettingsModal from './PageSettingsModal.vue';
  import {
    pageNameId,
    type PageRow,
    type SourceRow,
    type SourcesResponse,
    type SourceType,
  } from './types';

  const props = defineProps<{
    isActive: boolean;
    elementType: string;
    /** The index's current page, so the matching one starts selected. */
    page?: string | null;
    /** The index's current source, so the matching one starts selected. */
    sourceKey?: string | null;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    /**
     * The settings were saved. `sourceKey` is the source the index should show
     * next — the one last edited, unless it can't be shown — or null to stay
     * put; `url` is the link the nav gives it, when the server knows one.
     */
    (e: 'saved', landing: {sourceKey: string | null; url: string | null}): void;
  }>();

  type Renderer = {
    currentValues(): UiPayload['values'];
    setValue(path: string[], value: UiPayload['values'][string]): void;
  };

  const {announce} = useAnnouncer();

  // `uuid` lives on the legacy `Craft` global and isn't described by CraftStatic.
  const craft = Craft as typeof Craft & {uuid(): string};

  const loading = ref(false);
  /**
   * Whether the sources arrived. Until they have, there's nothing to save — and
   * saving an empty list would reset the element type's sources to their
   * defaults.
   */
  const loaded = ref(false);
  const saving = ref(false);
  const multiPage = ref(false);
  const sources = ref<SourceRow[]>([]);
  const selectedKey = ref<string | null>(null);
  const selectedPage = ref<string | null>(null);
  /**
   * The source most recently selected, kept after it's deselected — switching
   * pages or moving it clears the selection, but it's still the one being
   * worked on, so it's where the index lands after saving.
   */
  const lastEdited = ref<string | null>(null);
  const errors = ref<Record<string, UiPayload['errors']>>({});
  /** The source whose settings failed to load, so its spinner stops. */
  const failedUiKey = shallowRef<string | null>(null);
  const renderers = new Map<string, Renderer>();

  const body = ref<HTMLElement | null>(null);
  const settingsPane = ref<HTMLElement | null>(null);

  const {screen, rowElement, focusRow, showScreen} = useSourceScreens(body);

  const {
    visibleSources,
    sourceSpan,
    reorderSources,
    moveRows,
    moveToPage: moveSourcesToPage,
  } = useSourceOrdering({sources, multiPage, selectedPage});

  const {
    pages,
    editingPage,
    pageModalActive,
    resetPages,
    openPageSettings,
    savePage,
    validatePageName,
    pageActions,
    sourceMoves,
    reorderPages,
  } = useSourcePages({
    sources,
    selectedPage,
    moveRows,
    onRemoved: (page, neighbor) => void onPageRemoved(page, neighbor),
  });

  const selected = computed(() =>
    sources.value.find((source) => source.key === selectedKey.value)
  );

  const settingsLoading = computed(
    () =>
      !!selected.value &&
      !selected.value.ui &&
      selected.value.key !== failedUiKey.value
  );

  watch(
    () => props.isActive,
    (active) => {
      if (active) load();
    },
    {immediate: true}
  );

  async function load(): Promise<void> {
    loading.value = true;
    loaded.value = false;
    lastEdited.value = null;
    renderers.clear();
    errors.value = {};
    screen.value = 'sources';

    try {
      const {data} = await actionClient.post<SourcesResponse>(
        ElementSourcesController.show().url,
        {
          elementType: props.elementType,
        }
      );

      multiPage.value = data.multiPage;
      sources.value = data.sources.map((source) => ({
        // Headings saved before 5.8 have no key, and neither does the blank one
        // ElementSources adds to separate newly added sources from the rest.
        // Keyed here, as the legacy modal did, they're saved like any other
        // heading instead of being dropped.
        key:
          source.key ??
          (source.type === 'heading' ? `heading:${craft.uuid()}` : null),
        type: source.type,
        label:
          (source.type === 'heading' ? source.heading : source.label) ?? '',
        handle: source.handle ?? null,
        page: source.page ?? '',
        ui: source.ui,
        mounted: false,
      }));
      resetPages(data);
      selectedPage.value = initialPage();
      loaded.value = true;
      void select(initialSource());
    } catch (error: any) {
      Craft.cp?.displayError?.(error?.response?.data?.message);
    } finally {
      loading.value = false;
    }

    if (loaded.value) {
      // Start in the list, on the source being edited.
      await nextTick();
      focusRow('sources', selectedKey.value);
    }
  }

  function initialPage(): string | null {
    if (!multiPage.value) return null;

    const current = props.page ? pageNameId(props.page) : null;
    const match = pages.value.find((page) => pageNameId(page.name) === current);

    return (match ?? pages.value[0])?.name ?? null;
  }

  function initialSource(): string | null {
    const onPage = sources.value.filter(
      (source) => !multiPage.value || source.page === selectedPage.value
    );
    const current = onPage.find((source) => source.key === props.sourceKey);

    return (current ?? onPage.find((source) => source.key))?.key ?? null;
  }

  async function select(key: string | null): Promise<void> {
    selectedKey.value = key;

    const source = key
      ? sources.value.find((row) => row.key === key)
      : undefined;
    if (!source?.key) return;

    if (source.type !== 'heading') lastEdited.value = source.key;

    if (!source.ui) {
      failedUiKey.value = null;

      let ui: UiPayload;
      try {
        ui = await fetchUi(source.key, source.type);
      } catch (error: any) {
        failedUiKey.value = source.key;
        Craft.cp?.displayError?.(error?.response?.data?.message);
        return;
      }

      // A heading keyed on load isn't in project config yet, so the server
      // builds it blank; the text it already had comes from the list.
      if (source.type === 'heading') {
        const values = (
          ui.values.sources as Record<string, Record<string, unknown>>
        )?.[source.key];
        if (values) values.heading = source.label;
      }

      source.ui = ui;
    }
    // Settings are built on first select and kept mounted afterwards, so
    // unsaved edits and server-rendered controls survive switching sources.
    source.mounted = true;
  }

  async function fetchUi(
    sourceKey: string,
    type: SourceType
  ): Promise<UiPayload> {
    const {data} = await actionClient.post(ElementSourcesController.ui().url, {
      elementType: props.elementType,
      sourceKey,
      type,
    });

    return data.ui;
  }

  function setRenderer(key: string, el: unknown): void {
    if (el) {
      renderers.set(key, el as Renderer);
    } else {
      renderers.delete(key);
    }
  }

  /**
   * Keeps the sidebar label in step with the label/heading control, and — as the
   * legacy modal did — starts a newly chosen sort attribute in its own default
   * direction.
   */
  function onChange(
    source: SourceRow,
    change: UiChange,
    values: UiPayload['values']
  ): void {
    const leaf = change.path.at(-1);

    if (leaf === 'label' || leaf === 'heading') {
      source.label = String(valueAt(values, change.path) ?? '');
    }

    if (leaf === 'attr' && change.path.at(-2) === 'defaultSort' && source.key) {
      const dir = defaultSortDir(
        source,
        change.path,
        valueAt(values, change.path)
      );

      if (dir)
        renderers
          .get(source.key)
          ?.setValue([...change.path.slice(0, -1), 'dir'], dir);
    }
  }

  /** The default direction the server gave the sort attribute option `attr`. */
  function defaultSortDir(
    source: SourceRow,
    path: string[],
    attr: unknown
  ): string | null {
    let dir: string | null = null;

    visitControls(source.ui?.nodes ?? [], (control) => {
      if (!pathsMatch(control.path, path)) return;

      const options = (control.props.options ?? []) as Array<{
        value: unknown;
        defaultDir?: string;
      }>;
      dir = options.find((option) => option.value === attr)?.defaultDir ?? null;
    });

    return dir;
  }

  async function refresh(
    source: SourceRow,
    values: UiPayload['values'],
    scope: string[] = []
  ): Promise<UiPayload> {
    const {data} = await actionClient.post(ElementSourcesController.ui().url, {
      elementType: props.elementType,
      sourceKey: source.key,
      type: source.type,
      // `values` is already relative to `scope`, unlike currentValues().
      settings: values,
      scope,
    });

    if (!data.ui) {
      throw new Error('The source did not return a UI payload.');
    }

    // Server-rendered controls register their assets on every render.
    return data.ui;
  }

  async function add(type: 'heading' | 'custom'): Promise<void> {
    const key = `${type}:${craft.uuid()}`;

    // Listed straight away, with its settings loading in beside it.
    sources.value.push({
      key,
      type: type as SourceType,
      label: '',
      handle: null,
      page: selectedPage.value ?? '',
      ui: null,
      mounted: false,
    });
    announce(t('Success'));
    void showScreen('settings');

    const selecting = select(key);
    await nextTick();
    rowElement('sources', key)?.scrollIntoView({block: 'nearest'});
    await selecting;
    await nextTick();
    focusFirstInput();
  }

  function focusFirstInput(): void {
    settingsPane.value
      ?.querySelector<HTMLInputElement>('input[type="text"]')
      ?.focus();
  }

  /** Removes a source, moving selection to its nearest neighbor as Craft 5 did. */
  async function remove(key: string): Promise<void> {
    const source = sources.value.find((row) => row.key === key);
    if (!source) return;

    const visible = visibleSources.value;
    const index = visible.indexOf(source);
    const neighbor = visible[index - 1] ?? visible[index + 1];

    sources.value = sources.value.filter((row) => row !== source);
    renderers.delete(key);
    delete errors.value[key];

    if (selectedKey.value === key) {
      await select(neighbor?.key ?? null);
      await nextTick();
      focusElement(settingsPane.value?.querySelector(FOCUSABLE));
    }
  }

  /** A source with no key can't be addressed; fall back to its position. */
  function sourceId(source: SourceRow, index: number): string {
    return source.key ?? `unkeyed-${index}`;
  }

  function sourceActions(source: SourceRow): ActionItem[] {
    if (!source.key) return [];

    const key = source.key;
    const items: ActionItem[] = [];

    // Native sources are disabled rather than deleted — they come from the
    // element type, not from project config.
    if (source.type !== 'native') {
      items.push({
        label:
          source.type === 'heading'
            ? t('Remove heading')
            : t('Delete custom source'),
        variant: 'danger',
        onClick: () => void remove(key),
      });
    }

    return items;
  }

  /**
   * Moves a source — and, for a heading, the sources beneath it — to the end of
   * another page.
   */
  function moveToPage(key: string, page: string): void {
    const moved = moveSourcesToPage(key, page);

    // They're no longer on the page being viewed, so nothing is selected.
    if (moved.some((row) => row.key === selectedKey.value)) void select(null);
  }

  /**
   * A removed page's sources went to `neighbor`, which takes over the selection
   * if the removed page had it — Craft 5's behavior.
   */
  async function onPageRemoved(
    page: PageRow,
    neighbor: PageRow | undefined
  ): Promise<void> {
    if (selectedPage.value === page.name) {
      selectPage(neighbor?.name ?? null);
    }

    // Focus the neighbor's menu, where the removed page's was.
    await nextTick();
    focusElement(
      rowElement('pages', neighbor?.name ?? null)?.querySelector(
        'craft-action-menu [slot="invoker"]'
      )
    );
  }

  /** Views a page's sources, with none selected until one is chosen. */
  function selectPage(name: string | null): void {
    if (name === selectedPage.value) return;

    selectedPage.value = name;
    void select(null);
  }

  function openPage(name: string): void {
    selectPage(name);
    void showScreen('sources');
  }

  function openSource(key: string): void {
    void select(key);
    void showScreen('settings');
  }

  /**
   * A source that was never selected falls through to the payload the server
   * sent, so it round-trips unchanged without having to be mounted.
   */
  function settingsFor(source: SourceRow): Record<string, unknown> {
    if (!source.key) return {};

    // A heading keyed on load has no UI until it's selected; its text is all
    // there is to save.
    if (!source.ui && source.type === 'heading') return {heading: source.label};

    const values =
      renderers.get(source.key)?.currentValues() ?? source.ui?.values ?? {};

    return (
      ((values.sources as Record<string, any>)?.[source.key] as Record<
        string,
        unknown
      >) ?? {}
    );
  }

  async function save(): Promise<void> {
    // Enter submits even while Save is disabled.
    if (saving.value || !loaded.value) return;

    const saveable = sources.value.filter((source) => source.key);
    saving.value = true;
    errors.value = {};

    const landing = landingSource();

    try {
      const {data} = await actionClient.post<{redirect?: string}>(
        ElementSourcesController.store().url,
        {
          elementType: props.elementType,
          landingSource: landing,
          sourceOrder: saveable.map((source) => source.key),
          sources: Object.fromEntries(
            saveable.map((source) => [source.key, settingsFor(source)])
          ),
          ...(multiPage.value
            ? {
                sourcePages: Object.fromEntries(
                  saveable.map((source) => [source.key, source.page])
                ),
                // Key order is page order — store() looks each page up by its
                // index in this object.
                pageSettings: Object.fromEntries(
                  pages.value.map((page) => [
                    page.name,
                    {icon: page.icon ?? ''},
                  ])
                ),
              }
            : {}),
        }
      );

      // The index's sources, columns and view modes all come from what just
      // changed, so the index starts over from the server.
      emit('saved', {sourceKey: landing, url: data.redirect ?? null});
    } catch (error: any) {
      const responseErrors = error?.response?.data?.errors;

      if (responseErrors) {
        setErrors(responseErrors);
      } else {
        Craft.cp?.displayError?.(error?.response?.data?.message);
      }

      saving.value = false;
    }
  }

  /** Whether a source will be listed on the index once saved. */
  function isEnabled(source: SourceRow): boolean {
    if (source.type !== 'native') return true;

    const enabled = settingsFor(source).enabled;

    return !(
      enabled === false ||
      enabled === '' ||
      enabled === '0' ||
      enabled === 0
    );
  }

  /**
   * The source the index should land on after saving: the one last edited, as
   * Craft 5 did, on whichever page it now lives. Failing that — it was disabled,
   * say — the index's own source, then the first on the index's page.
   */
  function landingSource(): string | null {
    const showable = sources.value.filter(
      (source) => source.key && source.type !== 'heading' && isEnabled(source)
    );
    const find = (key: string | null | undefined) =>
      key ? showable.find((source) => source.key === key) : undefined;
    const indexPage = props.page ? pageNameId(props.page) : null;
    const onIndexPage = showable.find(
      (source) =>
        !multiPage.value ||
        indexPage === null ||
        pageNameId(source.page) === indexPage
    );

    return (
      (
        find(selectedKey.value) ??
        find(lastEdited.value) ??
        find(props.sourceKey) ??
        onIndexPage
      )?.key ?? null
    );
  }

  function setErrors(next: Record<string, string | string[]>): void {
    const byKey: Record<string, UiPayload['errors']> = {};

    for (const [path, messages] of Object.entries(next)) {
      const segments = path.split('.');
      const key = segments[1];
      if (!key) continue;

      (byKey[key] ??= []).push({
        path: segments,
        messages: Array.isArray(messages) ? messages : [messages],
      });
    }

    errors.value = byKey;

    // Surface the first source that failed, so its messages are visible.
    const first = Object.keys(byKey)[0];
    if (first) {
      // On whichever page it lives, so its settings sit beside its list.
      const source = sources.value.find((row) => row.key === first);
      if (multiPage.value && source) selectedPage.value = source.page;

      void select(first);
      void showScreen('settings');
    }
  }
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="t('Customize sources')"
    :loading="saving"
    :submit-disabled="!loaded"
    :dismissible="false"
    width="5xl"
    height="40rem"
    resizable
    @close="emit('close')"
    @submit="save"
  >
    <div ref="body" class="cs-body">
      <craft-spinner v-if="loading" class="cs-spinner" />

      <template v-else>
        <CustomizeSourcesColumn
          v-if="multiPage"
          data-screen="pages"
          nav
          :heading="t('Pages')"
          :selected="screen === 'pages'"
        >
          <CustomSourceList
            :items="pages"
            :item-id="(page) => page.name"
            :label="(page) => page.name"
            :icon="(page) => page.icon"
            :selected="selectedPage"
            :actions="pageActions"
            accepts="source"
            :can-drop-into="(page) => page.name !== selectedPage"
            @select="openPage"
            @reorder="reorderPages"
            @drop-into="(page, key) => moveToPage(key, page)"
          />

          <template #footer>
            <craft-button
              type="button"
              icon="plus"
              variant="dashed"
              @click="openPageSettings(null)"
            >
              {{ t('New page') }}
            </craft-button>
          </template>
        </CustomizeSourcesColumn>

        <CustomizeSourcesColumn
          data-screen="sources"
          nav
          :heading="t('Sources')"
          :selected="screen === 'sources'"
          :back="multiPage ? t('Back to pages') : undefined"
          @back="showScreen('pages')"
        >
          <CustomSourceList
            :items="visibleSources"
            :item-id="sourceId"
            :label="(source) => source.label"
            :sublabel="(source) => source.handle"
            :item-type="(source) => source.type"
            :selected="selectedKey"
            :disabled="(source) => !source.key"
            :actions="sourceActions"
            :moves="sourceMoves"
            :span="sourceSpan"
            drag-type="source"
            @select="openSource"
            @reorder="reorderSources"
            @move-to="(key, page) => moveToPage(key, page)"
          />

          <template #footer>
            <ActionMenu
              :label="t('Source actions')"
              :actions="[
                {label: t('New heading'), onClick: () => add('heading')},
                {label: t('New custom source'), onClick: () => add('custom')},
              ]"
            >
              <template #invoker>
                <craft-button
                  slot="invoker"
                  type="button"
                  icon="plus"
                  variant="dashed"
                  :aria-label="t('Source actions')"
                ></craft-button>
              </template>
            </ActionMenu>
          </template>
        </CustomizeSourcesColumn>

        <CustomizeSourcesColumn
          fill
          data-screen="settings"
          :heading="selected?.ui ? selected.label : undefined"
          :selected="screen === 'settings'"
          :back="t('Back to sources')"
          @back="showScreen('sources')"
        >
          <div ref="settingsPane">
            <template v-for="source in sources" :key="source.key">
              <craft-field-group
                v-if="source.mounted && source.ui && source.key"
                v-show="source.key === selectedKey"
              >
                <UiRenderer
                  :ref="(el) => setRenderer(source.key!, el)"
                  :payload="source.ui"
                  :errors="errors[source.key!] ?? []"
                  :refresh="
                    source.ui.refreshable
                      ? (values, scope) => refresh(source, values, scope)
                      : undefined
                  "
                  @change="(change, values) => onChange(source, change, values)"
                />
              </craft-field-group>
            </template>

            <craft-spinner v-if="settingsLoading" />
          </div>
        </CustomizeSourcesColumn>
      </template>
    </div>

    <PageSettingsModal
      :is-active="pageModalActive"
      :page="editingPage"
      :validate-name="validatePageName"
      @close="pageModalActive = false"
      @save="savePage"
    />
  </ModalForm>
</template>

<style scoped lang="scss">
  // Fills the modal and holds the columns to its height, so each scrolls on its
  // own. Not wrapping: a wrapping row is as tall as its tallest column, which
  // would stretch past the modal instead of scrolling — and the narrow layout
  // shows one column at a time rather than stacking them.
  .cs-body {
    display: flex;
    flex-flow: row nowrap;
    align-items: stretch;
    container-type: inline-size;
    flex: 1 1 0;
    min-height: 0;
  }

  .cs-spinner {
    margin: auto;
  }

  // Global, since these are Modal's and ModalForm's; `:has()` keeps them to this modal.
  :global(.content:has(> form > craft-pane > div.cs-body)) {
    overflow-y: hidden;
  }

  :global(form:has(> craft-pane > div.cs-body)),
  :global(craft-pane:has(> div.cs-body)),
  :global(craft-pane:has(> div.cs-body)::part(base)) {
    flex: 1 1 0;
    min-height: 0;
  }

  :global(craft-pane:has(> div.cs-body)::part(body)) {
    display: flex;
    flex-direction: column;
    flex: 1 1 0;
    min-height: 0;
    padding: 0;
  }
</style>
