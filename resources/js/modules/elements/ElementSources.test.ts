import {createApp, h, nextTick, ref} from 'vue';
import '@craftcms/ui/components/nav-item/nav-item';
import '@craftcms/ui/components/nav-list/nav-list';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import ElementSources from './ElementSources.vue';

let teardown: (() => void) | undefined;
afterEach(() => {
  teardown?.();
  document.body.innerHTML = '';
});

it('emits the chosen source and renders headings with their choices', async () => {
  const activeSource = ref('news');
  const select = vi.fn((key: string) => {
    activeSource.value = key;
  });
  const host = document.createElement('div');
  document.body.append(host);
  const app = createApp({
    render: () =>
      h(ElementSources, {
        sources: [
          {
            type: 'heading',
            heading: 'Sections',
            children: [
              {type: 'native', key: 'news', label: 'News'},
              {type: 'native', key: 'pages', label: 'Pages'},
            ],
          },
        ],
        activeSource: activeSource.value,
        onSelect: select,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(host);
  teardown = () => app.unmount();
  const items = Array.from(host.querySelectorAll('craft-nav-item'));
  await Promise.all(items.map((item) => item.updateComplete));
  const buttons = items.flatMap((item) =>
    Array.from(item.shadowRoot!.querySelectorAll('button'))
  );
  expect(host.textContent).toContain('Sections');
  expect(buttons).toHaveLength(2);
  expect(items.every((item) => item.getAttribute('role') === 'listitem')).toBe(
    true
  );
  expect(
    items.every(
      (item) =>
        item.shadowRoot?.querySelector('li')?.getAttribute('role') ===
        'presentation'
    )
  ).toBe(true);
  expect(buttons[0]?.getAttribute('aria-current')).toBe('true');
  buttons[0]!.click();
  expect(select).not.toHaveBeenCalled();
  buttons[1]!.focus();
  buttons[1]!.click();
  await nextTick();
  await Promise.all(items.map((item) => item.updateComplete));
  expect(select).toHaveBeenCalledExactlyOnceWith('pages');
  expect(buttons[1]?.getAttribute('aria-current')).toBe('true');
  expect(buttons[0]?.getAttribute('aria-current')).toBe('false');
  expect(
    host.querySelector('craft-nav-list')?.shadowRoot?.querySelector('ul')
  ).toBeTruthy();
  expect(document.activeElement?.shadowRoot?.activeElement).toBe(buttons[1]);
});
