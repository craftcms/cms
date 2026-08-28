import {createApp} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import DeleteButton from './DeleteButton.vue';

const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
  app.unmount();
  container.replaceChildren();
  vi.unstubAllGlobals();
});

it('emits clicks only after confirmation', () => {
  const confirm = vi.fn((): boolean => false);
  const onClick = vi.fn();
  vi.stubGlobal('confirm', confirm);
  app = createApp(DeleteButton, {
    confirm: 'Delete this item?',
    onClick,
  });
  app.mount(container);

  const button = container.querySelector('craft-button');
  if (!button) throw new Error('Expected a delete button.');

  button.click();
  expect(confirm).toHaveBeenCalledWith('Delete this item?');
  expect(onClick).not.toHaveBeenCalled();

  confirm.mockReturnValue(true);
  button.click();
  expect(onClick).toHaveBeenCalledOnce();
});
