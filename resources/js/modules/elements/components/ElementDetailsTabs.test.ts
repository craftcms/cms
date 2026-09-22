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
    formActions: [],
    headerActions: [],
    autosaveUrl: '',
    discardDraftUrl: '',
    isProvisionalDraft: false,
    draftId: null,
    canAutosave: false,
    notice: null,
    mergeNotice: null,
    canDiscardDraft: false,
    submitButtonLabel: 'Save',
    actionMenu: [],
    previewTargets: [],
    elementDisplayName: 'Entry',
    activityUrl: null,
    activityTimelineUrl: null,
    activityPageUrl: null,
    updatedTimestamps: {element: null, canonical: null},
    contextMenu: null,
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
  tabs().dispatchEvent(new Event('selected-changed'));
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
            {payload: payload(), activityTimelineVersion: 0},
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
});

describe('ElementDetailsTabs', () => {
  it('merges, filters, and orders registered tabs before and after mounting', async () => {
    const PluginTab = defineComponent({
      props: {
        payload: Object,
        activeTabId: String,
      },
      setup: (props) => () =>
        h(
          'div',
          {class: 'plugin-content'},
          `${props.payload?.elementId}:${props.activeTabId}`
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
              },
              {info: () => h('div', {class: 'info-content'}, 'Info content')}
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

    elementDetailsTabRegistry.register({
      id: 'plugin:details',
      label: 'Plugin details',
      icon: 'puzzle-piece',
      component: PluginTab,
      order: 5,
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
      '1:plugin:details'
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
      '1:info'
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
