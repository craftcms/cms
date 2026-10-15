import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vitest';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
import ContentFooter from './ContentFooter.vue';

let cleanup: (() => void) | null = null;

afterEach(() => {
  cleanup?.();
  cleanup = null;
});

async function flush(): Promise<void> {
  for (let i = 0; i < 4; i++) {
    await nextTick();
  }
}

it('keeps footer actions working after the slot is emptied and refilled', async () => {
  const filled = ref(true);
  const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
  const Screen = defineComponent({
    setup() {
      provideLayoutSlotRegistry('footer-test');

      return () => [
        h(ContentFooter, {readOnly: false, form: null, defaultFormActions: []}),
        filled.value
          ? h(LayoutSlot, {name: 'additional-buttons'}, () =>
              h('button', {class: 'footer-action'}, 'Action')
            )
          : null,
      ];
    },
  });
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApp(Screen);
  app.mount(root);
  cleanup = () => {
    app.unmount();
    root.remove();
    warn.mockRestore();
  };

  await flush();
  expect(
    root.querySelector('.content-footer__actions .footer-action')
  ).not.toBeNull();

  filled.value = false;
  await flush();
  expect(root.querySelector('.footer-action')).toBeNull();

  filled.value = true;
  await flush();
  expect(
    root.querySelector('.content-footer__actions .footer-action')
  ).not.toBeNull();
  expect(warn).not.toHaveBeenCalledWith(
    expect.stringContaining('Teleport'),
    expect.anything()
  );
});
