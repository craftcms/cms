<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import MixedInput from '@/components/form/MixedInput.vue';
  import ModalForm from '@/components/ModalForm.vue';
  import {useFlashMessages} from '@/composables/useFlashMessages';
  import {useReorderableItems} from '@/composables/useReorderableItems';
  import type {
    MixedInputPart,
    MixedInputToken,
  } from '@/components/form/MixedInput.vue';
  import {
    destroy,
    reorder,
    store,
    update,
  } from '@actions/Settings/RoutesController';
  import {router, useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import {computed, ref} from 'vue';

  interface RouteData {
    uid: string;
    siteUid: string | null;
    siteName: string;
    uriParts: Array<MixedInputPart>;
    template: string;
    sortOrder: number | null;
  }

  interface RouteSiteOption {
    uid: string;
    name: string;
  }

  interface RouteFormData {
    uriParts: Array<MixedInputPart>;
    template: string;
    siteUid: string | null;
  }

  interface SettingsRoutesPageProps {
    routes: Array<RouteData>;
  }

  const props = defineProps<{
    title: string;
    routes: Array<RouteData>;
    tokens: Record<string, string>;
    sites: Array<RouteSiteOption>;
    isMultiSite: boolean;
    readOnly?: boolean;
  }>();

  const {flash} = useFlashMessages();
  const modalActive = ref(false);
  const editingRoute = ref<RouteData | null>(null);
  const mixedInput = ref<InstanceType<typeof MixedInput> | null>(null);

  const form = useForm<RouteFormData>({
    uriParts: [''],
    template: '',
    siteUid: null,
  });

  const formSiteUid = computed({
    get: () => form.siteUid ?? '',
    set: (siteUid: string) => {
      form.siteUid = siteUid || null;
    },
  });

  const tokenOptions = computed<Array<MixedInputToken>>(() =>
    Object.entries(props.tokens).map(([name, value]) => ({name, value}))
  );

  const canReorder = computed(() => !props.readOnly && props.routes.length > 1);
  const routeIds = computed(() => props.routes.map((route) => route.uid));

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => routeIds.value,
      enabled: () => canReorder.value,
      onReorder: handleReorder,
    });

  function isToken(part: MixedInputPart): part is [string, string] {
    return Array.isArray(part);
  }

  function copyUriParts(parts: Array<MixedInputPart>): Array<MixedInputPart> {
    return parts.map((part) => (isToken(part) ? [part[0], part[1]] : part));
  }

  function openCreateModal() {
    editingRoute.value = null;
    form.clearErrors();
    form.uriParts = [''];
    form.template = '';
    form.siteUid = null;
    modalActive.value = true;
  }

  function openEditModal(route: RouteData) {
    editingRoute.value = route;
    form.clearErrors();
    form.uriParts = copyUriParts(route.uriParts.length ? route.uriParts : ['']);
    form.template = route.template;
    form.siteUid = route.siteUid;
    modalActive.value = true;
  }

  function closeModal() {
    modalActive.value = false;
    editingRoute.value = null;
    form.clearErrors();
  }

  function routeDropEdge(routeUid: string): Edge | null {
    const state = getDropState(routeUid);

    return state.type === 'is-over' ? state.closestEdge : null;
  }

  function addUriToken(token: MixedInputToken) {
    mixedInput.value?.addToken(token);
  }

  function handleUriTokenClick(event: MouseEvent, token: MixedInputToken) {
    if (event.detail === 0) {
      addUriToken(token);
    }
  }

  function normalizedUriParts(): Array<MixedInputPart> {
    const parts = copyUriParts(form.uriParts);

    if (typeof parts[0] === 'string') {
      parts[0] = parts[0].replace(/^\/+/, '');
    }

    return parts.filter((part) =>
      typeof part === 'string' ? part !== '' : true
    );
  }

  function reorderedRoutes(
    routes: Array<RouteData>,
    startIndex: number,
    finishIndex: number
  ): Array<RouteData> | null {
    if (
      startIndex < 0 ||
      startIndex >= routes.length ||
      finishIndex < 0 ||
      finishIndex >= routes.length ||
      finishIndex === startIndex
    ) {
      return null;
    }

    const route = routes[startIndex];

    if (!route) {
      return null;
    }

    const newRoutes = [...routes];
    newRoutes.splice(startIndex, 1);
    newRoutes.splice(finishIndex, 0, route);

    return newRoutes;
  }

  function saveRoute() {
    const uriParts = normalizedUriParts();

    form.clearErrors();

    form
      .transform((data) => ({
        ...data,
        uriParts,
        siteUid: data.siteUid || null,
      }))
      .submit(editingRoute.value ? update(editingRoute.value.uid) : store(), {
        preserveScroll: true,
        onSuccess: () => {
          closeModal();
          flash('success', t('Route saved.'));
        },
        onError: () => {
          flash('error', t('Couldn’t save route.'));
        },
      });
  }

  function deleteRoute(route: RouteData) {
    if (!confirm(t('Are you sure you want to delete this route?'))) {
      return;
    }

    form.delete(destroy(route.uid).url, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
        flash('success', t('Route deleted.'));
      },
    });
  }

  function handleReorder(startIndex: number, finishIndex: number) {
    const newRoutes = reorderedRoutes(props.routes, startIndex, finishIndex);

    if (!newRoutes) {
      return;
    }

    router
      .optimistic<SettingsRoutesPageProps>((pageProps) => {
        const routes = reorderedRoutes(
          pageProps.routes,
          startIndex,
          finishIndex
        );

        return routes ? {routes} : undefined;
      })
      .post(
        reorder(),
        {
          routeUids: newRoutes.map((route) => route.uid),
        },
        {
          preserveScroll: true,
          preserveState: true,
          onSuccess: () => {
            flash('success', t('New route order saved.'));
          },
          onError: () => {
            flash('error', t('Couldn’t save new route order.'));
          },
        }
      );
  }
</script>

<template>
  <AppLayout :title="title">
    <CalloutReadOnly v-if="readOnly" />

    <template #actions>
      <craft-button
        v-if="!readOnly"
        type="button"
        variant="primary"
        @click="openCreateModal"
      >
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New route') }}
      </craft-button>
    </template>

    <div v-if="routes.length === 0" class="empty-routes">
      {{ t('No routes exist yet.') }}
    </div>

    <div v-else class="routes-list">
      <article
        v-for="(route, index) in routes"
        :key="route.uid"
        :ref="(el) => setItemRef(el as HTMLElement | null, route.uid)"
        :class="{
          route: true,
          'route--readonly': readOnly,
          'route--dragging':
            !readOnly && getDragState(route.uid).type === 'is-dragging',
        }"
        @click="!readOnly && openEditModal(route)"
      >
        <div class="route__uri">
          <span v-if="isMultiSite" class="route__site">
            {{ route.siteName }}
          </span>
          <span class="route__parts">
            <template
              v-for="(part, partIndex) in route.uriParts"
              :key="`${route.uid}-${partIndex}`"
            >
              <span v-if="isToken(part)" class="route-token">
                {{ part[0] }}
              </span>
              <span v-else>{{ part }}</span>
            </template>
          </span>
        </div>

        <div class="route__template">
          <craft-icon name="template"></craft-icon>
          <span>{{ route.template }}</span>
        </div>

        <div class="route__actions" v-if="!readOnly" @click.stop>
          <craft-button
            type="button"
            icon
            size="small"
            appearance="plain"
            @click="openEditModal(route)"
          >
            <craft-icon name="pencil" :label="t('Edit')"></craft-icon>
          </craft-button>

          <craft-action-menu>
            <craft-button
              slot="invoker"
              type="button"
              icon
              size="small"
              appearance="plain"
            >
              <craft-icon name="ellipsis" :label="t('Actions')"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item
                v-if="index !== 0"
                icon="arrow-up"
                @click="handleReorder(index, index - 1)"
              >
                {{ t('Move up') }}
              </craft-action-item>
              <craft-action-item
                v-if="index !== routes.length - 1"
                icon="arrow-down"
                @click="handleReorder(index, index + 1)"
              >
                {{ t('Move down') }}
              </craft-action-item>
            </div>
          </craft-action-menu>

          <span
            :ref="(el) => setHandleRef(el as HTMLElement | null, route.uid)"
            class="route__reorder"
          >
            <craft-button
              type="button"
              icon
              size="small"
              appearance="plain"
              @click.prevent
            >
              <craft-icon
                name="custom-icons/grip-dots"
                :label="t('Reorder')"
              ></craft-icon>
            </craft-button>
          </span>
        </div>

        <DropIndicator contained :edge="routeDropEdge(route.uid)" />
      </article>
    </div>

    <ModalForm
      :is-active="modalActive"
      :title="editingRoute ? t('Edit Route') : t('Create a new route')"
      :loading="form.processing"
      :submit-label="t('Save')"
      width="md"
      @close="closeModal"
      @submit="saveRoute"
    >
      <template #header>
        <div class="route-modal-header">
          <h1>
            {{ editingRoute ? t('Edit Route') : t('Create a new route') }}
          </h1>
        </div>
      </template>

      <div class="route-modal">
        <div class="route-uri-field">
          <div class="route-uri-field__label">
            {{ t('If the URI looks like this') }}:
          </div>

          <div class="route-uri-field__controls">
            <MixedInput
              ref="mixedInput"
              v-model="form.uriParts"
              class="route-uri-input"
              :invalid="!!form.errors.uriParts"
              :disabled="form.processing"
              :aria-label="t('URI')"
            >
              <template v-if="form.errors.uriParts" #error>
                <ul class="error-list">
                  <li>{{ form.errors.uriParts }}</li>
                </ul>
              </template>
            </MixedInput>

            <CraftSelect
              v-if="isMultiSite"
              class="route-site-select"
              id="route-site"
              name="siteUid"
              v-model="formSiteUid"
              :disabled="form.processing"
            >
              <select
                slot="input"
                v-model="formSiteUid"
                :aria-label="t('Site')"
              >
                <option value="">{{ t('Global') }}</option>
                <option v-for="site in sites" :key="site.uid" :value="site.uid">
                  {{ site.name }}
                </option>
              </select>
            </CraftSelect>
          </div>

          <div class="route-token-picker">
            <h3>{{ t('Add a token') }}</h3>
            <button
              v-for="token in tokenOptions"
              :key="token.name"
              type="button"
              class="route-token route-token--button"
              :disabled="form.processing"
              @mousedown.prevent="addUriToken(token)"
              @click="handleUriTokenClick($event, token)"
            >
              {{ token.name }}
            </button>
          </div>
        </div>

        <CraftInput
          :label="t('Load this template')"
          id="route-template"
          name="template"
          v-model="form.template"
          dir="ltr"
          :disabled="form.processing"
          :error="form.errors.template"
          required
        />
      </div>

      <template #footer>
        <div class="route-modal-footer">
          <craft-button
            v-if="editingRoute"
            type="button"
            appearance="plain"
            class="route-delete-button"
            :disabled="form.processing"
            @click="deleteRoute(editingRoute)"
          >
            {{ t('Delete') }}
          </craft-button>

          <div class="route-modal-footer__actions">
            <craft-button type="reset" appearance="plain" @click="closeModal">
              {{ t('Cancel') }}
            </craft-button>
            <craft-button
              type="submit"
              variant="primary"
              :loading="form.processing"
            >
              {{ t('Save') }}
            </craft-button>
          </div>
        </div>
      </template>
    </ModalForm>
  </AppLayout>
</template>

<style scoped lang="scss">
  .empty-routes {
    color: var(--fg-subtle);
  }

  .routes-list {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .route {
    align-items: stretch;
    background: var(--gray-050);
    border-radius: var(--radius-lg);
    box-shadow: var(--pane-shadow);
    cursor: pointer;
    display: flex;
    min-height: 2.5rem;
    position: relative;
  }

  .route--readonly {
    cursor: not-allowed;
    opacity: 0.75;
  }

  .route--dragging {
    opacity: 0.45;
  }

  .route__uri {
    align-items: center;
    background: var(--white);
    border-radius: var(--radius-lg) 0 0 var(--radius-lg);
    color: var(--link-color);
    display: flex;
    gap: var(--c-spacing-sm);
    min-width: min(26rem, 55%);
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    position: relative;
  }

  .route__site {
    background: var(--gray-050);
    border-radius: var(--radius-sm);
    box-shadow: inset 0 0 0 1px var(--border-hairline);
    color: var(--fg-subtle);
    display: inline-flex;
    font-size: var(--font-size-sm);
    line-height: 1.2;
    padding: 0.125rem 0.35rem;
    white-space: nowrap;
  }

  .route__parts {
    align-items: center;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.125rem;
    min-width: 0;
    word-break: break-word;
  }

  .route__template {
    align-items: center;
    color: var(--fg-subtle);
    display: flex;
    gap: var(--c-spacing-xs);
    min-width: 0;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
  }

  .route__template span {
    overflow-wrap: anywhere;
  }

  .route__actions {
    align-items: center;
    display: flex;
    gap: var(--c-spacing-xs);
    margin-left: auto;
    padding: var(--c-spacing-xs) var(--c-spacing-sm);
  }

  .route__reorder {
    display: inline-flex;
  }

  .route__reorder craft-button {
    cursor: move;
  }

  .route-token {
    align-items: center;
    background: var(--gray-100);
    border: 0;
    border-radius: var(--radius-sm);
    color: var(--fg);
    display: inline-flex;
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
    gap: 0.25rem;
    line-height: 1.3;
    padding: 0.125rem 0.4rem;
  }

  .route-token--button {
    appearance: none;
    cursor: pointer;
  }

  .route-token--button:focus {
    box-shadow: 0 0 0 1px var(--white);
    outline: 2px solid var(--link-color);
    outline-offset: 1px;
  }

  .route-modal-header {
    background: var(--gray-100);
    border-bottom: 1px solid var(--border-hairline);
    padding: 24px;
  }

  .route-modal-header h1 {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.25;
    margin: 0;
  }

  :deep(.cp-pane:has(.route-modal) .cp-pane__body) {
    padding: 0;
  }

  :deep(.content.w-md:has(.route-modal)) {
    width: 500px;
  }

  .route-modal {
    display: grid;
    gap: 22px;
    padding: 22px 24px 24px;
    width: 100%;
  }

  .route-uri-field {
    display: grid;
    gap: 10px;
  }

  .route-uri-field__label {
    font-weight: 600;
  }

  .route-uri-field__controls {
    align-items: flex-start;
    display: flex;
    gap: 8px;
  }

  .route-uri-input {
    flex: 1 1 auto;
    min-width: 0;
  }

  .route-site-select {
    flex: 0 0 134px;
  }

  .route-site-select :deep(select) {
    min-height: 34px;
    width: 100%;
  }

  .route-token-picker {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: inset 0 1px 3px -1px #bed2e9;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
    padding: 13px 24px 14px;
  }

  .route-token-picker h3 {
    flex-basis: 100%;
    font-size: var(--font-size-sm);
    font-weight: 600;
    margin: 0 0 3px;
    text-align: center;
  }

  .route-token-picker .route-token {
    font-family: inherit;
    font-size: 12px;
    line-height: 16px;
    padding: 2px 7px;
  }

  .route-modal-footer {
    align-items: center;
    background: var(--gray-100);
    border-top: 1px solid var(--border-hairline);
    display: flex;
    gap: var(--c-spacing-md);
    justify-content: space-between;
    min-height: 44px;
    padding: 6px 24px;
  }

  .route-modal-footer__actions {
    display: flex;
    gap: 8px;
    margin-left: auto;
  }

  .route-delete-button {
    color: var(--fg);
  }

  @media (max-width: 720px) {
    .route {
      display: grid;
    }

    .route__uri {
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
      min-width: 0;
    }

    .route__actions {
      justify-content: flex-end;
    }

    .route-site-select {
      flex-basis: auto;
    }

    .route-uri-field__controls {
      display: grid;
    }
  }
</style>
