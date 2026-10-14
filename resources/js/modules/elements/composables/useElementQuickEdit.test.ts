import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {
  CHIP_DOUBLE_CLICK_DELAY,
  useElementQuickEdit,
} from './useElementQuickEdit';

const openSlideout = vi.fn();
const refreshResults = vi.fn();

const {onDblClick, deferChipClick, openEditor} = useElementQuickEdit({
  openSlideout,
  refreshResults,
});

beforeEach(() => {
  openSlideout.mockReset();
  refreshResults.mockReset();
});
afterEach(() => {
  document.body.innerHTML = '';
});

const CP_URL = '/admin/entries/news/5-hello';
const ASSET_CP_URL = '/admin/assets/edit/7-glasses';

/**
 * A table row, mirroring what `ContentIndexViewModel::tableRows()` emits: the
 * element's metadata on the title chip, wrapped in a link to its edit page.
 */
function renderRow(attributes: Record<string, string> = {}) {
  const data = {'data-editable': '', 'data-cp-url': CP_URL, ...attributes};

  const table = document.createElement('table');
  table.innerHTML = `
    <tbody>
      <tr tabindex="0">
        <td class="cp-table-cell--select">
          <craft-checkbox><label slot="label">Select row</label></craft-checkbox>
        </td>
        <td class="cp-table-cell--title">
          <a href="https://cp.test${CP_URL}">
            <craft-chip class="element" ${Object.entries(data)
              .map(([key, value]) => `${key}="${value}"`)
              .join(' ')}>
              <craft-element-label><span class="label-link">Hello</span></craft-element-label>
            </craft-chip>
          </a>
        </td>
        <td class="cp-table-cell--image">
          <craft-chip class="element" data-editable data-cp-url="${ASSET_CP_URL}">
            <span class="label-link">glasses</span>
            <div slot="suffix"><button type="button">Remove</button></div>
          </craft-chip>
        </td>
        <td class="cp-table-cell--postDate">Today</td>
        <td class="cp-table-cell--actions"><craft-action-menu></craft-action-menu></td>
      </tr>
    </tbody>
  `;
  document.body.appendChild(table);

  return {
    row: table.querySelector('tr')!,
    chip: table.querySelector('.element')!,
    link: table.querySelector('a')!,
    assetChip: table.querySelector<HTMLElement>(
      `[data-cp-url="${ASSET_CP_URL}"]`
    )!,
    checkbox: table.querySelector('craft-checkbox')!,
    actionMenu: table.querySelector('craft-action-menu')!,
    postDate: table.querySelector('.cp-table-cell--postDate')!,
  };
}

/**
 * A card, which puts the `element` class on the `<li>` and the metadata on the
 * `<craft-card>` inside it.
 */
function renderCard() {
  const list = document.createElement('ul');
  list.innerHTML = `
    <li class="element" data-id="5" tabindex="0">
      <craft-card data-editable data-cp-url="${CP_URL}">
        <div slot="header"><craft-checkbox></craft-checkbox></div>
        <div class="card-body">Hello</div>
      </craft-card>
    </li>
  `;
  document.body.appendChild(list);

  return {
    card: list.querySelector('li')!,
    body: list.querySelector('.card-body')!,
    checkbox: list.querySelector('craft-checkbox')!,
  };
}

/**
 * Dispatches for real rather than calling the handler directly, so the
 * handler sees the event's composed path.
 */
function dispatch(
  target: Element,
  type: string,
  handler: (event: MouseEvent) => void,
  init: MouseEventInit = {}
): MouseEvent {
  const event = new MouseEvent(type, {
    bubbles: true,
    cancelable: true,
    composed: true,
    ...init,
  });
  const listener = (e: Event) => handler(e as MouseEvent);
  document.addEventListener(type, listener, {capture: true});

  try {
    target.dispatchEvent(event);
  } finally {
    document.removeEventListener(type, listener, {capture: true});
  }

  return event;
}

function dblclick(target: Element): MouseEvent {
  return dispatch(target, 'dblclick', onDblClick);
}

function click(target: Element, init: MouseEventInit = {}): MouseEvent {
  return dispatch(target, 'click', deferChipClick, {detail: 1, ...init});
}

describe('useElementQuickEdit', () => {
  it('preserves the opener when a client-side action opens the editor', () => {
    const opener = document.createElement('button');
    openEditor(CP_URL, opener);

    expect(openSlideout).toHaveBeenCalledWith(
      CP_URL,
      expect.objectContaining({opener})
    );
  });

  it('opens the element when a row is double-clicked', () => {
    const {postDate} = renderRow();

    const event = dblclick(postDate);

    expect(openSlideout).toHaveBeenCalledWith(CP_URL, expect.anything());
    expect(event.defaultPrevented).toBe(true);
  });

  it('opens from a double-click on the row itself', () => {
    const {row} = renderRow();

    dblclick(row);

    expect(openSlideout).toHaveBeenCalledWith(CP_URL, expect.anything());
  });

  it('opens the element when a card is double-clicked', () => {
    const {body} = renderCard();

    dblclick(body);

    // Cards keep the metadata on the inner `<craft-card>`, not the `.element`
    // wrapper, so this only works if both shapes are handled.
    expect(openSlideout).toHaveBeenCalledWith(CP_URL, expect.anything());
  });

  describe('leaves interactive controls alone', () => {
    it.each([
      ['the title link', (r: ReturnType<typeof renderRow>) => r.link],
      ['the select checkbox', (r: ReturnType<typeof renderRow>) => r.checkbox],
      ['the action menu', (r: ReturnType<typeof renderRow>) => r.actionMenu],
    ])('ignores a double-click on %s', (_label, pick) => {
      const row = renderRow();

      const event = dblclick(pick(row));

      expect(openSlideout).not.toHaveBeenCalled();
      // The control's own behavior has to survive untouched.
      expect(event.defaultPrevented).toBe(false);
    });

    it('ignores a double-click on a checkbox inside a card', () => {
      const {checkbox} = renderCard();

      dblclick(checkbox);

      expect(openSlideout).not.toHaveBeenCalled();
    });

    it('ignores a button inside a chip', () => {
      const {assetChip} = renderRow();

      const event = dblclick(assetChip.querySelector('button')!);

      expect(openSlideout).not.toHaveBeenCalled();
      expect(event.defaultPrevented).toBe(false);
    });
  });

  describe('chips', () => {
    it('opens the title chip’s element, even though a link wraps it', () => {
      const {link} = renderRow();

      const event = dblclick(link.querySelector('.label-link')!);

      expect(openSlideout).toHaveBeenCalledWith(CP_URL, expect.anything());
      expect(event.defaultPrevented).toBe(true);
    });

    it('opens a related chip’s own element rather than the row’s', () => {
      const {row, assetChip} = renderRow();

      dblclick(assetChip.querySelector('.label-link')!);

      expect(openSlideout).toHaveBeenCalledOnce();
      expect(openSlideout).toHaveBeenCalledWith(
        ASSET_CP_URL,
        expect.objectContaining({opener: row})
      );
    });

    it('leaves a chip that can’t be edited to the row', () => {
      const {assetChip} = renderRow();
      assetChip.removeAttribute('data-editable');

      dblclick(assetChip);

      expect(openSlideout).toHaveBeenCalledWith(CP_URL, expect.anything());
    });
  });

  it('ignores a double-click on a non-editable element', () => {
    const {chip, postDate} = renderRow();
    chip.removeAttribute('data-editable');

    dblclick(postDate);

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores a double-click on a trashed element', () => {
    const {postDate} = renderRow({'data-trashed': ''});

    dblclick(postDate);

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores an element with no edit url', () => {
    const {chip, assetChip, postDate} = renderRow();
    chip.removeAttribute('data-cp-url');
    assetChip.removeAttribute('data-cp-url');

    dblclick(postDate);

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores rows inside an element picker', () => {
    const {row, postDate} = renderRow();
    const table = row.closest('table')!;
    const picker = document.createElement('div');
    picker.className = 'elementselect';
    table.replaceWith(picker);
    picker.appendChild(table);

    dblclick(postDate);

    // Rows in a picker are a selection UI, not an index.
    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores a double-click outside any row', () => {
    renderRow();

    dblclick(document.body);

    expect(openSlideout).not.toHaveBeenCalled();
  });
});

describe('clicking a chip', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });
  afterEach(() => {
    vi.useRealTimers();
  });

  /** Records the clicks that make it past the deferral to the row. */
  function rowClicks(row: Element): MouseEvent[] {
    const clicks: MouseEvent[] = [];
    row.addEventListener('click', (event) => clicks.push(event as MouseEvent));

    return clicks;
  }

  it('holds the click back until a double-click is ruled out', () => {
    const {row, assetChip} = renderRow();
    const clicks = rowClicks(row);
    const label = assetChip.querySelector('.label-link')!;

    const event = click(label);

    // Not reaching the row (and toggling its selection), yet.
    expect(event.defaultPrevented).toBe(true);
    expect(clicks).toHaveLength(0);

    vi.advanceTimersByTime(CHIP_DOUBLE_CLICK_DELAY);

    expect(clicks).toHaveLength(1);
    expect(clicks[0]!.target).toBe(label);
    expect(clicks[0]!.defaultPrevented).toBe(false);
  });

  it('follows a link around the chip straight away', () => {
    const {row, link} = renderRow();
    const clicks = rowClicks(row);
    const mouseups: Event[] = [];
    row.addEventListener('mouseup', (event) => mouseups.push(event));
    const label = link.querySelector('.label-link')!;

    dispatch(label, 'mouseup', deferChipClick, {detail: 1});
    const event = click(label);

    expect(event.defaultPrevented).toBe(false);
    expect(mouseups).toHaveLength(1);
    expect(clicks).toHaveLength(1);
  });

  it('holds back the mouseup and replays it with the click', () => {
    const {row, assetChip} = renderRow();
    const mouseups: Event[] = [];
    row.addEventListener('mouseup', (event) => mouseups.push(event));
    const label = assetChip.querySelector('.label-link')!;

    dispatch(label, 'mouseup', deferChipClick, {detail: 1});
    click(label);

    expect(mouseups).toHaveLength(0);

    vi.advanceTimersByTime(CHIP_DOUBLE_CLICK_DELAY);

    expect(mouseups).toHaveLength(1);
  });

  it('drops both clicks of a double-click', () => {
    const {row, assetChip} = renderRow();
    const clicks = rowClicks(row);
    const label = assetChip.querySelector('.label-link')!;

    click(label);
    const second = click(label, {detail: 2});
    vi.advanceTimersByTime(CHIP_DOUBLE_CLICK_DELAY);

    expect(second.defaultPrevented).toBe(true);
    expect(clicks).toHaveLength(0);
  });

  it.each([
    ['a modified click', {metaKey: true}],
    ['a shift-click', {shiftKey: true}],
    ['a keyboard activation', {detail: 0}],
    ['a secondary click', {button: 1}],
  ])('lets %s through straight away', (_label, init) => {
    const {row, assetChip} = renderRow();
    const clicks = rowClicks(row);

    const event = click(assetChip, init);

    expect(event.defaultPrevented).toBe(false);
    expect(clicks).toHaveLength(1);
  });

  it('lets a click on a button inside the chip through straight away', () => {
    const {row, assetChip} = renderRow();
    const clicks = rowClicks(row);

    click(assetChip.querySelector('button')!);

    expect(clicks).toHaveLength(1);
  });

  it('lets a click elsewhere in the row through straight away', () => {
    const {row, postDate} = renderRow();
    const clicks = rowClicks(row);

    click(postDate);

    expect(clicks).toHaveLength(1);
  });
});

/**
 * A save in the slideout has to show up in the row behind it. A full
 * `router.reload()` would work but throws away scroll position and the table's
 * selection, so the index asks for just the results.
 */
describe('refreshing the index after a save', () => {
  /** The `onSaved` handler `useElementQuickEdit` registered with the panel. */
  function openedWith(): (result: {draft?: boolean}) => void {
    const {postDate} = renderRow();
    dblclick(postDate);

    return openSlideout.mock.calls[0]![1].onSaved;
  }

  it('pulls fresh rows without leaving the page', () => {
    openedWith()({});

    // The index's own partial reload — not a full visit, and not the
    // bulk-action one, which would clear the selection too.
    expect(refreshResults).toHaveBeenCalled();
  });

  it('debounces autosaved drafts into one refresh', async () => {
    vi.useFakeTimers();

    try {
      const onSaved = openedWith();

      onSaved({draft: true});
      onSaved({draft: true});
      onSaved({draft: true});

      // Typing shouldn't cost a request per keystroke-batch…
      expect(refreshResults).not.toHaveBeenCalled();

      await vi.advanceTimersByTimeAsync(600);

      // …but the last one always lands.
      expect(refreshResults).toHaveBeenCalledTimes(1);
    } finally {
      vi.useRealTimers();
    }
  });
});
