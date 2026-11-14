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

/** A form with one Matrix field, whose blocks each have two tabs. */
function payload(uid: string): UiPayload {
  const field = (handle: string): UiNodePayload =>
    ({
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {label: handle, instructions: null, required: false},
      control: {
        type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
        component: 'craft:text',
        props: {inputType: 'text'},
        path: ['fields', 'matrix', 'entries', uid, handle],
        mode: 'editable',
        deltaGroup: ['fields', 'matrix'],
        uis: [],
      } as UiControlPayload,
    }) as UiNodePayload;
  const tab = (tabUid: string, handle: string): UiNodePayload => ({
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Tab',
    component: 'craft:tab',
    uid: tabUid,
    props: {label: handle},
    children: [field(handle)],
  });

  return {
    scope: [],
    refreshable: false,
    values: {
      fields: {
        matrix: {
          entries: {[uid]: {type: 'text', heading: 'A', body: 'Body'}},
          sortOrder: [uid],
        },
      },
    },
    nodes: [
      {
        type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
        component: 'craft:field',
        props: {label: 'Content', instructions: null, required: false},
        control: {
          type: 'CraftCms\\Cms\\Ui\\Controls\\NestedElementBlocks',
          component: 'craft:nested-element-blocks',
          props: {
            entryTypes: [{value: 'text', label: 'Text'}],
            addLabel: 'Add an entry',
            minEntries: null,
            maxEntries: null,
          },
          path: ['fields', 'matrix'],
          mode: 'editable',
          deltaGroup: ['fields', 'matrix'],
          nestsUis: true,
          uis: [
            {
              scope: ['fields', 'matrix', 'entries', uid],
              refreshable: false,
              nodes: [tab('content', 'heading'), tab('more', 'body')],
            },
          ],
        } as UiControlPayload,
      } as UiNodePayload,
    ],
    errors: [],
    globalErrors: [],
  } as UiPayload;
}

/**
 * Applying a draft replaces the blocks created in it, which rebuilds them on
 * their first tabs; focus has to find its way back to the tab it was on.
 */
it('keeps focus in a replaced block’s second tab through a save', async () => {
  const container = document.createElement('div');
  document.body.append(container);
  const current = shallowRef(payload('draft-a'));
  const renderer = ref();
  app = createApp({
    render: () =>
      h(UiRenderer, {
        ref: renderer,
        payload: current.value,
      }),
  });
  const components = createCpComponentRegistry();
  registerUiComponents(components);
  components.install(app);
  app.mount(container);

  const input = (uid: string) =>
    container.querySelector<HTMLInputElement>(
      `input[name="fields[matrix][entries][${uid}][body]"]`
    );
  const strip = await vi.waitFor(() => {
    const element = container.querySelector<HTMLElement>('craft-tabs');
    expect(
      element?.shadowRoot?.querySelector('[part="tab-group"]')
    ).toBeTruthy();

    return element!;
  });
  [...strip.querySelectorAll<HTMLElement>('craft-tab')][1]!.click();
  const draftInput = await vi.waitFor(() => {
    const element = input('draft-a');
    expect(element?.checkVisibility()).toBe(true);

    return element!;
  });
  draftInput.focus();
  draftInput.setSelectionRange(2, 2);

  renderer.value.setSubmitting(true);
  current.value = payload('saved-a');
  await nextTick();
  renderer.value.advanceBaseline();
  renderer.value.setSubmitting(false);

  await vi.waitFor(() => {
    expect(document.activeElement).toBe(input('saved-a'));
  });
  expect(input('saved-a')!.selectionStart).toBe(2);
  expect(
    (container.querySelector('craft-tabs') as {selectedIndex?: number})
      .selectedIndex
  ).toBe(1);
});
