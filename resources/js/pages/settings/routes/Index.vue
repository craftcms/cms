<script setup lang="ts">
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
  import {ButtonVariant, t} from '@craftcms/ui';
  import type {Edge} from '@atlaskit/pragmatic-drag-and-drop-hitbox/types';
  import Empty from '@/common/components/Empty.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

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

    router
      .optimistic<{routes: Array<RouteIndexData>}>(({routes}) => ({
        routes: routes.filter(({uid}) => uid !== route.uid),
      }))
      .delete(destroy({uid: route.uid}));
  }

  useAppLayout({title: props.title});
</script>

<template>
  <LayoutSlot name="actions">
    <Link :href="create()">
      <craft-button
        v-if="!readOnly"
        type="button"
        icon="plus"
        :variant="ButtonVariant.Primary"
      >
        {{ t('New route') }}
      </craft-button>
    </Link>
  </LayoutSlot>

  <div v-if="routes.length === 0" class="empty-routes">
    <craft-pane appearance="raised">
      <Empty :label="t('No routes exist yet.')" />
    </craft-pane>
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
      <div v-if="isMultiSite" class="route__site">
        <div class="route-site">
          {{ route.siteName }}
        </div>
      </div>

      <Link :href="edit({uid: route.uid})" class="route__parts">
        <div>
          <span
            v-if="route.uriDisplayHtml"
            v-html="route.uriDisplayHtml"
          ></span>
          <craft-icon v-else name="home" :label="t('Home')"></craft-icon>
        </div>
      </Link>

      <div class="route__icon">
        <craft-icon name="arrow-right" :label="t('Resolves to')"></craft-icon>
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
          :href="edit({uid: route.uid})"
        >
          <craft-icon name="pencil" :label="t('Edit')"></craft-icon>
        </Link>
        <craft-reorder-button
          :ref="(el: any) => setHandleRef(el, route.uid)"
          :position="getRowPosition(index)"
          @reorder="
            (e: CustomEvent<{direction: 'up' | 'down'}>) =>
              handleReorder(
                index,
                e.detail.direction === 'up' ? index - 1 : index + 1
              )
          "
        ></craft-reorder-button>
        <craft-button
          @click="deleteRoute(route)"
          variant="danger-plain"
          size="small"
          icon
        >
          <craft-icon name="trash" :label="t('Delete')"></craft-icon>
        </craft-button>
      </div>

      <DropIndicator contained :edge="routeDropEdge(route.uid)" />
    </div>
  </div>
</template>

<style scoped lang="scss">
  .routes-list {
    display: grid;
    grid-template-areas: 'site parts icon template actions';
    grid-template-columns: auto auto auto 1fr auto;
    gap: var(--c-spacing-sm) 0;
  }

  .route {
    grid-column: 1 / -1;
    display: grid;
    align-items: center;
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
    position: relative;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    background: var(--c-surface-raised);
    grid-template-columns: subgrid;
  }

  .route--readonly {
    cursor: pointer;
    opacity: 0.75;
  }

  .route--dragging {
    opacity: 0.45;
  }

  .route-site {
    background: var(--c-color-neutral-fill-quiet);
    border-radius: var(--c-radius-sm);
    box-shadow: inset 0 0 0 1px var(--c-color-neutral-border-quiet);
    color: var(--c-text-quiet);
    display: inline-flex;
    font-size: var(--c-text-sm);
    padding: 0.125rem 0.35rem;
    white-space: nowrap;
  }

  .route__site {
    grid-area: site;
    padding-inline-end: var(--c-spacing-md);
  }

  .route__icon {
    grid-area: icon;
    padding-inline: var(--c-spacing-md);
  }

  .route__parts {
    align-items: center;
    display: block;
    grid-area: parts;
    word-break: break-word;
    padding-inline-end: var(--c-spacing-md);
  }

  .route__parts ::v-deep(.token) {
    align-items: center;
    background: color-mix(currentColor, transparent 90%);
    border: 1px solid transparent;
    border-radius: var(--c-radius-sm);
    color: currentColor;
    display: inline-flex;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    padding: 0 0.25em;
  }

  .route__template {
    color: var(--c-text-quiet);
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    grid-area: template;
    padding-inline-end: var(--c-spacing-md);
  }

  .route__template span {
    overflow-wrap: anywhere;
  }

  .route__actions {
    align-items: center;
    display: flex;
    gap: var(--c-spacing-xs);
    grid-area: actions;
  }

  @media (max-width: 720px) {
    .route {
      grid-template-areas: 'site actions' 'parts parts' 'template template';
      grid-template-columns: repeat(2, 1fr);
      gap: var(--c-spacing-sm) 0;
    }

    .route__icon {
      display: none;
    }

    .route__actions {
      justify-self: end;
    }
  }
</style>
