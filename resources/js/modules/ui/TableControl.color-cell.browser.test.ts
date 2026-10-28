import '../../../css/cp.css';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser/context';
import {createApp, h, nextTick, type App} from 'vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiNodePayload, UiPayload} from './types';

function colorField(path: string[], label: string): UiNodePayload {
  return {
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
    component: 'craft:field',
    props: {label, labelSrOnly: path.length > 1},
    control: {
      type: 'CraftCms\\Cms\\Ui\\Controls\\Color',
      component: 'craft:color',
      props: {presets: []},
      path,
      mode: 'editable',
      deltaGroup: [path[0]!],
    },
  };
}

const payload: UiPayload = {
  scope: [],
  refreshable: false,
  globalErrors: [],
  errors: [],
  values: {rows: [{color: '4f7cff'}], standalone: '4f7cff'},
  nodes: [
    {
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Rows'},
      control: {
        type: 'CraftCms\\Cms\\Ui\\Controls\\Table',
        component: 'craft:table',
        nestsUis: true,
        path: ['rows'],
        deltaGroup: ['rows'],
        mode: 'editable',
        props: {
          columns: {color: {heading: 'Color', type: 'color'}},
          rowTemplate: {
            scope: [],
            refreshable: false,
            nodes: [colorField(['color'], 'Color')],
          },
        },
        uis: [
          {
            scope: ['rows', '0'],
            refreshable: false,
            nodes: [colorField(['rows', '0', 'color'], 'Color')],
          },
        ],
      },
    },
    colorField(['standalone'], 'Standalone'),
  ],
};

let app: App | null = null;
let host: HTMLElement | null = null;

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(new Response('<svg></svg>'))
  );
});

afterEach(() => {
  app?.unmount();
  app = null;
  host?.remove();
  host = null;
  vi.unstubAllGlobals();
});

async function mount() {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp({render: () => h(UiRenderer, {payload})});
  const registry = createCpComponentRegistry();
  registerUiComponents(registry);
  registry.install(app);
  app.mount(host);
  await nextTick();

  for (const element of host.querySelectorAll('*')) {
    if ('updateComplete' in element) {
      await (element as {updateComplete: Promise<unknown>}).updateComplete;
    }
  }
  await nextTick();

  const cell = host.querySelector<HTMLTableCellElement>(
    'td[data-column="color"]'
  )!;
  const color = cell.querySelector('craft-input-color')!;
  const standalone = [...host.querySelectorAll('craft-input-color')].find(
    (element) => !element.closest('td')
  )!;

  return {
    cell,
    input: color.querySelector<HTMLInputElement>('input[slot="input"]')!,
    swatch: color.shadowRoot!.querySelector<HTMLElement>(
      '.input-color__swatch'
    )!,
    standaloneInput: standalone.querySelector<HTMLInputElement>(
      'input[slot="input"]'
    )!,
  };
}

/** The cursor shown at a point, looking inside shadow roots. */
function cursorAt(x: number, y: number): string {
  let element = document.elementFromPoint(x, y);
  while (element?.shadowRoot) {
    const inner = element.shadowRoot.elementFromPoint(x, y);
    if (!inner || inner === element) break;
    element = inner;
  }
  return element ? getComputedStyle(element).cursor : '';
}

it('gives a color cell’s field no border or padding of its own, leaving fields outside tables alone', async () => {
  const {input, standaloneInput} = await mount();

  expect(getComputedStyle(input).borderInlineStartWidth).toBe('0px');
  expect(getComputedStyle(standaloneInput).borderInlineStartWidth).not.toBe(
    '0px'
  );
  expect(parseFloat(getComputedStyle(input).paddingInlineStart)).toBeLessThan(
    parseFloat(getComputedStyle(standaloneInput).paddingInlineStart)
  );
});

it('shows a text cursor across a color cell, except for a pointer on its swatch', async () => {
  const {cell, input, swatch} = await mount();
  const cellBox = cell.getBoundingClientRect();
  const swatchBox = swatch.getBoundingClientRect();
  const y = swatchBox.top + swatchBox.height / 2;

  expect(cursorAt(cellBox.left + 2, y)).toBe('text');
  expect(
    cursorAt((swatchBox.right + input.getBoundingClientRect().left) / 2, y)
  ).toBe('text');
  expect(cursorAt(swatchBox.left + swatchBox.width / 2, y)).toBe('pointer');
});

it('focuses a color cell’s field when anywhere but its swatch is clicked', async () => {
  const {cell, input, swatch} = await mount();
  const cellBox = cell.getBoundingClientRect();
  const swatchBox = swatch.getBoundingClientRect();
  const y = swatchBox.top + swatchBox.height / 2 - cellBox.top;

  for (const x of [
    2,
    (swatchBox.right + input.getBoundingClientRect().left) / 2 - cellBox.left,
    cellBox.width - 2,
  ]) {
    input.blur();
    await userEvent.click(cell, {position: {x, y}});
    expect(document.activeElement, `click at x=${x}`).toBe(input);
  }
});

it('draws a color cell’s focus ring on the cell for its field, and on the swatch for the swatch', async () => {
  const {cell, input, swatch} = await mount();

  await userEvent.click(input);
  expect(getComputedStyle(cell).outlineStyle).toBe('solid');
  expect(getComputedStyle(input).outlineStyle).toBe('none');

  await userEvent.keyboard('{Shift>}{Tab}{/Shift}');
  expect(getComputedStyle(cell).outlineStyle).toBe('none');
  expect(getComputedStyle(swatch).outlineStyle).toBe('solid');
});
