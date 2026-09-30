import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';

const registrationCleanups: Array<ReturnType<typeof vi.fn>> = [];
const registerItem = vi.fn(() => {
  const cleanup = vi.fn();
  registrationCleanups.push(cleanup);

  return cleanup;
});
const monitorCleanup = vi.fn();

vi.mock('@/common/composables/useDragAndDrop.js', () => ({
  useDragAndDrop: () => ({
    registerItem,
    getDragState: vi.fn(),
    getDropState: vi.fn(),
    setupMonitor: () => monitorCleanup,
  }),
}));

const {useReorderableRows} = await import('./useReorderableRows');

describe('useReorderableRows', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
    registerItem.mockClear();
    registrationCleanups.length = 0;
    monitorCleanup.mockClear();
  });

  it('refreshes registrations when handles and enabled state change', async () => {
    const busy = ref(false);

    const component = {
      setup() {
        const reorder = useReorderableRows({
          getRowIds: () => ['entry'],
          onReorder: vi.fn(),
          enabled: () => !busy.value,
        });

        return () =>
          h('table', [
            h(
              'tr',
              {
                ref: (element) =>
                  reorder.setRowRef(
                    element as HTMLTableRowElement | null,
                    'entry'
                  ),
              },
              busy.value
                ? []
                : [
                    h('button', {
                      ref: (element) =>
                        reorder.setHandleRef(
                          element as HTMLElement | null,
                          'entry'
                        ),
                    }),
                  ]
            ),
          ]);
      },
    };

    container = document.createElement('div');
    document.body.append(container);
    app = createApp(component);
    app.mount(container);
    await nextTick();
    await nextTick();

    const firstHandle = container.querySelector('button');
    expect(registerItem).toHaveBeenLastCalledWith(
      expect.any(HTMLTableRowElement),
      firstHandle,
      'entry',
      0
    );
    expect(registerItem).toHaveBeenCalledTimes(1);

    busy.value = true;
    await nextTick();
    await nextTick();
    expect(registrationCleanups[0]).toHaveBeenCalledOnce();
    expect(registerItem).toHaveBeenCalledTimes(1);

    busy.value = false;
    await nextTick();
    await nextTick();

    const replacementHandle = container.querySelector('button');
    expect(replacementHandle).not.toBe(firstHandle);
    expect(registerItem).toHaveBeenLastCalledWith(
      expect.any(HTMLTableRowElement),
      replacementHandle,
      'entry',
      0
    );
    expect(registerItem).toHaveBeenCalledTimes(2);

    app.unmount();
    app = undefined;
    expect(registrationCleanups[1]).toHaveBeenCalledOnce();
    expect(monitorCleanup).toHaveBeenCalledOnce();
  });
});
