import {effectScope} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const openSlideout = vi.hoisted(() => vi.fn());

vi.mock('@/common/slideouts', () => ({openSlideout}));

const {useElementQuickEdit} = await import('./useElementQuickEdit');

const navigate = vi.fn();
let scope: ReturnType<typeof effectScope>;
let handlers: ReturnType<typeof useElementQuickEdit>;

beforeEach(() => {
  vi.useFakeTimers();
  openSlideout.mockReset();
  navigate.mockReset();

  Object.defineProperty(window, 'location', {
    configurable: true,
    value: {
      get href() {
        return 'https://cp.test/admin/entries';
      },
      set href(value: string) {
        navigate(value);
      },
    },
  });

  scope = effectScope();
  scope.run(() => {
    handlers = useElementQuickEdit();
  });
});

afterEach(() => {
  scope.stop();
  vi.useRealTimers();
  document.body.innerHTML = '';
});

/**
 * Mirrors what `ContentIndexViewModel::tableRows()` produces: the chip, with
 * its `data-*` metadata, wrapped in a link to the element's edit page.
 */
function renderRow(
  attributes: Record<string, string> = {}
): {link: HTMLAnchorElement; chip: HTMLElement; row: HTMLElement} {
  const data = {
    'data-editable': '',
    'data-cp-url': '/admin/entries/news/5-hello',
    'data-id': '5',
    'data-type': 'craft\\elements\\Entry',
    ...attributes,
  };

  const table = document.createElement('table');
  table.innerHTML = `
    <tbody>
      <tr>
        <td class="cp-table-cell--title">
          <a href="https://cp.test/admin/entries/news/5-hello">
            <craft-chip class="element" ${Object.entries(data)
              .map(([key, value]) => `${key}="${value}"`)
              .join(' ')}>
              <span class="label">Hello</span>
            </craft-chip>
          </a>
        </td>
        <td class="cp-table-cell--postDate">Today</td>
      </tr>
    </tbody>
  `;
  document.body.appendChild(table);

  return {
    link: table.querySelector('a')!,
    chip: table.querySelector('.element')!,
    row: table.querySelector('tr')!,
  };
}

function click(target: Element, init: MouseEventInit = {}): MouseEvent {
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    button: 0,
    ...init,
  });
  Object.defineProperty(event, 'target', {value: target});
  handlers.onClick(event);

  return event;
}

function dblclick(target: Element): MouseEvent {
  const event = new MouseEvent('dblclick', {bubbles: true, cancelable: true});
  Object.defineProperty(event, 'target', {value: target});
  handlers.onDblClick(event);

  return event;
}

describe('useElementQuickEdit', () => {
  it('opens the element in a slideout on double-click', () => {
    const {chip} = renderRow();

    dblclick(chip);

    expect(openSlideout).toHaveBeenCalledWith(
      '/admin/entries/news/5-hello',
      expect.anything()
    );
  });

  it('opens from a double-click anywhere in the row, not just the chip', () => {
    const {row} = renderRow();

    dblclick(row.querySelector('.cp-table-cell--postDate')!);

    expect(openSlideout).toHaveBeenCalledWith(
      '/admin/entries/news/5-hello',
      expect.anything()
    );
  });

  it('stops the click reaching a link that navigates itself', () => {
    // The title renders through `CpLink`. If its `inertia` prop is on, it's an
    // Inertia `<Link>` that listens on the anchor and starts a visit — deeper
    // than this handler, so we have to claim the event in the capture phase
    // before it ever gets there.
    const {link} = renderRow();
    const stopPropagation = vi.fn();

    const event = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      button: 0,
    });
    Object.defineProperty(event, 'target', {value: link});
    event.stopPropagation = stopPropagation;

    handlers.onClick(event);

    expect(stopPropagation).toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(true);
  });

  it('leaves the event alone when there is nothing to open', () => {
    const {link, chip} = renderRow();
    chip.removeAttribute('data-editable');
    const stopPropagation = vi.fn();

    const event = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      button: 0,
    });
    Object.defineProperty(event, 'target', {value: link});
    event.stopPropagation = stopPropagation;

    handlers.onClick(event);

    expect(stopPropagation).not.toHaveBeenCalled();
    expect(event.defaultPrevented).toBe(false);
  });

  it('holds the link navigation so the second click can claim it', () => {
    const {link} = renderRow();

    const event = click(link);

    // The title is an anchor, so without this the first click would navigate
    // before `dblclick` ever fires.
    expect(event.defaultPrevented).toBe(true);
    expect(navigate).not.toHaveBeenCalled();

    vi.advanceTimersByTime(250);

    expect(navigate).toHaveBeenCalledWith(
      'https://cp.test/admin/entries/news/5-hello'
    );
  });

  it('cancels the held navigation when a double-click follows', () => {
    const {link, chip} = renderRow();

    click(link);
    click(link);
    dblclick(chip);

    vi.advanceTimersByTime(1000);

    expect(navigate).not.toHaveBeenCalled();
    expect(openSlideout).toHaveBeenCalledOnce();
  });

  it.each([
    ['meta', {metaKey: true}],
    ['ctrl', {ctrlKey: true}],
    ['shift', {shiftKey: true}],
    ['middle button', {button: 1}],
  ])('leaves a %s click to the browser', (_label, init) => {
    const {link} = renderRow();

    const event = click(link, init);

    // Open-in-new-tab and friends must stay instant.
    expect(event.defaultPrevented).toBe(false);
    expect(navigate).not.toHaveBeenCalled();
  });

  it('does not delay navigation for an element it cannot open', () => {
    const {link, chip} = renderRow();
    // PHP's `array_filter` omits `data-editable` entirely when false.
    chip.removeAttribute('data-editable');

    const event = click(link);

    // Nothing to open on double-click, so the link shouldn't be made to wait.
    expect(event.defaultPrevented).toBe(false);

    vi.advanceTimersByTime(1000);

    expect(navigate).not.toHaveBeenCalled();
  });

  it('ignores a double-click on a non-editable element', () => {
    const {chip} = renderRow();
    chip.removeAttribute('data-editable');

    dblclick(chip);

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores a double-click on a trashed element', () => {
    const {chip} = renderRow({'data-trashed': ''});

    dblclick(chip);

    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores elements inside an element picker', () => {
    const {chip} = renderRow();
    const picker = document.createElement('div');
    picker.className = 'elementselect';
    chip.parentElement!.replaceChildren();
    picker.appendChild(chip);
    document.body.appendChild(picker);

    dblclick(chip);

    // Chips in a picker are a selection UI, not an index.
    expect(openSlideout).not.toHaveBeenCalled();
  });

  it('ignores an element with no edit url', () => {
    const {chip} = renderRow();
    chip.removeAttribute('data-cp-url');

    dblclick(chip);

    expect(openSlideout).not.toHaveBeenCalled();
  });
});
