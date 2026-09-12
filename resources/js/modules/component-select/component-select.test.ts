import {afterEach, expect, it, vi} from 'vite-plus/test';
import {
  ComponentSelect,
  type ComponentSelectSettings,
} from './component-select';

let select: ComponentSelect | undefined;

afterEach(() => {
  select?.destroy();
  select = undefined;
  vi.unstubAllGlobals();
});

function mount(inline = false): HTMLElement {
  const container = document.createElement('div');
  container.innerHTML = `
    <ul class="${inline ? 'inline-chips' : ''}">
      <li><craft-chip data-id="1"><div slot="suffix"></div></craft-chip></li>
      <li><craft-chip data-id="2"><div slot="suffix"></div></craft-chip></li>
    </ul>
  `;

  vi.stubGlobal('$', (element: HTMLElement) => ({0: element, on: vi.fn()}));
  vi.stubGlobal('Craft', {
    orientation: 'ltr',
    t: (_category: string, message: string) => message,
    hasMousePointerEvents: () => false,
    addActionsToChip: (
      chip: {0: HTMLElement},
      actions: Array<Record<string, any>>
    ) => {
      const suffix = chip[0].querySelector<HTMLElement>('[slot="suffix"]')!;

      for (const action of actions) {
        const button = document.createElement('button');
        button.textContent = action.label;
        for (const [name, value] of Object.entries(action.attributes ?? {})) {
          button.toggleAttribute(name, Boolean(value));
        }
        button.addEventListener(
          'click',
          action.onActivate ?? action.callback ?? (() => {})
        );
        suffix.append(button);
      }
    },
  });

  const settings: ComponentSelectSettings = {
    name: null,
    limit: null,
    sortable: true,
    selectable: false,
    showHandles: false,
    showDescription: false,
    showActionMenus: true,
    hyperlinks: false,
    createAction: null,
    disabled: false,
  };
  select = new ComponentSelect(container, settings);

  return container;
}

function visibleActions(chip: Element): string[] {
  return [...chip.querySelectorAll('button:not([hidden])')]
    .map((button) => button.textContent ?? '')
    .filter((label) => label !== '');
}

it('adds position-aware move actions to component menus', () => {
  const container = mount();
  const chips = container.querySelectorAll('craft-chip');

  expect(visibleActions(chips[0]!)).toEqual(['Move down', 'Remove']);
  expect(visibleActions(chips[1]!)).toEqual(['Move up', 'Remove']);

  chips[0]!.querySelector<HTMLElement>('[data-move-backward]')!.click();

  const reorderedChips = container.querySelectorAll('craft-chip');
  expect([...reorderedChips].map((chip) => chip.dataset.id)).toEqual([
    '2',
    '1',
  ]);
  expect(visibleActions(reorderedChips[0]!)).toEqual(['Move down', 'Remove']);
  expect(visibleActions(reorderedChips[1]!)).toEqual(['Move up', 'Remove']);
});

it('uses reading-order actions for inline component selects', () => {
  const container = mount(true);
  const chips = container.querySelectorAll('craft-chip');

  expect(visibleActions(chips[0]!)).toEqual(['Move backward', 'Remove']);
  expect(visibleActions(chips[1]!)).toEqual(['Move forward', 'Remove']);
});
