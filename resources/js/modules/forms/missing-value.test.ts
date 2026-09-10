import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import AddressControl from '@/modules/forms/AddressControl.vue';
import type {FormControlPayload} from '@/modules/forms/types';

/**
 * Every control whose value is a shape rather than a scalar has to survive the
 * beat before that value reaches the values tree — a throw during render is
 * what "Failed to render Form Control" is, and it takes the field with it.
 */
describe('controls with a missing value', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    try {
      app?.unmount();
    } catch {
      // Some controls boot legacy widgets that don't tear down under happy-dom.
    }
    container?.remove();
    app = undefined;
    container = undefined;
  });

  function mount(component: unknown, props: Record<string, unknown>): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () => h(component as never, props as never),
    });
    app.mount(container);
  }

  const payload = (component: string, controlProps: object) =>
    ({
      type: 'CraftCms\\Cms\\Form\\Controls\\Test',
      component,
      props: controlProps,
      path: ['fields', 'test'],
      mode: 'editable',
      deltaGroup: ['fields', 'test'],
      forms: [],
    }) as unknown as FormControlPayload;

  // TableControl isn't here: it boots the legacy editable table, which wants
  // jQuery on the page. Its guard is covered by the type — `value` is optional
  // and every read goes through `model`.

  it('renders an address with no fields filled in', async () => {
    expect(() =>
      mount(AddressControl, {
        control: payload('craft:address', {
          fields: [],
          countries: [],
          countryCode: 'US',
        }),
        value: undefined,
        editable: true,
      })
    ).not.toThrow();
    await nextTick();
  });
});
