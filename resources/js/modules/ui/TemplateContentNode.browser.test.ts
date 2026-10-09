import {afterEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick} from 'vue';
import type {App} from 'vue';
import TemplateContentNode from './TemplateContentNode.vue';

let app: App | undefined;
let container: HTMLDivElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
  history.replaceState(null, '', location.pathname);
});

it('allows keyboard navigation and actions in trusted template content', async () => {
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(TemplateContentNode, {
        node: {
          type: 'CraftCms\\Cms\\Ui\\Nodes\\TemplateContent',
          component: 'craft:template-content',
          uid: 'sidebar-content',
          props: {
            html: `<a href="#supporting-url">Supporting URL</a>
              <svg role="img" aria-label="SVG preview" width="32" height="32" viewBox="0 0 10 10"><path fill="currentColor" d="M0 0h10v10z" /></svg>
              <button type="button" onclick="this.textContent = 'Downloaded'">Download SVG</button>`,
            width: 100,
            inert: false,
          },
        },
      }),
  });
  app.mount(container);
  await nextTick();

  const link = page.getByRole('link', {name: 'Supporting URL'});
  await userEvent.tab();
  await expect.element(link).toHaveFocus();
  await userEvent.keyboard('{Enter}');
  expect(location.hash).toBe('#supporting-url');
  await expect
    .element(page.getByRole('img', {name: 'SVG preview'}))
    .toBeVisible();

  const button = page.getByRole('button', {name: 'Download SVG'});
  await userEvent.tab();
  await expect.element(button).toHaveFocus();
  await userEvent.keyboard(' ');
  await expect
    .element(page.getByRole('button', {name: 'Downloaded'}))
    .toBeVisible();
});
