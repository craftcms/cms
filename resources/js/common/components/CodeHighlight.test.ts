import {createApp, h} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import CodeHighlight from './CodeHighlight.vue';

let teardown: (() => void) | undefined;

function mount(props: InstanceType<typeof CodeHighlight>['$props']) {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({render: () => h(CodeHighlight, props)});
  app.mount(container);
  teardown = () => {
    app.unmount();
    container.remove();
  };

  return container;
}

afterEach(() => {
  teardown?.();
  teardown = undefined;
});

it('colors JSON keys and values, rendering markup in them as text', () => {
  const container = mount({
    language: 'json',
    value: {description: '<img src=x onerror=alert(1)>', progress: 0},
  });

  expect(container.querySelector('img')).toBeNull();
  expect(container.textContent).toContain(
    '"description": "<img src=x onerror=alert(1)>"'
  );
  expect(container.querySelector('.th-property')?.textContent).toBe(
    '"description"'
  );
  expect(container.querySelector('.th-number')?.textContent).toBe('0');
});

it('colors YAML keys and values', () => {
  const container = mount({language: 'yaml', code: 'system:\n  live: true'});

  expect(
    [...container.querySelectorAll('.th-property')].map((el) => el.textContent)
  ).toEqual(['system', 'live']);
  expect(container.querySelector('.th-literal')?.textContent).toBe('true');
});
