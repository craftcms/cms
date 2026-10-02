(() => {
  const dashboards = new WeakMap();
  const widgets = new WeakMap();

  window.addEventListener('craft:dashboard-mounted', ({detail: context}) => {
    const previous = window.dashboard;
    const dashboard = {
      widgets: {},
      grid: context.grid,
      get widgetTypes() {
        return context.widgetTypes;
      },
      getTypeInfo(type, property, fallback) {
        const info = context.widgetTypes[type] ?? context.widgets.find((widget) => widget.type === type);

        return property ? (info?.[property] ?? fallback) : info;
      },
      createWidget: context.add,
      showWidgetManager: context.showManager,
    };

    dashboards.set(context.element, {previous, dashboard});
    window.dashboard = dashboard;
  });

  window.addEventListener('craft:dashboard-unmounted', ({detail: {element}}) => {
    const {previous, dashboard} = dashboards.get(element);

    if (window.dashboard === dashboard) {
      window.dashboard = previous;
    }

    dashboards.delete(element);
  });

  window.addEventListener('craft:widget-mounted', ({detail: context}) => {
    widgets.set(context.element, {context, api: null});
  });

  window.addEventListener('craft:widget-content-ready', ({target}) => {
    const element = target.closest('.dashboard-widget');
    const state = widgets.get(element);

    if (!state || state.api) return;

    const {context} = state;
    const api = new window.Craft.Widget(element, null, () => {}, context.widget.settings, context.widget.settingsForm);
    api.removeListener(api.$settingsBtn, 'click');
    api.showSettings = context.showSettings;
    api.hideSettings = context.hideSettings;
    api.destroy = () => {
      if (!state.api) return;

      delete window.dashboard.widgets[context.widget.id];
      window.Garnish.Base.prototype.destroy.call(api);
      window.jQuery(element).removeData('widget');
      state.api = null;
    };
    state.api = api;
  });

  window.addEventListener('craft:widget-unmounting', ({detail: {element}}) => {
    widgets.get(element).api?.destroy();

    widgets.delete(element);
  });
})();
