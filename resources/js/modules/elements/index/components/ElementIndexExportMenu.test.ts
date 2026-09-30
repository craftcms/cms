import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

vi.mock('@craftcms/ui', () => ({
  t: (message: string) => message,
  ButtonVariant: {Fill: 'fill', Plain: 'plain'},
}));

const ElementIndexExportMenu = (await import('./ElementIndexExportMenu.vue'))
  .default;

let teardown: (() => void) | undefined;

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

function mount() {
  const container = document.createElement('div');
  document.body.append(container);
  const onExport = vi.fn();
  const app = createApp({
    render: () =>
      h(ElementIndexExportMenu, {
        exporters: [
          {type: 'Expanded', name: 'Expanded', formattable: true},
          {type: 'Raw', name: 'Raw', formattable: false},
        ],
        maxLimit: 40,
        onExport,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };

  return {container, onExport};
}

it('labels the limit and offers each supported exporter format', () => {
  const {container} = mount();
  const input = container.querySelector<HTMLInputElement>('input')!;
  const label = container.querySelector<HTMLLabelElement>('label')!;
  const choices = [...container.querySelectorAll('craft-button')]
    .map((button) => button.textContent?.replace(/\s+/g, ' ').trim())
    .filter((label) => label !== 'Export');

  expect(label.htmlFor).toBe(input.id);
  expect(input.max).toBe('40');
  expect(choices).toEqual([
    'Expanded (CSV)',
    'Expanded (XLSX)',
    'Expanded (JSON)',
    'Expanded (XML)',
    'Expanded (YAML)',
    'Raw',
  ]);
});

it('emits the chosen exporter, format, and limit', async () => {
  const {container, onExport} = mount();
  const input = container.querySelector<HTMLInputElement>('input')!;
  input.value = '25';
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await nextTick();

  [...container.querySelectorAll<HTMLElement>('craft-button')]
    .find((button) => button.textContent?.includes('JSON'))!
    .click();

  expect(onExport).toHaveBeenCalledWith('json', 'Expanded', 25);
});
