import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  provide,
  ref,
  type App,
  type Ref,
} from 'vue';
import {elementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
import {ScreenDetailsOverlayKey} from '@/common/composables/screen';
import ElementDetailsTabs from './ElementDetailsTabs.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));
vi.mock('@/modules/activity/components/ActivityTimeline.vue', () => ({
  default: {render: () => null},
}));
vi.mock('./RevisionsList.vue', () => ({default: {render: () => null}}));

let app: App | undefined;
let container: HTMLElement | undefined;

function payload(): ElementEditPayload {
  return {
    elementId: 1,
    canonicalId: 1,
    elementType: 'craft\\elements\\Entry',
    siteId: 1,
    fieldLayoutId: null,
    title: 'Example entry',
    docTitle: 'Example entry',
    crumbs: [],
    readOnly: false,
    form: null,
    sidebarForm: null,
    metadataHtml: null,
    statusLabelHtml: null,
    saveUrl: '',
    applyDraftUrl: '',
    editorActions: {
      primary: {
        label: 'Save',
        actionUrl: null,
        params: {},
        redirect: null,
        tabId: null,
      },
      menu: [],
      buttons: [],
    },
    autosaveUrl: '',
    discardDraftUrl: '',
    isProvisionalDraft: false,
    draftId: null,
    canAutosave: false,
    notice: null,
    mergeNotice: null,
    canDiscardDraft: false,
    actionMenu: [],
    previewTargets: [],
    elementDisplayName: 'Entry',
    activityUrl: null,
    activityTimelineUrl: null,
    activityPageUrl: null,
    updatedTimestamps: {element: null, canonical: null},
    contextMenu: null,
    workflow: {
      current: null,
      draftReviews: [],
    },
  };
}

function tabIds(): string[] {
  return [...container!.querySelectorAll('craft-tab')].map((tab) => tab.id);
}

function tabs(): HTMLElement & {selectedIndex: number} {
  return container!.querySelector('craft-tabs') as HTMLElement & {
    selectedIndex: number;
  };
}

function select(index: number): void {
  tabs().selectedIndex = index;
  tabs().dispatchEvent(new CustomEvent('craft-tab-show'));
}

function mountWithOverlay(overlaid?: Ref<boolean>): void {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp(
    defineComponent({
      setup() {
        if (overlaid) {
          provide(ScreenDetailsOverlayKey, overlaid);
        }

        return () =>
          h(
            ElementDetailsTabs,
            {
              payload: payload(),
              activityTimelineVersion: 0,
              updatePayload: vi.fn(),
            },
            {info: () => h('div', 'Info content')}
          );
      },
    })
  );
  app.mount(container);
}

afterEach(() => {
  app?.unmount();
  container?.remove();
  app = undefined;
  container = undefined;
  window.history.replaceState({}, '', '/');
});

describe('ElementDetailsTabs', () => {
  it('merges, filters, and orders registered tabs before and after mounting', async () => {
    const PluginTab = defineComponent({
      props: {
        payload: Object,
        active: Boolean,
      },
      setup: (props) => () =>
        h(
          'div',
          {class: 'plugin-content'},
          `${props.payload?.elementId}:${props.active}`
        ),
    });
    const PluginTabActions = defineComponent({
      props: {
        payload: Object,
        active: Boolean,
      },
      setup: (props) => () =>
        h(
          'button',
          {class: 'plugin-actions'},
          `${props.payload?.elementId}:${props.active}`
        ),
    });
    const HiddenTab = defineComponent({render: () => null});
    const overlaid = ref(false);
    const conditionalVisible = ref(true);
    const finalVisible = ref(true);

    elementDetailsTabRegistry.register({
      id: 'plugin:hidden',
      label: 'Hidden',
      icon: 'puzzle-piece',
      component: HiddenTab,
      visible: (element) => element.elementId === null,
    });

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup() {
          provide(ScreenDetailsOverlayKey, overlaid);

          return () =>
            h(
              ElementDetailsTabs,
              {
                payload: payload(),
                activityTimelineVersion: 0,
                updatePayload: vi.fn(),
              },
              {
                info: () => h('div', {class: 'info-content'}, 'Info content'),
              }
            );
        },
      })
    );
    app.mount(container);
    await nextTick();

    expect(tabIds()).toEqual([
      'element-details-tab-info',
      'element-details-tab-revisions',
    ]);

    select(1);
    expect(window.location.hash).toBe('');

    elementDetailsTabRegistry.register({
      id: 'plugin:details',
      label: 'Plugin details',
      icon: 'puzzle-piece',
      component: PluginTab,
      headerActionsComponent: PluginTabActions,
      order: 5,
      props: ({payload, active}) => ({payload, active}),
    });
    await nextTick();

    expect(tabIds()).toEqual([
      'element-details-tab-info',
      'element-details-tab-plugin:details',
      'element-details-tab-revisions',
    ]);

    elementDetailsTabRegistry.register({
      id: 'plugin:conditional',
      label: 'Conditional',
      icon: 'puzzle-piece',
      component: PluginTab,
      order: 6,
      visible: () => conditionalVisible.value,
      props: ({payload, active}) => ({payload, active}),
    });
    await nextTick();

    select(3);
    await nextTick();

    elementDetailsTabRegistry.register({
      id: 'plugin:before-revisions',
      label: 'Before revisions',
      icon: 'puzzle-piece',
      component: HiddenTab,
      order: 15,
    });
    elementDetailsTabRegistry.register({
      id: 'plugin:final',
      label: 'Final',
      icon: 'puzzle-piece',
      component: PluginTab,
      order: 30,
      visible: () => finalVisible.value,
      props: ({payload, active}) => ({payload, active}),
    });
    await nextTick();
    await nextTick();

    expect(tabIds()).toEqual([
      'element-details-tab-info',
      'element-details-tab-plugin:details',
      'element-details-tab-plugin:conditional',
      'element-details-tab-plugin:before-revisions',
      'element-details-tab-revisions',
      'element-details-tab-plugin:final',
    ]);
    expect(tabs().selectedIndex).toBe(4);

    select(1);
    await nextTick();

    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:true'
    );
    expect(container.querySelector('.plugin-actions')?.textContent).toBe(
      '1:true'
    );

    select(2);
    conditionalVisible.value = false;
    await nextTick();
    await nextTick();

    expect(tabIds()).toEqual([
      'element-details-tab-info',
      'element-details-tab-plugin:details',
      'element-details-tab-plugin:before-revisions',
      'element-details-tab-revisions',
      'element-details-tab-plugin:final',
    ]);
    expect(tabs().selectedIndex).toBe(0);
    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:false'
    );

    select(4);
    finalVisible.value = false;
    await nextTick();
    await nextTick();

    expect(tabIds()).toEqual([
      'element-details-tab-info',
      'element-details-tab-plugin:details',
      'element-details-tab-plugin:before-revisions',
      'element-details-tab-revisions',
    ]);
    expect(tabs().selectedIndex).toBe(0);

    overlaid.value = true;
    await nextTick();
    expect(tabs().selectedIndex).toBe(-1);
  });

  it('persists the selected full-page tab in the URL', async () => {
    elementDetailsTabRegistry.register({
      id: 'workflow',
      label: 'Workflow',
      icon: 'clipboard-list-check',
      component: defineComponent({render: () => null}),
      order: 5,
    });
    window.history.replaceState({}, '', '/entries/1#workflow');

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(ElementDetailsTabs, {
      payload: payload(),
      activityTimelineVersion: 0,
      updatePayload: vi.fn(),
      syncLocationHash: true,
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await nextTick();
    await nextTick();

    const workflowIndex = tabIds().indexOf('element-details-tab-workflow');
    const revisionsIndex = tabIds().indexOf('element-details-tab-revisions');

    expect(tabs().selectedIndex).toBe(workflowIndex);

    select(revisionsIndex);
    expect(window.location.hash).toBe('#revisions');

    window.location.hash = 'workflow';
    window.dispatchEvent(new HashChangeEvent('hashchange'));
    await nextTick();
    await nextTick();
    expect(tabs().selectedIndex).toBe(workflowIndex);
  });

  it('allows its host to select a visible tab', async () => {
    elementDetailsTabRegistry.register({
      id: 'host-selectable',
      label: 'Host selectable',
      icon: 'clipboard-list-check',
      component: defineComponent({render: () => null}),
      order: 5,
    });
    const detailsTabs = ref<{select: (tabId: string) => void} | null>(null);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup: () => () =>
          h(ElementDetailsTabs, {
            ref: detailsTabs,
            payload: payload(),
            activityTimelineVersion: 0,
            updatePayload: vi.fn(),
            syncLocationHash: true,
          }),
      })
    );
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await nextTick();

    detailsTabs.value?.select('host-selectable');
    await nextTick();
    await nextTick();

    expect(tabs().selectedIndex).toBe(
      tabIds().indexOf('element-details-tab-host-selectable')
    );
    expect(window.location.hash).toBe('#host-selectable');
  });

  it('folds away when the shell overlays the column', async () => {
    const overlaid = ref(false);
    mountWithOverlay(overlaid);
    await nextTick();
    await nextTick();

    expect(tabs().selectedIndex).toBe(0);

    overlaid.value = true;
    await nextTick();

    expect(tabs().selectedIndex).toBe(-1);

    overlaid.value = false;
    await nextTick();

    expect(tabs().selectedIndex).toBe(0);
  });

  it('stays open in a shell that never overlays', async () => {
    mountWithOverlay();
    await nextTick();
    await nextTick();

    expect(tabs().selectedIndex).toBe(0);
  });
});
