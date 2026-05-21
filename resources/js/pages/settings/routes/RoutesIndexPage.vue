<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import DropIndicator from '@/components/DropIndicator.vue';
  import {useReorderableItems} from '@/composables/useReorderableItems';
  import type {RouteIndexData} from './types';
  import {
    create,
    destroy,
    edit,
    reorder,
  } from '@actions/Settings/RoutesController';
  import {router, Link} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';

  const props = defineProps<{
    title: string;
    routes: Array<RouteIndexData>;
    isMultiSite: boolean;
    readOnly?: boolean;
  }>();

  const {setItemRef, setHandleRef, getDragState, getDropState} =
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
      {{ t('No routes exist yet.') }}
    </div>

    <div v-else class="routes-list">
      <Link
        as="article"
        :href="edit(route.uid)"
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
          <span v-if="isMultiSite" class="route__site">
            {{ route.siteName }}
          </span>
          <span class="route__parts" v-html="route.uriDisplayHtml"></span>
        </div>

        <div class="route__template">
          <craft-icon name="template"></craft-icon>
          <span>{{ route.template }}</span>
        </div>

        <div class="route__actions" v-if="!readOnly" @click.stop>
          <Link
            as="craft-button"
            size="small"
            appearance="plain"
            :href="edit(route.uid)"
          >
            <craft-icon name="pencil" :label="t('Edit')"></craft-icon>
          </Link>

          <craft-action-menu>
            <craft-button slot="invoker" size="small" appearance="plain">
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
              <craft-action-item
                icon="trash"
                variant="danger"
                @click="deleteRoute(route)"
              >
                {{ t('Delete') }}
              </craft-action-item>
            </div>
          </craft-action-menu>

          <span
            :ref="(el) => setHandleRef(el, route.uid)"
            class="route__reorder"
          >
            <craft-button size="small" appearance="plain" @click.prevent>
              <craft-icon
                name="custom-icons/grip-dots"
                :label="t('Reorder')"
              ></craft-icon>
            </craft-button>
          </span>
        </div>

        <DropIndicator contained :edge="routeDropEdge(route.uid)" />
      </Link>
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

  .route__reorder {
    display: inline-flex;
  }

  .route__reorder craft-button {
    cursor: move;
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
