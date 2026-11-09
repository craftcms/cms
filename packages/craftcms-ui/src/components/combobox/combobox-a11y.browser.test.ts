import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vite-plus/test';
import {cdp, page, userEvent} from 'vite-plus/test/browser';
import {contrast, loadTokens} from '../../../test/contrast';
import type CraftCombobox from './combobox.js';
import './combobox.js';
import '../field/field.js';
import '../../styles/cp.css';

const grouped = [
  {
    type: 'optgroup',
    label: 'Pome',
    options: [
      {label: 'Apple', value: 'apple'},
      {label: 'Pear', value: 'pear'},
    ],
  },
  {
    type: 'optgroup',
    label: 'Stone',
    options: [
      {label: 'Cherry', value: 'cherry'},
      {label: 'Damson', value: 'damson'},
    ],
  },
] as CraftCombobox['options'];

const fruit: CraftCombobox['options'] = [
  {label: 'Apple', value: 'apple', data: {icon: 'star'}},
  {label: 'Banana', value: 'banana', disabled: true},
  {label: 'Cherry', value: 'cherry'},
  {label: 'Damson', value: 'damson'},
];

beforeAll(loadTokens);

beforeEach(async () => {
  document.body.innerHTML = '';
  await page.viewport(1280, 720);
});

afterEach(async () => {
  await cdp().send('Emulation.setEmulatedMedia', {features: []});
});

async function settle() {
  await new Promise((resolve) => requestAnimationFrame(resolve));
  await new Promise((resolve) => setTimeout(resolve));
}

async function fixture(
  configure: (control: CraftCombobox) => void = () => {},
  theme = 'light'
) {
  const surface = document.createElement('div');
  surface.dataset.theme = theme;
  surface.style.backgroundColor = 'var(--c-surface-default)';
  surface.style.padding = '1rem';
  surface.innerHTML = `
    <button type="button">Before</button>
    <label id="fruit-label">Fruit</label>
    <button type="button">After</button>`;
  const control = document.createElement('craft-combobox') as CraftCombobox;
  control.setAttribute('aria-labelledby', 'fruit-label');
  control.options = fruit;
  control.showAllOnEmpty = true;
  configure(control);
  surface.querySelector('label')!.after(control);
  document.body.append(surface);
  await control.updateComplete;
  await settle();

  return {
    control,
    surface,
    input: control.querySelector('input')!,
    combobox: control.querySelector<HTMLElement>('[role=combobox]')!,
    listbox: control.querySelector<HTMLElement>('[role=listbox]')!,
    background: getComputedStyle(surface).backgroundColor,
  };
}

function popupOf(control: CraftCombobox): HTMLElement {
  return control.shadowRoot!.querySelector<HTMLElement>(
    '#overlay-content-node-wrapper'
  )!;
}

function option(control: CraftCombobox, label: string): HTMLElement {
  return [...control.querySelectorAll<HTMLElement>('craft-option')].find(
    (candidate) => candidate.textContent?.trim().startsWith(label)
  )!;
}

function activeOption(control: CraftCombobox): string | undefined {
  return control
    .querySelector<HTMLElement>('craft-option[active]')
    ?.textContent?.trim();
}

async function open(input: HTMLInputElement) {
  input.focus();
  await userEvent.keyboard('{ArrowDown}');
  await settle();
}

describe('semantics', () => {
  it('exposes an expandable combobox that autocompletes from the listbox it controls', async () => {
    const {control, input, combobox, listbox} = await fixture();

    await expect
      .element(page.getByRole('combobox', {name: 'Fruit', expanded: false}))
      .toBeInTheDocument();
    expect(combobox).toHaveAttribute('aria-haspopup', 'listbox');
    expect(combobox).toHaveAttribute('aria-controls', listbox.id);
    expect(input).toHaveAttribute('aria-autocomplete', 'list');

    await open(input);

    expect(control.open).toBe(true);
    await expect
      .element(page.getByRole('combobox', {name: 'Fruit', expanded: true}))
      .toBeInTheDocument();
  });

  it.each([
    ['an aria-labelledby on the host', false],
    ['a Form field label', true],
  ])('names its listbox after the label from %s', async (_, inField) => {
    const {control, input} = await fixture();
    if (inField) {
      control.removeAttribute('aria-labelledby');
      const field = document.createElement('craft-field');
      field.label = 'Fruit';
      control.slot = 'input';
      control.replaceWith(field);
      field.append(control);
      await field.updateComplete;
      await settle();
    }

    await open(input);

    await expect
      .element(page.getByRole('listbox', {name: 'Fruit'}))
      .toBeInTheDocument();
  });

  it.each([false, true])(
    'names each option group after its label and exposes its options in it (multiple choice: %s)',
    async (multipleChoice) => {
      const {input} = await fixture((c) => {
        c.multipleChoice = multipleChoice;
        c.options = grouped;
      });

      await open(input);

      for (const [group, labels] of [
        ['Pome', ['Apple', 'Pear']],
        ['Stone', ['Cherry', 'Damson']],
      ] as const) {
        for (const label of labels) {
          await expect
            .element(
              page
                .getByRole('group', {name: group})
                .getByRole('option', {name: label})
            )
            .toBeInTheDocument();
        }
      }
    }
  );

  it('drops a group that has no matching options', async () => {
    const {input} = await fixture((c) => (c.options = grouped));

    input.focus();
    await userEvent.keyboard('Che');
    await settle();

    await expect
      .element(page.getByRole('group', {name: 'Stone'}))
      .toBeInTheDocument();
    await expect
      .element(page.getByRole('group', {name: 'Pome'}))
      .not.toBeInTheDocument();
  });

  it('marks its listbox multiselectable in multiple-choice mode', async () => {
    const {listbox} = await fixture((c) => (c.multipleChoice = true));

    expect(listbox).toHaveAttribute('aria-multiselectable', 'true');
  });

  it('keeps the chevron and the selected option’s icon out of the accessibility tree', async () => {
    const {control} = await fixture((c) => (c.value = 'apple'));
    const icons = control.shadowRoot!.querySelectorAll('.indicator, .prefix');

    expect(icons).toHaveLength(2);
    await expect
      .element(page.elementLocator(control).getByRole('img'))
      .not.toBeInTheDocument();
    await expect
      .element(page.getByRole('combobox', {name: 'Fruit'}))
      .toBeInTheDocument();
  });
});

describe('keyboard and focus', () => {
  it('focuses the textbox when the host is focused or a wrapping field’s label is clicked', async () => {
    const field = document.createElement('craft-field');
    field.setAttribute('label', 'Fruit');
    const control = document.createElement('craft-combobox') as CraftCombobox;
    control.slot = 'input';
    control.options = fruit;
    field.append(control);
    document.body.append(field);
    await control.updateComplete;
    await settle();
    const input = control.querySelector('input')!;

    control.focus();
    expect(document.activeElement).toBe(input);

    input.blur();
    await page.getByText('Fruit', {exact: true}).click();
    expect(document.activeElement).toBe(input);
  });

  it('keeps focus in the textbox and points it at the active option', async () => {
    const {control, input} = await fixture();

    await open(input);
    await userEvent.keyboard('{ArrowDown}');

    expect(document.activeElement).toBe(input);
    const active = control.querySelector<HTMLElement>('craft-option[active]')!;
    expect(input).toHaveAttribute('aria-activedescendant', active.id);
    await expect.element(active).toHaveAccessibleName('Apple');
  });

  it('opens on Alt+Down without moving the active option', async () => {
    const {control, input} = await fixture();

    input.focus();
    await userEvent.keyboard('{Alt>}{ArrowDown}{/Alt}');
    await settle();

    expect(control.open).toBe(true);
    expect(activeOption(control)).toBeUndefined();
  });

  it('moves the active option with Down and Up, wrapping at both ends', async () => {
    const {control, input} = await fixture(
      (c) => (c.options = fruit.filter((item) => item.label !== 'Banana'))
    );

    await open(input);
    const visited = [];
    for (const key of ['Down', 'Down', 'Down', 'Down', 'Up']) {
      await userEvent.keyboard(`{Arrow${key}}`);
      visited.push(activeOption(control));
    }

    expect(visited).toEqual(['Apple', 'Cherry', 'Damson', 'Apple', 'Damson']);
  });

  it('stops on a disabled option, exposed as unavailable, without selecting it', async () => {
    const {control, input} = await fixture();

    await open(input);
    await userEvent.keyboard('{ArrowDown}{ArrowDown}');
    const banana = option(control, 'Banana');

    expect(activeOption(control)).toBe('Banana');
    await expect
      .element(page.getByRole('option', {name: 'Banana', disabled: true}))
      .toBeInTheDocument();
    expect(input).toHaveAttribute('aria-activedescendant', banana.id);

    await userEvent.keyboard('{Enter}');
    await settle();

    expect(control.value).toBe('');
  });

  it('moves the active option across groups and selects it with Enter', async () => {
    const {control, input} = await fixture((c) => (c.options = grouped));

    await open(input);
    await userEvent.keyboard('{ArrowDown}{ArrowDown}{ArrowDown}');

    expect(activeOption(control)).toBe('Cherry');

    await userEvent.keyboard('{Enter}');
    await settle();

    expect(control.value).toBe('cherry');
  });

  it('moves the caret, not the active option, with Home and End', async () => {
    const {control, input} = await fixture();

    await userEvent.click(input);
    await userEvent.keyboard('an');
    await settle();
    const active = activeOption(control);

    await userEvent.keyboard('{Home}');
    expect(input.selectionStart).toBe(0);
    expect(activeOption(control)).toBe(active);

    await userEvent.keyboard('{End}');
    expect(input.selectionStart).toBe(input.value.length);
    expect(activeOption(control)).toBe(active);
  });

  it('closes on Tab without selecting the active option, and moves focus on', async () => {
    const {control, input, surface} = await fixture();

    await open(input);
    await userEvent.keyboard('{ArrowDown}');
    await userEvent.tab();
    await settle();

    expect(control.open).toBe(false);
    expect(control.value).toBe('');
    expect(document.activeElement).toBe(surface.querySelectorAll('button')[1]);
  });

  it.each([false, true])(
    'returns focus to the textbox after an option is picked with a pointer (multiple choice: %s)',
    async (multipleChoice) => {
      const {control, input} = await fixture(
        (c) => (c.multipleChoice = multipleChoice)
      );

      await open(input);
      await userEvent.click(option(control, 'Cherry'));
      await settle();

      expect(control.value).toEqual(multipleChoice ? ['cherry'] : 'cherry');
      expect(document.activeElement).toBe(input);
    }
  );

  describe.each(['light', 'dark'])('in the %s theme', (theme) => {
    it.each([false, true])(
      'shows a 3:1 focus ring around the field when focused from the keyboard (multiple choice: %s)',
      async (multipleChoice) => {
        const {control, input, surface, background} = await fixture((c) => {
          c.multipleChoice = multipleChoice;
          if (multipleChoice) {
            c.value = ['apple', 'cherry'];
          }
        }, theme);

        surface.querySelectorAll('button')[1]!.focus();
        await userEvent.tab({shift: true});
        await settle();
        expect(document.activeElement).toBe(input);

        const ringed = multipleChoice
          ? control.shadowRoot!.querySelector<HTMLElement>(
              '.input-group__input'
            )!
          : input.closest<HTMLElement>('.form-control')!;
        const style = getComputedStyle(ringed);
        expect(style.outlineStyle).not.toBe('none');
        expect(parseFloat(style.outlineWidth)).toBeGreaterThanOrEqual(2);
        expect(contrast(style.outlineColor, background)).toBeGreaterThanOrEqual(
          3
        );

        const ring = ringed.getBoundingClientRect();
        for (const token of control.querySelectorAll('craft-combobox-token')) {
          const box = token.getBoundingClientRect();
          expect(box.left).toBeGreaterThanOrEqual(ring.left);
          expect(box.right).toBeLessThanOrEqual(ring.right);
        }
      }
    );

    it('shows a 3:1 focus ring on a token focused from the keyboard', async () => {
      const {control, surface, background} = await fixture((c) => {
        c.multipleChoice = true;
        c.value = ['apple', 'cherry'];
      }, theme);
      const token = control.querySelector('craft-combobox-token')!;

      surface.querySelector('button')!.focus();
      await userEvent.tab();
      await settle();
      const button = token.shadowRoot!.activeElement as HTMLElement | null;
      expect(button).toBe(token.shadowRoot!.querySelector('button'));

      const style = getComputedStyle(button!);
      const field = getComputedStyle(
        control.shadowRoot!.querySelector('.input-group__input')!
      ).backgroundColor;
      expect(style.outlineStyle).not.toBe('none');
      expect(parseFloat(style.outlineWidth)).toBeGreaterThanOrEqual(2);
      expect(
        contrast(style.outlineColor, field, background)
      ).toBeGreaterThanOrEqual(3);
    });
  });
});

describe('announcements', () => {
  function liveRegion(control: CraftCombobox): HTMLElement {
    return control.shadowRoot!.querySelector<HTMLElement>('[aria-live]')!;
  }

  it('renders an empty polite live region before anything is announced', async () => {
    const {control} = await fixture();
    const region = liveRegion(control);

    expect(region).toHaveAttribute('aria-live', 'polite');
    expect(region).toHaveAttribute('aria-atomic', 'true');
    expect(region.textContent?.trim()).toBe('');
  });

  it.each([false, true])(
    'announces how many options there are when the list opens (multiple choice: %s)',
    async (multipleChoice) => {
      const {control, input} = await fixture((c) => {
        c.multipleChoice = multipleChoice;
        if (multipleChoice) {
          c.value = ['apple'];
        }
      });

      await open(input);

      await vi.waitFor(() =>
        expect(liveRegion(control).textContent?.trim()).toBe(
          multipleChoice ? '3 results' : '4 results'
        )
      );
    }
  );

  it('announces the new count as typing filters the options', async () => {
    const {control, input} = await fixture();

    input.focus();
    await userEvent.keyboard('Che');

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('1 result')
    );
  });

  it('announces when no options match', async () => {
    const {control, input} = await fixture();

    input.focus();
    await userEvent.keyboard('zzz');

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('No results')
    );
  });

  it('announces every match, not just those shown, when the list is cut short', async () => {
    const {control, input} = await fixture((c) => (c.limit = 2));

    await open(input);

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('4 results')
    );
  });

  it.each([
    ['its remove button', 'Removed Cherry'],
    ['Backspace', 'Removed Cherry'],
    ['the clear button', 'Removed Apple and Cherry'],
  ])('announces which values were removed with %s', async (how, message) => {
    document.documentElement.lang = 'en';
    const {control, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.clearable = true;
      c.value = ['apple', 'cherry'];
    });

    if (how === 'Backspace') {
      await userEvent.click(input);
      await userEvent.keyboard('{Backspace}');
    } else {
      await userEvent.click(
        page.getByRole('button', {
          name: how === 'the clear button' ? 'Clear' : 'Cherry',
          exact: true,
        })
      );
    }

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe(message)
    );
    await settle();
    expect(liveRegion(control).textContent?.trim()).toBe(message);
  });

  it('announces the count again once typing follows a removal', async () => {
    const {control, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple', 'cherry'];
    });

    await userEvent.click(input);
    await userEvent.keyboard('{Backspace}');
    await settle();
    await userEvent.keyboard('Da');

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('1 result')
    );
  });

  it('announces the count once typing follows a removal, even when typing leaves it unchanged', async () => {
    const {control, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['cherry', 'apple'];
    });

    await userEvent.click(input);
    await userEvent.keyboard('{Backspace}');
    await settle();
    expect(liveRegion(control).textContent?.trim()).toBe('Removed Apple');
    await userEvent.keyboard('a');

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('3 results')
    );
  });

  it('announces the count when the list opens after the control is moved mid-removal', async () => {
    const {control, surface, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['cherry', 'apple'];
    });

    await userEvent.click(input);
    await userEvent.keyboard('{Backspace}');
    await settle();
    control.remove();
    surface.append(control);
    await control.updateComplete;
    control.open = false;
    await settle();
    await open(input);

    await vi.waitFor(() =>
      expect(liveRegion(control).textContent?.trim()).toBe('3 results')
    );
  });

  it('clears the live region after the announcement timeout elapses', async () => {
    const {control} = await fixture();
    vi.useFakeTimers({toFake: ['setTimeout', 'clearTimeout']});

    try {
      control.open = true;
      await vi.advanceTimersByTimeAsync(0);
      expect(liveRegion(control).textContent?.trim()).toBe('4 results');

      await vi.advanceTimersByTimeAsync(4999);
      expect(liveRegion(control).textContent?.trim()).toBe('4 results');

      await vi.advanceTimersByTimeAsync(1);
      expect(liveRegion(control).textContent?.trim()).toBe('');
    } finally {
      vi.useRealTimers();
    }
  });
});

describe('multiple choice', () => {
  it('lists the selected values, in the order they were chosen', async () => {
    await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['cherry', 'apple'];
    });
    const items = page.getByRole('list').getByRole('listitem');

    await expect.element(items.nth(0)).toHaveTextContent('Cherry');
    await expect.element(items.nth(1)).toHaveTextContent('Apple');
    expect(items.elements()).toHaveLength(2);
  });

  it('names the combobox by the label and then the selected values, leaving out “Remove”', async () => {
    const {input, combobox} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple', 'cherry'];
    });

    await expect.element(input).toHaveAccessibleName('Fruit Apple Cherry');
    await expect.element(combobox).toHaveAccessibleName('Fruit Apple Cherry');
    await expect
      .element(page.getByRole('button', {name: 'Cherry', exact: true}))
      .toHaveAccessibleDescription('Remove');
  });

  it('names the textbox by a wrapping field’s label and then its selected values', async () => {
    const {control, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple'];
    });
    control.removeAttribute('aria-labelledby');
    const field = document.createElement('craft-field');
    field.label = 'Status';
    control.slot = 'input';
    field.append(control);
    document.body.append(field);
    await field.updateComplete;
    await settle();

    await expect.element(input).toHaveAccessibleName('Status Apple');
  });

  it('renames the textbox as values are added and removed', async () => {
    const {input} = await fixture((c) => {
      c.multipleChoice = true;
    });

    await expect.element(input).toHaveAccessibleName('Fruit');
    await userEvent.click(input);
    await userEvent.keyboard('{ArrowDown}{Enter}');
    await expect.element(input).toHaveAccessibleName('Fruit Apple');
    await userEvent.keyboard('{Backspace}');
    await expect.element(input).toHaveAccessibleName('Fruit');
  });

  it('announces each value as it’s added, through a polite list that’s there before anything is chosen', async () => {
    const {control} = await fixture((c) => {
      c.multipleChoice = true;
    });
    const list = control.querySelector('ul')!;

    expect(list).toHaveAttribute('aria-live', 'polite');
    expect(list).toHaveAttribute('aria-atomic', 'false');
    expect(list).toBeEmptyDOMElement();

    control.value = ['cherry'];
    await control.updateComplete;

    expect(control.querySelector('ul')).toBe(list);
    expect(list).toHaveTextContent('Cherry');
  });

  it.each(['its button', 'Backspace'])(
    'focuses the textbox after a token is removed with %s',
    async (how) => {
      const {control, input} = await fixture((c) => {
        c.multipleChoice = true;
        c.value = ['apple', 'cherry'];
      });

      if (how === 'Backspace') {
        await userEvent.click(input);
        await userEvent.keyboard('{Backspace}');
      } else {
        await userEvent.click(
          page.getByRole('button', {name: 'Cherry', exact: true})
        );
      }
      await settle();

      expect(control.value).toEqual(['apple']);
      expect(document.activeElement).toBe(input);
    }
  );
});

describe('pointer', () => {
  it('makes the clear button at least 24 by 24 pixels', async () => {
    const {control} = await fixture((c) => {
      c.clearable = true;
      c.value = 'cherry';
    });
    const box = control
      .shadowRoot!.querySelector('.clear')!
      .getBoundingClientRect();

    expect(box.width).toBeGreaterThanOrEqual(24);
    expect(box.height).toBeGreaterThanOrEqual(24);
  });

  it('makes each token’s remove button at least 24 by 24 pixels, or leaves room for one', async () => {
    const {control} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple', 'cherry'];
    });
    const buttons = [...control.querySelectorAll('craft-combobox-token')].map(
      (token) => token.shadowRoot!.querySelector('button')!
    );
    const boxes = buttons.map((button) => button.getBoundingClientRect());

    for (const [index, box] of boxes.entries()) {
      const centerX = box.left + box.width / 2;
      const centerY = box.top + box.height / 2;
      const roomy =
        (box.width >= 24 && box.height >= 24) ||
        boxes.every(
          (other, otherIndex) =>
            otherIndex === index ||
            Math.hypot(
              other.left + other.width / 2 - centerX,
              other.top + other.height / 2 - centerY
            ) >= 24
        );
      expect(roomy, `remove button ${index}`).toBe(true);
    }
  });
});

describe('visual', () => {
  describe.each(['light', 'dark'])('in the %s theme', (theme) => {
    it.each([false, true])(
      'draws the textbox and popup borders at 3:1, and the footer text at 4.5:1 inside the popup (multiple choice: %s)',
      async (multipleChoice) => {
        const {control, input, background} = await fixture((c) => {
          c.multipleChoice = multipleChoice;
          c.limit = 2;
        }, theme);
        const border = multipleChoice
          ? control.shadowRoot!.querySelector('.input-group__input')!
          : input.closest('.form-control')!;
        await open(input);
        const popup = popupOf(control);
        const footer =
          control.shadowRoot!.querySelector<HTMLElement>('.combobox__footer')!;

        expect(
          contrast(getComputedStyle(border).borderTopColor, background)
        ).toBeGreaterThanOrEqual(3);
        expect(
          contrast(getComputedStyle(popup).borderTopColor, background)
        ).toBeGreaterThanOrEqual(3);
        expect(
          contrast(
            getComputedStyle(footer).color,
            getComputedStyle(popup).backgroundColor
          )
        ).toBeGreaterThanOrEqual(4.5);
        const box = popup.getBoundingClientRect();
        const text = footer.getBoundingClientRect();
        expect(text.top).toBeGreaterThanOrEqual(box.top);
        expect(text.bottom).toBeLessThanOrEqual(box.bottom);
      }
    );
  });

  it.each([false, true])(
    'draws the error border around the field while invalid (multiple choice: %s)',
    async (multipleChoice) => {
      const {control, input, surface} = await fixture((c) => {
        c.multipleChoice = multipleChoice;
        c.invalid = true;
      });
      const border = multipleChoice
        ? control.shadowRoot!.querySelector('.input-group__input')!
        : input.closest('.form-control')!;
      const danger = document.createElement('div');
      danger.style.borderColor = 'var(--c-color-danger-border-loud)';
      surface.append(danger);

      expect(getComputedStyle(border).borderTopColor).toBe(
        getComputedStyle(danger).borderTopColor
      );

      control.invalid = false;
      await control.updateComplete;
      expect(getComputedStyle(border).borderTopColor).not.toBe(
        getComputedStyle(danger).borderTopColor
      );
    }
  );

  it('keeps the textbox border and popup border in forced colors mode', async () => {
    await cdp().send('Emulation.setEmulatedMedia', {
      features: [{name: 'forced-colors', value: 'active'}],
    });
    const {control, input} = await fixture((c) => (c.multipleChoice = true));
    await open(input);

    for (const element of [
      control.shadowRoot!.querySelector('.input-group__input')!,
      popupOf(control),
    ]) {
      const style = getComputedStyle(element);
      expect(style.borderTopStyle).not.toBe('none');
      expect(parseFloat(style.borderTopWidth)).toBeGreaterThan(0);
      expect(style.borderTopColor).not.toBe('rgba(0, 0, 0, 0)');
    }
  });

  it('draws one focus ring, around the field, when the multiple-choice textbox is focused', async () => {
    const {control, input, surface} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple'];
    });

    surface.querySelectorAll('button')[1]!.focus();
    await userEvent.tab({shift: true});
    await settle();
    expect(document.activeElement).toBe(input);

    const ringed = [
      input,
      ...control.querySelectorAll('[slot=input]'),
      ...control.shadowRoot!.querySelectorAll('*'),
    ].filter((element) => getComputedStyle(element).outlineStyle !== 'none');
    expect(ringed).toEqual([
      control.shadowRoot!.querySelector('.input-group__input'),
    ]);
  });

  it('rings only the token, not the field, when a token is focused', async () => {
    const {control, surface} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple'];
    });

    surface.querySelector('button')!.focus();
    await userEvent.tab();
    await settle();
    expect(document.activeElement).toBe(
      control.querySelector('craft-combobox-token')
    );

    expect(
      getComputedStyle(
        control.shadowRoot!.querySelector('.input-group__input')!
      ).outlineStyle
    ).toBe('none');
  });

  it('keeps the focus ring on a token in forced colors mode', async () => {
    await cdp().send('Emulation.setEmulatedMedia', {
      features: [{name: 'forced-colors', value: 'active'}],
    });
    const {control, surface, background} = await fixture((c) => {
      c.multipleChoice = true;
      c.value = ['apple'];
    });
    const token = control.querySelector('craft-combobox-token')!;

    surface.querySelector('button')!.focus();
    await userEvent.tab();
    await settle();
    const button = token.shadowRoot!.querySelector('button')!;
    expect(token.shadowRoot!.activeElement).toBe(button);

    const style = getComputedStyle(button);
    expect(style.outlineStyle).not.toBe('none');
    expect(parseFloat(style.outlineWidth)).toBeGreaterThanOrEqual(1);
    expect(
      contrast(
        style.outlineColor,
        getComputedStyle(
          control.shadowRoot!.querySelector('.input-group__input')!
        ).backgroundColor,
        background
      )
    ).toBeGreaterThanOrEqual(3);
  });

  it.each([
    ['below', 'flex-start'],
    ['above', 'flex-end'],
  ])(
    'opens the popup %s the field without covering the textbox',
    async (_, alignment) => {
      const {control, input, surface} = await fixture();
      document.body.style.margin = '0';
      surface.style.display = 'flex';
      surface.style.flexDirection = 'column';
      surface.style.justifyContent = alignment;
      surface.style.height = '100vh';
      surface.style.boxSizing = 'border-box';

      await open(input);
      await vi.waitFor(() =>
        expect(popupOf(control).getBoundingClientRect().height).toBeGreaterThan(
          0
        )
      );
      const field = input.closest<HTMLElement>('.form-control')!;
      const textbox = field.getBoundingClientRect();
      const popup = popupOf(control).getBoundingClientRect();
      const points = [
        [textbox.left + textbox.width / 2, textbox.top + textbox.height / 2],
        [textbox.left + 2, textbox.top + 2],
        [textbox.right - 2, textbox.top + 2],
        [textbox.left + 2, textbox.bottom - 2],
        [textbox.right - 2, textbox.bottom - 2],
      ];

      if (alignment === 'flex-start') {
        expect(popup.top).toBeGreaterThanOrEqual(textbox.bottom - 1);
      } else {
        expect(popup.bottom).toBeLessThanOrEqual(textbox.top + 1);
      }
      for (const [x, y] of points) {
        const hit = control.shadowRoot!.elementFromPoint(x!, y!);
        expect(
          !!hit && (field.contains(hit) || !!hit.closest('.combobox__textbox'))
        ).toBe(true);
      }
    }
  );

  it('fits a 320 pixel viewport, wrapping long tokens and labels, with the popup open', async () => {
    await page.viewport(320, 640);
    const long =
      'Supercalifragilistic option label that is far too long for a phone';
    const {control, input} = await fixture((c) => {
      c.multipleChoice = true;
      c.options = [...fruit, {label: long, value: 'long'}];
      c.value = ['long', 'apple'];
    });
    await open(input);

    expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(320);
    for (const token of control.querySelectorAll('craft-combobox-token')) {
      expect(token.getBoundingClientRect().right).toBeLessThanOrEqual(320);
    }
    expect(popupOf(control).getBoundingClientRect().right).toBeLessThanOrEqual(
      320
    );
  });
});
