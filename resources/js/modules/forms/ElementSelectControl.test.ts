import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {FormControlPayload} from './types';

const stub = vi.hoisted(() => {
  const replaceElement = vi.fn();
  const removeElement = vi.fn();

  class StubElementSelectInput extends HTMLElement {
    replaceElement = replaceElement;
    removeElement = removeElement;
  }

  return {replaceElement, removeElement, StubElementSelectInput};
});

// The real module boots `BaseElementSelectInput` (jQuery + garnish + the
// `Craft` global) as soon as the element connects; the chips' menus only need
// the element's public seam, so the tag is stubbed with it.
vi.mock('@/modules/element-select-input', () => ({
  CraftElementSelectInput: stub.StubElementSelectInput,
}));

if (!customElements.get('craft-element-select-input')) {
  customElements.define(
    'craft-element-select-input',
    stub.StubElementSelectInput
  );
}

// Imported after the mock so the component picks it up.
const {default: ElementSelectControl} =
  await import('./ElementSelectControl.vue');

function control(props: Record<string, unknown> = {}): FormControlPayload<any> {
  return {
    type: 'CraftCms\\Cms\\Form\\Controls\\ElementSelect',
    component: 'craft:element-select',
    props: {
      elementType: 'CraftCms\\Cms\\Elements\\Entry',
      customElement: 'craft-element-select-input',
      elements: [{id: 5, label: 'Some entry'}],
      sources: null,
      criteria: {},
      selectionLabel: 'Add an entry',
      limit: null,
      showSiteMenu: false,
      ...props,
    },
    path: ['related'],
    mode: 'editable',
    deltaGroup: ['related'],
  } as FormControlPayload<any>;
}

describe('ElementSelectControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    // The menu items' `<craft-icon>`s fetch their SVGs; left in flight they're
    // aborted at teardown and reported as unhandled errors.
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
    stub.replaceElement.mockClear();
    stub.removeElement.mockClear();
  });

  async function mount(
    options: {
      props?: Record<string, unknown>;
      editable?: boolean;
    } = {}
  ): Promise<HTMLElement> {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ElementSelectControl, {
          control: control(options.props),
          value: [5],
          editable: options.editable ?? true,
        }),
    });
    app.mount(container);
    await nextTick();

    return container;
  }

  function menus(root: HTMLElement): any[] {
    return [
      ...root.querySelectorAll('craft-chip [slot="suffix"] craft-action-menu'),
    ];
  }

  it('renders exactly one action menu per chip, with Replace and Remove', async () => {
    const root = await mount();

    expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
    expect(menus(root)).toHaveLength(1);
    expect(menus(root)[0].actions.map((action: any) => action.label)).toEqual([
      'Replace',
      'Remove',
    ]);
  });

  it('hands the chip actions off to the element select input', async () => {
    const root = await mount();
    const [replace, remove] = menus(root)[0].actions;

    replace.onClick();
    expect(stub.replaceElement).toHaveBeenCalledWith(5);

    remove.onClick();
    expect(stub.removeElement).toHaveBeenCalledWith(5);
  });

  it('drops Replace when there is no element type to pick from', async () => {
    const root = await mount({props: {elementType: null}});

    expect(menus(root)[0].actions.map((action: any) => action.label)).toEqual([
      'Remove',
    ]);
  });

  it('renders no chip actions when the control is read-only', async () => {
    const root = await mount({editable: false});

    expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
    expect(menus(root)).toHaveLength(0);
  });

  it('leaves the chips’ action menus to itself, not the input', async () => {
    const root = await mount();
    const settings = JSON.parse(
      root
        .querySelector('craft-element-select-input')!
        .getAttribute('settings')!
    );

    expect(settings.showActionMenu).toBe(false);
  });
});
