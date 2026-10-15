import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {ButtonVariant} from '@craftcms/ui';
import TypePicker from './TypePicker.vue';

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

afterEach(() => {
  app.unmount();
  container.remove();
});

it('updates the invoker variant when the button variant changes', async () => {
  const variant = ref<ButtonVariant>(ButtonVariant.Solid);
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(TypePicker, {types: [], label: 'Add', buttonVariant: variant.value}),
  });
  app.mount(container);

  const invoker = container.querySelector<HTMLElement & {variant: string}>(
    'craft-button'
  )!;
  expect(invoker.variant).toBe(ButtonVariant.Solid);

  variant.value = ButtonVariant.Primary;
  await nextTick();

  expect(invoker.variant).toBe(ButtonVariant.Primary);
});
