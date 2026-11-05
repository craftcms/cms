import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  ComponentSelect,
  type ComponentSelectSettings,
} from './component-select';

const chip = (id: string, label: string): string =>
  `<craft-chip data-id="${id}" data-type="EntryType"><input type="hidden" name="entryTypes[]" value="${id}">${label}</craft-chip>`;

const settings: ComponentSelectSettings = {
  name: 'entryTypes',
  limit: null,
  sortable: false,
  selectable: false,
  showHandles: false,
  showDescription: false,
  showActionMenus: true,
  hyperlinks: false,
  createAction: null,
  checkboxOptions: false,
  disabled: false,
};

describe('ComponentSelect', () => {
  let container: HTMLElement;

  beforeEach(() => {
    // Just enough jQuery for the chip wiring, which only binds listeners.
    (window as any).$ = () => {
      const wrapped = {on: () => wrapped, off: () => wrapped};

      return wrapped;
    };
    (window as any).Craft = {
      t: (_category: string, message: string) => message,
      addActionsToChip: vi.fn(),
      hasMousePointerEvents: () => false,
      appendHeadHtml: vi.fn(),
      appendBodyHtml: vi.fn(),
      sendActionRequest: vi.fn(async () => ({
        data: {
          components: {EntryType: {3: [chip('3', 'Gallery')]}},
          headHtml: '',
          bodyHtml: '',
        },
      })),
    };

    container = document.createElement('div');
    container.innerHTML = `
      <ul>
        <li>${chip('1', 'Article')}</li>
        <li>${chip('2', 'Video')}</li>
      </ul>
      <craft-action-menu>
        <craft-button slot="invoker" command="--choose-item"></craft-button>
        <craft-action-item data-id="3" data-type="EntryType">Gallery</craft-action-item>
      </craft-action-menu>
    `;
    document.body.append(container);
  });

  afterEach(() => {
    container.remove();
  });

  function selectedIds(): string[] {
    return [...container.querySelectorAll<HTMLElement>('ul > li > craft-chip')]
      .filter(
        (element) => !element.closest('li')?.hasAttribute('data-removing')
      )
      .map((element) => element.dataset.id ?? '');
  }

  it('puts the chosen component in the place of the chip it replaces', async () => {
    new ComponentSelect(container, settings);
    const first = container.querySelector<HTMLElement>('craft-chip')!;

    first.dispatchEvent(
      new CustomEvent('replace-component', {
        bubbles: true,
        cancelable: true,
        detail: {chip: first},
      })
    );
    container
      .querySelector('craft-action-item')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await vi.waitFor(() => expect(selectedIds()).toEqual(['3', '2']));
  });

  it('adds a chosen component after the others when nothing is being replaced', async () => {
    new ComponentSelect(container, settings);

    container
      .querySelector('craft-action-item')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await vi.waitFor(() => expect(selectedIds()).toEqual(['1', '2', '3']));
  });
});
