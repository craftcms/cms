<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import RouteEditModal from '@/pages/settings/routes/components/RouteEditModal.vue';
  import {useReorderableItems} from '@/composables/useReorderableItems';
  import type {MixedInputPart} from '@/components/form/MixedInput.vue';
  import {reorder} from '@actions/Settings/RoutesController';
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import {ref, shallowRef} from 'vue';

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

  const modalActive = ref(false);
  const editingRoute = shallowRef<RouteData | null>(null);

  const {setItemRef, setHandleRef, getDragState, getDropState} =
    useReorderableItems({
      getItemIds: () => props.routes.map((route) => route.uid),
      enabled: () => !props.readOnly && props.routes.length > 1,
      onReorder: handleReorder,
    });

  function isToken(part: MixedInputPart): part is [string, string] {
    return Array.isArray(part);
  }

  function openModal(route?: RouteData) {
    editingRoute.value = route ?? null;
    modalActive.value = true;
  }

  function closeModal() {
    modalActive.value = false;
    editingRoute.value = null;
  }

  function routeDropEdge(routeUid: string): Edge | null {
    const state = getDropState(routeUid);

    return state.type === 'is-over' ? state.closestEdge : null;
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
        @click="openModal"
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
        @click="!readOnly && openModal(route)"
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
            size="small"
            appearance="plain"
            @click="openModal(route)"
          >
            <craft-icon name="pencil" :label="t('Edit')"></craft-icon>
          </craft-button>

          <craft-action-menu>
            <craft-button
              slot="invoker"
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

    <RouteEditModal
      :is-active="modalActive"
      :route="editingRoute"
      :tokens="tokens"
      :sites="sites"
      :is-multi-site="isMultiSite"
      @close="closeModal"
    />
  </AppLayout>
</template>

<style scoped lang="scss">
  .empty-routes {
    color: var(--c-text-quiet);
  }

  .routes-list {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .route {
    align-items: stretch;
    background: var(--c-color-neutral-fill-quiet);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
    box-shadow: var(--c-shadow-raised);
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
    background: var(--c-surface-default);
    border-radius: var(--c-radius-lg) 0 0 var(--c-radius-lg);
    color: var(--c-text-link);
    display: flex;
    gap: var(--c-spacing-sm);
    min-width: min(26rem, 55%);
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    position: relative;
  }

  .route__site {
    background: var(--c-color-neutral-fill-quiet);
    border-radius: var(--c-radius-sm);
    box-shadow: inset 0 0 0 1px var(--c-color-neutral-border-quiet);
    color: var(--c-text-quiet);
    display: inline-flex;
    font-size: var(--c-text-sm);
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
    color: var(--c-text-quiet);
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
    background: var(--c-color-neutral-fill-normal);
    border: 0;
    border-radius: var(--c-radius-sm);
    color: var(--c-color-neutral-on-normal);
    display: inline-flex;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    gap: 0.25rem;
    line-height: 1.3;
    padding: 0.125rem 0.4rem;
  }

  @media (max-width: 720px) {
    .route {
      display: grid;
    }

    .route__uri {
      border-radius: var(--c-radius-lg) var(--c-radius-lg) 0 0;
      min-width: 0;
    }

    .route__actions {
      justify-content: flex-end;
    }
  }
</style>
