<script setup lang="ts">
  /**
   * The "Customize sources" modal: a source list on the left and the selected
   * source's settings on the right.
   *
   * Each source's settings are a Form payload built by
   * `ElementSourcesController::show()` and namespaced at `sources.<key>`, so a
   * FormRenderer per source produces exactly the shape `store()` reads back.
   */
  import {computed, nextTick, ref, watch} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import ElementSourcesController from '@actions/Elements/ElementSourcesController';
  import ModalForm from '@/common/components/ModalForm.vue';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
  import type {FormChange, FormPayload} from '@/modules/forms/types';
  import {valueAt} from '@/modules/forms/runtime';
  import PagesSidebar from './PagesSidebar.vue';
  import SourcesSidebar from './SourcesSidebar.vue';
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

  const emit = defineEmits<{(e: 'close'): void}>();

  type Renderer = {currentValues(): FormPayload['values']};

  // These live on the legacy `Craft` global and aren't described by CraftStatic.
  const craft = Craft as typeof Craft & {
    uuid(): string;
    appendHeadHtml(html: string): Promise<void>;
    appendBodyHtml(html: string): Promise<void>;
  };

  const loading = ref(false);
  const saving = ref(false);
  const multiPage = ref(false);
  const sources = ref<SourceRow[]>([]);
  const pages = ref<PageRow[]>([]);
  const selectedKey = ref<string | null>(null);
  const selectedPage = ref<string | null>(null);
  const errors = ref<Record<string, FormPayload['errors']>>({});
  const renderers = new Map<string, Renderer>();

  const selected = computed(() =>
    sources.value.find((source) => source.key === selectedKey.value)
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
    renderers.clear();
    errors.value = {};

    try {
      const {data} = await actionClient.post<SourcesResponse>(
        ElementSourcesController.show().url,
        {
          elementType: props.elementType,
        }
      );

      multiPage.value = data.multiPage;
      sources.value = data.sources.map((source) => ({
        // ElementSources synthesizes a keyless blank heading to separate
        // customized sources from the rest. It's regenerated on every read, so
        // it has no settings and must not be saved back.
        key: source.key,
        type: source.type,
        label:
          (source.type === 'heading' ? source.heading : source.label) ?? '',
        page: source.page ?? '',
        form: source.form,
        mounted: false,
      }));
      pages.value = pageRows(data);
      selectedPage.value = initialPage();
      void select(initialSource());
    } finally {
      loading.value = false;
    }
  }

  function pageRows(data: SourcesResponse): PageRow[] {
    if (!data.multiPage) return [];

    const names: string[] = [];
    for (const source of data.sources) {
      if (source.page && !names.includes(source.page)) names.push(source.page);
    }

    return names.map((name) => ({
      name,
      icon: data.pageSettings?.[name]?.icon ?? null,
    }));
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

    source.form ??= await fetchForm(source.key, source.type);
    // Settings are built on first select and kept mounted afterwards, so
    // unsaved edits and server-rendered controls survive switching sources.
    source.mounted = true;
  }

  async function fetchForm(
    sourceKey: string,
    type: SourceType
  ): Promise<FormPayload> {
    const {data} = await actionClient.post(
      ElementSourcesController.form().url,
      {
        elementType: props.elementType,
        sourceKey,
        type,
      }
    );

    await craft.appendHeadHtml(data.headHtml);
    await craft.appendBodyHtml(data.bodyHtml);

    return data.form;
  }

  function setRenderer(key: string, el: unknown): void {
    if (el) {
      renderers.set(key, el as Renderer);
    } else {
      renderers.delete(key);
    }
  }

  /** Keeps the sidebar label in step with the label/heading control. */
  function onChange(
    source: SourceRow,
    change: FormChange,
    values: FormPayload['values']
  ): void {
    const leaf = change.path.at(-1);

    if (leaf === 'label' || leaf === 'heading') {
      source.label = String(valueAt(values, change.path) ?? '');
    }
  }

  async function refresh(
    source: SourceRow,
    values: FormPayload['values'],
    scope: string[] = []
  ): Promise<FormPayload> {
    const {data} = await actionClient.post(
      ElementSourcesController.form().url,
      {
        elementType: props.elementType,
        sourceKey: source.key,
        type: source.type,
        // `values` is already relative to `scope`, unlike currentValues().
        settings: values,
        scope,
      }
    );

    if (!data.form) {
      throw new Error('The source did not return a Form payload.');
    }

    // Server-rendered controls register their assets on every render.
    await craft.appendHeadHtml(data.headHtml);
    await craft.appendBodyHtml(data.bodyHtml);

    return data.form;
  }

  async function add(type: 'heading' | 'custom'): Promise<void> {
    const key = `${type}:${craft.uuid()}`;

    sources.value.push({
      key,
      type: type as SourceType,
      label: '',
      page: selectedPage.value ?? '',
      form: await fetchForm(key, type),
      mounted: true,
    });
    await select(key);
    await nextTick();
    focusFirstInput();
  }

  function focusFirstInput(): void {
    settingsPane.value
      ?.querySelector<HTMLInputElement>('input[type="text"]')
      ?.focus();
  }

  function remove(key: string): void {
    const index = sources.value.findIndex((source) => source.key === key);
    if (index === -1) return;

    sources.value.splice(index, 1);
    renderers.delete(key);
    delete errors.value[key];

    if (selectedKey.value === key) void select(initialSource());
  }

  function reorderSources(from: number, to: number): void {
    const [moved] = sources.value.splice(from, 1);
    if (moved) sources.value.splice(to, 0, moved);
  }

  function moveToPage(key: string, page: string): void {
    const source = sources.value.find((row) => row.key === key);
    if (!source) return;

    source.page = page;
    // The source is no longer on the page being viewed.
    if (selectedKey.value === key) void select(initialSource());
  }

  function reorderPages(from: number, to: number): void {
    const [moved] = pages.value.splice(from, 1);
    if (moved) pages.value.splice(to, 0, moved);
  }

  function addPage(name: string, icon: string | null): void {
    pages.value.push({name, icon});
    selectedPage.value = name;
    void select(null);
  }

  function updatePage(page: PageRow, name: string, icon: string | null): void {
    const previous = page.name;
    page.icon = icon;
    page.name = name;

    // A page's name is its identity, so every source on it has to follow.
    for (const source of sources.value) {
      if (source.page === previous) source.page = name;
    }

    if (selectedPage.value === previous) selectedPage.value = name;
  }

  function removePage(page: PageRow): void {
    const index = pages.value.indexOf(page);
    if (index === -1) return;

    pages.value.splice(index, 1);

    // Reparent its sources to the neighbouring page rather than orphaning them.
    const neighbour = pages.value[index] ?? pages.value[index - 1];
    if (neighbour) {
      for (const source of sources.value) {
        if (source.page === page.name) source.page = neighbour.name;
      }
    }

    if (selectedPage.value === page.name) {
      selectedPage.value = neighbour?.name ?? null;
      void select(initialSource());
    }
  }

  function selectPage(name: string): void {
    selectedPage.value = name;
    void select(initialSource());
  }

  /**
   * A source that was never selected falls through to the payload the server
   * sent, so it round-trips unchanged without having to be mounted.
   */
  function settingsFor(source: SourceRow): Record<string, unknown> {
    if (!source.key) return {};

    const values =
      renderers.get(source.key)?.currentValues() ?? source.form?.values ?? {};

    return (
      ((values.sources as Record<string, any>)?.[source.key] as Record<
        string,
        unknown
      >) ?? {}
    );
  }

  async function save(): Promise<void> {
    if (saving.value) return;

    const saveable = sources.value.filter((source) => source.key);
    saving.value = true;
    errors.value = {};

    try {
      await actionClient.post(ElementSourcesController.store().url, {
        elementType: props.elementType,
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
                pages.value.map((page) => [page.name, {icon: page.icon ?? ''}])
              ),
            }
          : {}),
      });

      // The index's sources, columns and view modes all come from what just
      // changed, so start over from the server.
      window.location.reload();
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

  function setErrors(next: Record<string, string | string[]>): void {
    const byKey: Record<string, FormPayload['errors']> = {};

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
    if (first) void select(first);
  }

  const settingsPane = ref<HTMLElement | null>(null);
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="t('Customize sources')"
    :loading="saving"
    width="5xl"
    resizable
    @close="emit('close')"
    @submit="save"
  >
    <craft-spinner v-if="loading" />

    <div v-else class="cs-body">
      <div v-if="multiPage" class="cs-sidebar">
        <h2 class="cs-sidebar__heading">{{ t('Pages') }}</h2>
        <PagesSidebar
          :pages="pages"
          :selected="selectedPage"
          @select="selectPage"
          @reorder="reorderPages"
          @add="addPage"
          @update="updatePage"
          @remove="removePage"
        />
      </div>

      <div class="cs-sidebar">
        <h2 class="cs-sidebar__heading">{{ t('Sources') }}</h2>
        <SourcesSidebar
          :sources="sources"
          :pages="pages"
          :selected-key="selectedKey"
          :page="multiPage ? selectedPage : null"
          @select="select"
          @reorder="reorderSources"
          @remove="remove"
          @move-to-page="moveToPage"
          @add="add"
        />
      </div>

      <div ref="settingsPane" class="cs-settings">
        <template v-for="source in sources" :key="source.key">
          <div
            v-if="source.mounted && source.form && source.key"
            v-show="source.key === selectedKey"
          >
            <FormRenderer
              :ref="(el) => setRenderer(source.key!, el)"
              :payload="source.form"
              :errors="errors[source.key!] ?? []"
              :refresh="
                source.form.refreshable
                  ? (values, scope) => refresh(source, values, scope)
                  : undefined
              "
              @change="(change, values) => onChange(source, change, values)"
            />
          </div>
        </template>

        <craft-spinner v-if="selected && !selected.form" />
      </div>
    </div>
  </ModalForm>
</template>

<style scoped lang="scss">
  .cs-body {
    display: flex;
    // Wrapping is what the narrow layout below uses to stack the panes.
    flex-flow: row wrap;
    align-items: stretch;
    gap: var(--c-spacing-l);
    container-type: inline-size;
  }

  .cs-sidebar {
    flex: 0 0 200px;
  }

  .cs-sidebar__heading {
    margin-block: 0 var(--c-spacing-s);
    font-size: var(--c-font-size-sm);
  }

  .cs-settings {
    flex: 1;
    min-width: 0;
  }

  // Stack rather than squeeze three columns into a narrow modal. The query has to
  // target the children: an element can't match a container query on itself, and
  // `.cs-body` is the container.
  @container (max-width: 699px) {
    .cs-sidebar,
    .cs-settings {
      flex-basis: 100%;
    }
  }
</style>
