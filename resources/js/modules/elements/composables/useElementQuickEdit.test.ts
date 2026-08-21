import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const openSlideout = vi.hoisted(() => vi.fn());
const refreshResults = vi.hoisted(() => vi.fn());

vi.mock('@/common/slideouts', () => ({openSlideout}));
vi.mock('@/modules/elements/composables/useElementIndexTable', () => ({
    useElementIndexTable: () => ({refreshResults}),
}));

const {useElementQuickEdit} = await import('./useElementQuickEdit');

const {onDblClick} = useElementQuickEdit();

beforeEach(() => {
    openSlideout.mockReset();
    refreshResults.mockReset();
});
afterEach(() => {
    document.body.innerHTML = '';
});

const CP_URL = '/admin/entries/news/5-hello';

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

function dblclick(target: Element): MouseEvent {
    const event = new MouseEvent('dblclick', {bubbles: true, cancelable: true});
    Object.defineProperty(event, 'target', {value: target});
    onDblClick(event);

    return event;
}

describe('useElementQuickEdit', () => {
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
            [
                'the chip inside the link',
                (r: ReturnType<typeof renderRow>) => r.chip,
            ],
            [
                'the select checkbox',
                (r: ReturnType<typeof renderRow>) => r.checkbox,
            ],
            [
                'the action menu',
                (r: ReturnType<typeof renderRow>) => r.actionMenu,
            ],
        ])('ignores a double-click on %s', (_label, pick) => {
            const row = renderRow();

            const event = dblclick(pick(row));

            expect(openSlideout).not.toHaveBeenCalled();
            // The control's own behaviour has to survive untouched.
            expect(event.defaultPrevented).toBe(false);
        });

        it('ignores a double-click on a checkbox inside a card', () => {
            const {checkbox} = renderCard();

            dblclick(checkbox);

            expect(openSlideout).not.toHaveBeenCalled();
        });

        it('ignores content nested inside a link', () => {
            const {link} = renderRow();

            dblclick(link.querySelector('.label-link')!);

            expect(openSlideout).not.toHaveBeenCalled();
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
        const {chip, postDate} = renderRow();
        chip.removeAttribute('data-cp-url');

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
