import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import type {
  LinkAdvancedField,
  LinkTypeConfig,
} from '../link-field/craft-link-field';
import LinkControl from './LinkControl.vue';
import type {UiControlPayload} from './types';

let app: App | undefined;
let container: HTMLElement | undefined;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

it('lets table columns keep their widths when a compact link has a long URL', async () => {
  const control: UiControlPayload<{
    types: LinkTypeConfig[];
    showLabelField: boolean;
    advancedFields: LinkAdvancedField[];
    compact: boolean;
    registeredTypes: string[] | null;
  }> = {
    type: 'CraftCms\\Cms\\Ui\\Controls\\Link',
    component: 'craft:link',
    props: {
      types: [{id: 'url', label: 'URL', kind: 'text'}],
      showLabelField: true,
      advancedFields: [],
      compact: true,
      registeredTypes: null,
    },
    path: ['fields', 'link'],
    mode: 'editable',
    deltaGroup: ['fields', 'link'],
  };
  container = document.createElement('div');
  container.style.width = '400px';
  document.body.append(container);
  app = createApp({
    render: () =>
      h('table', {style: {width: '100%'}}, [
        h('thead', [
          h('tr', [
            h('th', {style: {width: '50%'}}, 'Event'),
            h('th', {style: {width: '50%'}}, 'Link'),
          ]),
        ]),
        h('tbody', [
          h('tr', [
            h('td', 'Talk'),
            h('td', [
              h(LinkControl, {
                control,
                value: {
                  type: 'url',
                  value: `https://tickets.example.com/${'long-path/'.repeat(20)}`,
                  label: 'Book tickets',
                },
                editable: true,
                label: 'Link, row 1',
              }),
            ]),
          ]),
        ]),
      ]),
  });
  app.mount(container);
  await nextTick();

  const [event, link] = container.querySelectorAll('th');

  expect(
    container.querySelector('.link-control__detail')!.textContent
  ).toContain('long-path/long-path/');
  expect(link!.offsetWidth).toBe(event!.offsetWidth);
});
