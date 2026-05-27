<script setup lang="ts">
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import DropIndicator from '@/common/components/DropIndicator.vue';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import type {RouteIndexData} from './types';
  import {
    create,
    destroy,
    edit,
    reorder,
  } from '@actions/Settings/RoutesController';
  import {Link, router} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import Empty from '@/common/components/Empty.vue';
  import Pane from '@/common/components/Pane.vue';
  import ReorderButton from '@/common/components/ReorderButton.vue';

  const props = defineProps<{
    title: string;
    routes: Array<RouteIndexData>;
    isMultiSite: boolean;
    readOnly?: boolean;
  }>();

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.routes.map((route) => route.uid),
      enabled: () => !props.readOnly && props.routes.length > 1,
      onReorder: handleReorder,
    });

  function routeDropEdge(routeUid: string): Edge | null {
    const state = getDropState(routeUid);

    return state.type === 'is-over' ? state.closestEdge : null;
  }

  function reorderedRoutes(
    routes: Array<RouteIndexData>,
    startIndex: number,
    finishIndex: number
  ): Array<RouteIndexData> {
    const newRoutes = [...routes];
    const [route] = newRoutes.splice(startIndex, 1);
    newRoutes.splice(finishIndex, 0, route!);

    return newRoutes;
  }

  function handleReorder(startIndex: number, finishIndex: number) {
    const routes = reorderedRoutes(props.routes, startIndex, finishIndex);

    router
      .optimistic<{routes: Array<RouteIndexData>}>(() => {
        return {
          routes,
        };
      })
      .post(
        reorder(),
        {
          routeUids: routes.map((route) => route.uid),
        },
        {
          preserveScroll: true,
          preserveState: true,
        }
      );
  }

  function deleteRoute(route: RouteIndexData) {
    if (!confirm(t('Are you sure you want to delete this route?'))) {
      return;
    }

    router.delete(destroy(route.uid));
  }
</script>

<template>
  <AppLayout :title="title">
    <template #actions>
      <Link :href="create()">
        <craft-button v-if="!readOnly" type="button" variant="primary">
          <craft-icon name="plus" slot="prefix"></craft-icon>
          {{ t('New route') }}
        </craft-button>
      </Link>
    </template>

    <div v-if="routes.length === 0" class="empty-routes">
      <Pane appearance="raised">
        <Empty :label="t('No routes exist yet.')" />
      </Pane>
    </div>

    <div v-else class="routes-list">
      <div
        v-for="(route, index) in routes"
        :key="route.uid"
        :ref="(el) => setItemRef(el, route.uid)"
        :class="{
          route: true,
          'route--readonly': readOnly,
          'route--dragging':
            !readOnly && getDragState(route.uid).type === 'is-dragging',
        }"
      >
        <div class="route__uri">
          <Link :href="edit(route.uid)">
            <span v-if="isMultiSite" class="route__site">
              {{ route.siteName }}
            </span>
            <div class="route__parts"></div>
            <span
              v-if="route.uriDisplayHtml"
              v-html="route.uriDisplayHtml"
            ></span>
            <craft-icon v-else name="home" :label="t('Home')"></craft-icon>
          </Link>
        </div>

        <div class="route__icon">
          <div class="route-icon">
            <craft-icon name="arrow-right"></craft-icon>
          </div>
        </div>

        <div class="route__template">
          <craft-icon name="template"></craft-icon>
          <span>{{ route.template }}</span>
        </div>

        <div class="flex-1"></div>

        <div class="flex gap-1 items-center px-2" v-if="!readOnly" @click.stop>
          <Link
            as="craft-button"
            size="small"
            appearance="plain"
            :href="edit(route.uid)"
          >
            <craft-icon name="pencil" :label="t('Edit')"></craft-icon>
          </Link>
          <ReorderButton
            :ref="(el) => setHandleRef(el, route.uid)"
            :position="getRowPosition(index)"
            @click:up="handleReorder(index, index - 1)"
            @click:down="handleReorder(index, index + 1)"
          />
          <craft-button
            @click="deleteRoute(route)"
            variant="danger"
            size="small"
            appearance="plain"
            icon
          >
            <craft-icon name="trash" :label="t('Delete')"></craft-icon>
          </craft-button>
        </div>

        <DropIndicator contained :edge="routeDropEdge(route.uid)" />
      </div>
    </div>
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
    background: var(--c-color-fill-quiet);
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
    cursor: pointer;
    display: flex;
    min-height: 2.5rem;
    position: relative;
  }

  .route--readonly {
    cursor: pointer;
    opacity: 0.75;
  }

  .route--dragging {
    opacity: 0.45;
  }

  .route__uri {
    align-items: center;
    background: var(--c-surface-raised);
    border-radius: var(--c-radius-lg) 0 0 var(--c-radius-lg);
    color: var(--c-text-link);
    display: flex;
    gap: var(--c-spacing-sm);
    min-width: min(26rem, 55%);
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    position: relative;
  }

  .route__icon {
    display: flex;
    flex-direction: column;
    justify-content: center;
    background-image: linear-gradient(
      to right,
      var(--c-surface-raised) 50%,
      transparent 50.1%
    );
  }

  .route-icon {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 2em;
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    border: 1px solid var(--c-color-border-quiet);
    align-self: center;
    background-color: var(--c-surface-raised);
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

  .route__parts ::v-deep(.token) {
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
