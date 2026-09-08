import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import MatrixControl from './MatrixControl.vue';
import type {FormControlPayload} from './types';

describe('MatrixControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    try {
      app?.unmount();
    } catch {
      // `craft-matrix-input` relocates its own light DOM, which trips Vue's
      // unmount under happy-dom. Not what these tests are about.
    }
    container?.remove();
    app = undefined;
    container = undefined;
  });

  const control = (): FormControlPayload =>
    ({
      type: 'CraftCms\\Cms\\Form\\Controls\\Matrix',
      component: 'craft:matrix',
      props: {
        entryTypes: [{value: 'newType', label: 'New Type'}],
        addLabel: 'Add an entry',
        minEntries: null,
        maxEntries: null,
      },
      path: ['fields', 'pageBuilder'],
      mode: 'editable',
      deltaGroup: ['fields', 'pageBuilder'],
      forms: [],
    }) as unknown as FormControlPayload;

  function mount(value: unknown): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(MatrixControl, {
          control: control(),
          value,
          values: {},
          errors: [],
          touchedPaths: new Set<string>(),
          editable: true,
        } as never),
    });
    app.mount(container);
  }

  it('renders empty when its value is missing from the values tree', async () => {
    // A block minted in the browser is keyed `uid:<uuid>` in the values tree,
    // but the server strips that prefix and scopes the block's nested Form to
    // the bare UUID. Between those two renders a repeater nested inside the
    // block resolves its path to `undefined` — it has to render empty rather
    // than throw, or FormRenderer swaps the whole form for a render error.
    expect(() => mount(undefined)).not.toThrow();
    await nextTick();

    expect(container!.querySelectorAll('.matrixblock').length).toBe(0);
    expect(
      container!.querySelector('[data-form-matrix-add="newType"]')
    ).not.toBeNull();
  });

  it('renders its blocks when the value is present', async () => {
    mount({
      entries: {'uid:block-a': {type: 'newType'}},
      sortOrder: ['uid:block-a'],
    });
    await nextTick();

    expect(container!.querySelectorAll('.matrixblock').length).toBe(1);
  });
});
