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
import {DetailsScreen} from '@/test/DetailsScreen';
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
    draftIsSaved: false,
    canAutosave: false,
    notice: null,
    mergeNotice: null,
    canDiscardDraft: false,
    actionMenu: [],
    previewTargets: [],
    elementDisplayName: 'Entry',
    activityUrl: null,
    activityTimelineUrl: null,
    aiAssistant: null,
    activityPageUrl: null,
    updatedTimestamps: {element: null, canonical: null},
    contextMenu: null,
    workflow: {
      current: null,
      draftReviews: [],
    },
    release: null,
    releaseOptions: [],
    canCreateRelease: false,
    canAddToRelease: false,
    releaseTimeZone: 'UTC',
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
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
}

/** Opens a panel from its trigger, leaving an already-open one alone. */
async function select(index: number): Promise<void> {
  const trigger = triggers()[index]!;

  if (trigger.getAttribute('aria-expanded') !== 'true') {
    trigger.click();
  }

  await settle();
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
            h(DetailsScreen, () =>
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
              )
            );
        },
      })
    );
    app.mount(container);
    await settle();

    expect(panelIds()).toEqual([
      'details-panel-info',
      'details-panel-revisions',
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
    await settle();

    expect(panelIds()).toEqual([
      'details-panel-info',
      'details-panel-plugin:details',
      'details-panel-revisions',
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
    await settle();

    await select(3);
    await settle();

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
    await settle();

    expect(panelIds()).toEqual([
      'details-panel-info',
      'details-panel-plugin:details',
      'details-panel-plugin:conditional',
      'details-panel-plugin:before-revisions',
      'details-panel-revisions',
      'details-panel-plugin:final',
    ]);
    expect(openIndex()).toBe(4);

    await select(1);
    await settle();

    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:true:function'
    );
    expect(container.querySelector('.plugin-actions')?.textContent).toBe(
      '1:true'
    );

    await select(2);
    conditionalVisible.value = false;
    await settle();

    expect(panelIds()).toEqual([
      'details-panel-info',
      'details-panel-plugin:details',
      'details-panel-plugin:before-revisions',
      'details-panel-revisions',
      'details-panel-plugin:final',
    ]);
    expect(openIndex()).toBe(0);
    expect(container.querySelector('.plugin-content')?.textContent).toBe(
      '1:false:function'
    );

    await select(4);
    finalVisible.value = false;
    await settle();

    expect(panelIds()).toEqual([
      'details-panel-info',
      'details-panel-plugin:details',
      'details-panel-plugin:before-revisions',
      'details-panel-revisions',
    ]);
    expect(openIndex()).toBe(0);

    overlaid.value = true;
    await settle();
    expect(openIndex()).toBe(-1);
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
          h(DetailsScreen, () =>
            h(ElementDetailsPanels, {
              ref: detailsPanels,
              payload: payload(),
              activityTimelineVersion: 0,
              updatePayload: vi.fn(),
              submitAction,
            })
          ),
      })
    );
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await settle();

    detailsPanels.value?.select('host-selectable');
    await settle();

    expect(openIndex()).toBe(
      panelIds().indexOf('details-panel-host-selectable')
    );
  });
});
