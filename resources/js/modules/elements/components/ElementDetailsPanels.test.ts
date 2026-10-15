import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {afterEach, beforeAll, describe, expect, it, vi} from 'vite-plus/test';
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
import {elementDetailsPanelRegistry} from '@/bootstrap/element-details-panels';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
import {ScreenDetailsOverlayKey} from '@/common/composables/screen';
import ElementDetailsPanels from './ElementDetailsPanels.vue';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));
vi.mock('@/modules/activity/components/ActivityTimeline.vue', () => ({
  default: {render: () => null},
}));
vi.mock('./RevisionsList.vue', () => ({default: {render: () => null}}));

let app: App | undefined;
let container: HTMLElement | undefined;

beforeAll(() => setIconResolver(() => nothing));
const submitAction = vi.fn();

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
    ui: null,
    sidebarUi: null,
    metadataHtml: null,
    statusLabelHtml: null,
    saveUrl: '',
    applyDraftUrl: '',
    additionalButtons: [],
    editorActions: {
      primary: {
        label: 'Save',
        actionUrl: null,
        params: {},
        redirect: null,
        panelId: null,
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

function triggers(): HTMLButtonElement[] {
  return [
    ...container!.querySelectorAll<HTMLButtonElement>(
      'craft-disclosure > button'
    ),
  ];
}

function panelIds(): string[] {
  return triggers().map((trigger) => trigger.id);
}

function openIndex(): number {
  return triggers().findIndex(
    (trigger) => trigger.getAttribute('aria-expanded') === 'true'
  );
}

async function settle(): Promise<void> {
  await Promise.resolve();
  await nextTick();
  await nextTick();
}

/** Opens a panel from its trigger, leaving an already-open one alone. */
async function select(index: number): Promise<void> {
  const trigger = triggers()[index]!;

  if (trigger.getAttribute('aria-expanded') !== 'true') {
    trigger.click();
  }

  await settle();
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
            ElementDetailsPanels,
            {
              payload: payload(),
              activityTimelineVersion: 0,
              updatePayload: vi.fn(),
              submitAction,
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

describe('ElementDetailsPanels', () => {
  it('merges, filters, and orders registered panels before and after mounting', async () => {
    const PluginPanel = defineComponent({
      props: {
        payload: Object,
        active: Boolean,
        submitAction: Function,
      },
      setup: (props) => () =>
        h(
          'div',
          {class: 'plugin-content'},
          `${props.payload?.elementId}:${props.active}:${typeof props.submitAction}`
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
    const HiddenPanel = defineComponent({render: () => null});
    const overlaid = ref(false);
    const conditionalVisible = ref(true);
    const finalVisible = ref(true);

    elementDetailsPanelRegistry.register({
      id: 'plugin:hidden',
      label: 'Hidden',
      icon: 'puzzle-piece',
      component: HiddenPanel,
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
              ElementDetailsPanels,
              {
                payload: payload(),
                activityTimelineVersion: 0,
                updatePayload: vi.fn(),
                submitAction,
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

    expect(panelIds()).toEqual([
      'element-details-panel-info',
      'element-details-panel-revisions',
    ]);

    await select(1);
    expect(window.location.hash).toBe('');

    elementDetailsPanelRegistry.register({
      id: 'plugin:details',
      label: 'Plugin details',
      icon: 'puzzle-piece',
      component: PluginPanel,
      headerActionsComponent: PluginTabActions,
      order: 5,
      props: ({payload, active, submitAction}) => ({
        payload,
        active,
        submitAction,
      }),
    });
    await nextTick();

    expect(panelIds()).toEqual([
      'element-details-panel-info',
      'element-details-panel-plugin:details',
      'element-details-panel-revisions',
    ]);

    elementDetailsPanelRegistry.register({
      id: 'plugin:conditional',
      label: 'Conditional',
      icon: 'puzzle-piece',
      component: PluginPanel,
      order: 6,
      visible: () => conditionalVisible.value,
      props: ({payload, active}) => ({payload, active}),
    });
    await nextTick();

    await select(3);
    await nextTick();

    elementDetailsPanelRegistry.register({
      id: 'plugin:before-revisions',
      label: 'Before revisions',
      icon: 'puzzle-piece',
      component: HiddenPanel,
      order: 15,
    });
    elementDetailsPanelRegistry.register({
      id: 'plugin:final',
      label: 'Final',
      icon: 'puzzle-piece',
      component: PluginPanel,
      order: 30,
      visible: () => finalVisible.value,
      props: ({payload, active}) => ({payload, active}),
    });
    await nextTick();
    await nextTick();

    expect(panelIds()).toEqual([
      'element-details-panel-info',
      'element-details-panel-plugin:details',
      'element-details-panel-plugin:conditional',
      'element-details-panel-plugin:before-revisions',
      'element-details-panel-revisions',
      'element-details-panel-plugin:final',
    ]);
    expect(openIndex()).toBe(4);

    await select(1);
    await nextTick();

    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:true:function'
    );
    expect(container.querySelector('.plugin-actions')?.textContent).toBe(
      '1:true'
    );

    await select(2);
    conditionalVisible.value = false;
    await nextTick();
    await nextTick();

    expect(panelIds()).toEqual([
      'element-details-panel-info',
      'element-details-panel-plugin:details',
      'element-details-panel-plugin:before-revisions',
      'element-details-panel-revisions',
      'element-details-panel-plugin:final',
    ]);
    expect(openIndex()).toBe(0);
    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:false:function'
    );

    await select(4);
    finalVisible.value = false;
    await nextTick();
    await nextTick();

    expect(panelIds()).toEqual([
      'element-details-panel-info',
      'element-details-panel-plugin:details',
      'element-details-panel-plugin:before-revisions',
      'element-details-panel-revisions',
    ]);
    expect(openIndex()).toBe(0);

    overlaid.value = true;
    await nextTick();
    expect(openIndex()).toBe(-1);
  });

  it('persists the open full-page panel in the URL', async () => {
    elementDetailsPanelRegistry.register({
      id: 'workflow',
      label: 'Workflow',
      icon: 'clipboard-list-check',
      component: defineComponent({render: () => null}),
      order: 5,
    });
    window.history.replaceState({}, '', '/entries/1#workflow');

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(ElementDetailsPanels, {
      payload: payload(),
      activityTimelineVersion: 0,
      updatePayload: vi.fn(),
      submitAction,
      syncLocationHash: true,
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await nextTick();
    await nextTick();

    const workflowIndex = panelIds().indexOf('element-details-panel-workflow');
    const revisionsIndex = panelIds().indexOf(
      'element-details-panel-revisions'
    );

    expect(openIndex()).toBe(workflowIndex);

    await select(revisionsIndex);
    expect(window.location.hash).toBe('#revisions');

    window.location.hash = 'workflow';
    window.dispatchEvent(new HashChangeEvent('hashchange'));
    await nextTick();
    await nextTick();
    expect(openIndex()).toBe(workflowIndex);
  });

  it('allows its host to open a visible panel', async () => {
    elementDetailsPanelRegistry.register({
      id: 'host-selectable',
      label: 'Host selectable',
      icon: 'clipboard-list-check',
      component: defineComponent({render: () => null}),
      order: 5,
    });
    const detailsPanels = ref<{select: (panelId: string) => void} | null>(null);

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(
      defineComponent({
        setup: () => () =>
          h(ElementDetailsPanels, {
            ref: detailsPanels,
            payload: payload(),
            activityTimelineVersion: 0,
            updatePayload: vi.fn(),
            submitAction,
            syncLocationHash: true,
          }),
      })
    );
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await nextTick();

    detailsPanels.value?.select('host-selectable');
    await nextTick();
    await nextTick();

    expect(openIndex()).toBe(
      panelIds().indexOf('element-details-panel-host-selectable')
    );
    expect(window.location.hash).toBe('#host-selectable');
  });

  it('folds away when the shell overlays the column', async () => {
    const overlaid = ref(false);
    mountWithOverlay(overlaid);
    await nextTick();
    await nextTick();

    expect(openIndex()).toBe(0);

    overlaid.value = true;
    await nextTick();

    expect(openIndex()).toBe(-1);

    overlaid.value = false;
    await nextTick();

    expect(openIndex()).toBe(0);
  });

  it('stays open in a shell that never overlays', async () => {
    mountWithOverlay();
    await nextTick();
    await nextTick();

    expect(openIndex()).toBe(0);
  });
});
