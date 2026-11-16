import '../../../../css/cp.css';
import '@craftcms/ui/components/tabs/tabs';
import '@craftcms/ui/components/tab/tab';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, ref, shallowRef, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {registerUiComponents} from '../register';
import type {UiControlPayload, UiNodePayload, UiPayload} from '../types';
import UiRenderer from '../UiRenderer.vue';

let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

function textField(path: string[]): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label: path.at(-1), instructions: null, required: false},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
      component: 'craft:text',
      props: {inputType: 'text'},
      path,
      mode: 'editable',
      deltaGroup: ['fields', 'matrix'],
      uis: [],
    } as UiControlPayload,
  } as UiNodePayload;
}

function tab(uid: string, children: UiNodePayload[]): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Tab',
    component: 'craft:tab',
    uid,
    props: {label: uid},
    children,
  };
}

/** A blocks field with one block, laid out by `nodes`. */
function blocksField(
  path: string[],
  uid: string,
  nodes: (scope: string[]) => UiNodePayload[]
): UiNodePayload {
  const scope = [...path, 'entries', uid];

  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label: path.at(-1), instructions: null, required: false},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\NestedElementBlocks',
      component: 'craft:nested-element-blocks',
      props: {
        entryTypes: [{value: 'text', label: 'Text'}],
        addLabel: 'Add an entry',
        minEntries: null,
        maxEntries: null,
      },
      path,
      mode: 'editable',
      deltaGroup: ['fields', 'matrix'],
      nestsUis: true,
      uis: [{scope, refreshable: false, nodes: nodes(scope)}],
    } as UiControlPayload,
  } as UiNodePayload;
}

/**
 * A form with one Matrix field holding one block, whose tabs are `content`,
 * `more`, with another Matrix field whose block has two tabs of its own, and
 * `extra`.
 */
function payload(uid: string): UiPayload {
  return {
    scope: [],
    refreshable: false,
    values: {
      fields: {
        matrix: {
          entries: {
            [uid]: {
              type: 'text',
              heading: 'A',
              body: 'Body',
              note: 'Note',
              inner: {
                entries: {n1: {type: 'text', title: 'T', summary: 'S'}},
                sortOrder: ['n1'],
              },
            },
          },
          sortOrder: [uid],
        },
      },
    },
    nodes: [
      blocksField(['fields', 'matrix'], uid, (scope) => [
        tab('content', [textField([...scope, 'heading'])]),
        tab('more', [
          textField([...scope, 'body']),
          blocksField([...scope, 'inner'], 'n1', (innerScope) => [
            tab('inner-a', [textField([...innerScope, 'title'])]),
            tab('inner-b', [textField([...innerScope, 'summary'])]),
          ]),
        ]),
        tab('extra', [textField([...scope, 'note'])]),
      ]),
    ],
    errors: [],
    globalErrors: [],
  } as UiPayload;
}

function mount(): {
  container: HTMLElement;
  save: () => Promise<void>;
} {
  const container = document.createElement('div');
  document.body.append(container);
  const current = shallowRef(payload('draft-a'));
  const renderer = ref();
  app = createApp({
    render: () => h(UiRenderer, {ref: renderer, payload: current.value}),
  });
  const components = createCpComponentRegistry();
  registerUiComponents(components);
  components.install(app);
  app.mount(container);

  return {
    container,
    async save() {
      renderer.value.setSubmitting(true);
      current.value = payload('saved-a');
      await nextTick();
      renderer.value.advanceBaseline();
      renderer.value.setSubmitting(false);
    },
  };
}

function input(container: HTMLElement, name: string): HTMLInputElement | null {
  return container.querySelector<HTMLInputElement>(`input[name="${name}"]`);
}

/** Selects a strip's tab once the strip has rendered. */
async function selectTab(strip: () => Element | null, index: number) {
  const element = await vi.waitFor(() => {
    const found = strip();
    expect(found?.shadowRoot?.querySelector('[part="tab-group"]')).toBeTruthy();

    return found!;
  });
  [...element.querySelectorAll<HTMLElement>(':scope > craft-tab')][
    index
  ]!.click();
}

async function visibleInput(
  container: HTMLElement,
  name: string
): Promise<HTMLInputElement> {
  return vi.waitFor(() => {
    const element = input(container, name);
    expect(element?.checkVisibility()).toBe(true);

    return element!;
  });
}

/**
 * Applying a draft replaces the blocks created in it, which rebuilds them on
 * their first tabs; focus has to find its way back to the tab it was on.
 */
it('keeps focus in a replaced block’s second tab through a save', async () => {
  const {container, save} = mount();
  const body = (uid: string) => `fields[matrix][entries][${uid}][body]`;

  await selectTab(() => container.querySelector('craft-tabs'), 1);
  const draftInput = await visibleInput(container, body('draft-a'));
  draftInput.focus();
  draftInput.setSelectionRange(2, 2);

  await save();

  await vi.waitFor(() => {
    expect(document.activeElement).toBe(input(container, body('saved-a')));
  });
  expect(input(container, body('saved-a'))!.selectionStart).toBe(2);
  expect(
    (container.querySelector('craft-tabs') as {selectedIndex?: number})
      .selectedIndex
  ).toBe(1);
});

/**
 * Only the tabs leading to the focused field have to be selected again; a
 * strip in another tab isn't rendered yet when focus goes back.
 */
it('keeps focus through a save past a strip in another tab', async () => {
  const {container, save} = mount();
  const note = (uid: string) => `fields[matrix][entries][${uid}][note]`;
  const outerStrip = () => container.querySelector('craft-tabs');

  await selectTab(outerStrip, 1);
  await selectTab(
    () =>
      container.querySelector(
        '[data-matrix-block] [data-matrix-block] craft-tabs'
      ),
    1
  );
  await visibleInput(
    container,
    'fields[matrix][entries][draft-a][inner][entries][n1][summary]'
  );
  await selectTab(outerStrip, 2);
  const draftInput = await visibleInput(container, note('draft-a'));
  draftInput.focus();

  await save();

  await vi.waitFor(() => {
    expect(document.activeElement).toBe(input(container, note('saved-a')));
  });
  expect((outerStrip() as {selectedIndex?: number}).selectedIndex).toBe(2);
});
