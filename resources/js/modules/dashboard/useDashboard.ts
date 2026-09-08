import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {provideHtmlWidgets} from './htmlWidgets';
import {jq} from '@/common/utils/jquery';

import {
  computed,
  nextTick,
  onUnmounted,
  onMounted,
  ref,
  shallowRef,
  watch,
} from 'vue';
import {actionClient, t} from '@craftcms/ui';
import {router} from '@inertiajs/vue3';
import {Grid} from '@/modules/grid/grid';
import {
  store,
  deleteMethod,
  reorder as reorderWidgets,
  updateColspan,
} from '@actions/Dashboard/WidgetsController';
import type {DashboardWidget, WidgetType} from './types';

export function useDashboard(props: {
  widgets: DashboardWidget[];
  widgetTypes: Record<string, WidgetType>;
}) {
  const {flash} = useFlashMessages();

  provideHtmlWidgets();

  const widgets = shallowRef(props.widgets);
  const container = ref<HTMLElement>();
  const ready = ref(false);
  const managing = ref(false);
  const busy = ref(false);
  const columns = ref(4);
  const deleted = shallowRef<DashboardWidget>();
  let nextId = -1;
  let grid: Grid;
  let mountedElement: HTMLElement;
  const actions = computed(() =>
    Object.entries(props.widgetTypes)
      .filter(([, type]) => type.selectable)
      .map(([type, info]) => ({label: info.name, onClick: () => add(type)}))
  );
  const savedWidgets = computed(() =>
    widgets.value.filter((widget) => widget.id > 0)
  );

  onMounted(async () => {
    mountedElement = container.value!;
    grid = new Grid(mountedElement, {
      maxCols: 4,
      onRefreshCols: () => {
        columns.value = grid?.totalCols ?? 4;
      },
    });
    window.dispatchEvent(
      new CustomEvent('craft:dashboard-mounted', {
        detail: {
          element: container.value!,
          grid,
          get widgets() {
            return widgets.value;
          },
          get widgetTypes() {
            return props.widgetTypes;
          },
          add,
          showManager: () => {
            managing.value = true;
          },
        },
      })
    );
    ready.value = true;

    await nextTick();
    refreshGrid();
  });

  onUnmounted(() => {
    ready.value = false;
    grid?.destroy();
    window.dispatchEvent(
      new CustomEvent('craft:dashboard-unmounted', {
        detail: {element: mountedElement},
      })
    );
  });

  watch(
    () => props.widgets,
    (value) => {
      widgets.value = value;
    }
  );

  watch(widgets, async () => {
    await nextTick();
    refreshGrid();
  });

  function refreshGrid() {
    if (!grid) return;

    grid.$items = jq()!(container.value!).children('.item');
    grid.$items.each((_: number, element: HTMLElement) =>
      jq()!(element).data('colspan', Number(element.dataset.colspan))
    );
    grid.setItems();
    grid.refreshCols(true);
    if (!grid.items.length) grid.$container.height('auto');
  }

  function refreshTypes() {
    router.reload({only: ['widgetTypes']});
  }

  function saved(id: number, widget: DashboardWidget | false) {
    widgets.value = widgets.value.flatMap((current) =>
      current.id === id ? (widget ? [widget] : []) : [current]
    );
    refreshTypes();
  }

  function cancel(id: number) {
    widgets.value = widgets.value.filter((widget) => widget.id !== id);
  }

  async function add(type: string) {
    const info = props.widgetTypes[type]!;

    if (info.settingsForm) {
      const id = nextId--;
      widgets.value = [
        ...widgets.value,
        {
          id,
          type,
          name: info.name,
          title: info.name,
          subtitle: null,
          colspan: 1,
          maxColspan: info.maxColspan ?? 4,
          settings: {},
          settingsForm: JSON.parse(
            JSON.stringify(info.settingsForm).replaceAll(
              '__NAMESPACE__',
              `newwidget${-id}-settings`
            )
          ),
          component: null,
          data: null,
          fragment: {html: '', headHtml: '', bodyHtml: ''},
        },
      ];
      return;
    }

    if (busy.value) return;

    busy.value = true;

    try {
      const {data} = await actionClient.post(store.url(), {type});
      if (data.info) widgets.value = [...widgets.value, data.info];
      refreshTypes();
    } catch {
      flash('error', t('Couldn’t save widget.'));
    } finally {
      busy.value = false;
    }
  }

  async function remove(widget: DashboardWidget) {
    if (busy.value) return;

    busy.value = true;

    try {
      await actionClient.post(deleteMethod.url(), {id: widget.id});

      cancel(widget.id);
      deleted.value = widget;
      refreshTypes();
    } catch {
      flash('error', t('Couldn’t delete widget.'));
    } finally {
      busy.value = false;
    }
  }

  async function undo() {
    if (!deleted.value || busy.value) return;

    busy.value = true;

    try {
      const {data} = await actionClient.post(store.url(), {
        type: deleted.value.type,
        settings: deleted.value.settings,
      });

      if (data.info) widgets.value = [...widgets.value, data.info];
      deleted.value = undefined;
      refreshTypes();
    } catch {
      flash('error', t('Couldn’t save widget.'));
    } finally {
      busy.value = false;
    }
  }

  async function reorder(from: number, to: number) {
    if (busy.value || from === to) return;

    const order = [...savedWidgets.value];
    order.splice(to, 0, order.splice(from, 1)[0]!);
    busy.value = true;

    try {
      await actionClient.post(reorderWidgets.url(), {
        ids: JSON.stringify(order.map((widget) => widget.id)),
      });

      widgets.value = [
        ...order,
        ...widgets.value.filter((widget) => widget.id < 0),
      ];
    } catch {
      flash('error', t('Couldn’t reorder widgets.'));
    } finally {
      busy.value = false;
    }
  }

  async function resize(widget: DashboardWidget, colspan: number) {
    if (busy.value) return;

    busy.value = true;

    try {
      await actionClient.post(updateColspan.url(), {id: widget.id, colspan});

      widgets.value = widgets.value.map((current) =>
        current.id === widget.id ? {...current, colspan} : current
      );
    } catch {
      flash('error', t('Couldn’t save widget.'));
    } finally {
      busy.value = false;
    }
  }

  return {
    widgets,
    savedWidgets,
    container,
    ready,
    managing,
    busy,
    columns,
    deleted,
    actions,
    saved,
    cancel,
    remove,
    undo,
    reorder,
    resize,
    refreshGrid,
  };
}
