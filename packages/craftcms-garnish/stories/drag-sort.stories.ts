import type {Meta, StoryObj} from '@storybook/html-vite';
import {DragSort} from '../src/index';
import {createEventLog, storyLayout} from './_log';

/**
 * `DragSort` — reorderable list — playground section 9.
 *
 * A `new DragSort(items, { insertion, ... })` wired to a sortable list. Grab a
 * row and drag it: the list reflows live around a dashed `insertion` placeholder
 * (`insertionPointChange` logs each move). On drop the new order is committed and
 * `sortChange` logs the new index order. The list is `axis: 'y'`-locked.
 */
const meta: Meta = {
  title: 'Drag/DragSort',
};
export default meta;

type Story = StoryObj;

export const ReorderableList: Story = {
  render: () => {
    const log = createEventLog('DragSort story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Grab a row and drag it up or down — the list reflows live around a dashed
        <code>insertion</code> placeholder. On drop the new order commits;
        <code>sortChange</code> logs the new order. Handles carry
        <code>touch-action: none</code>.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="reset">Reset list order</button>
      </div>
      <ul class="pg-sort-list" data-sort-list>
        <li class="pg-sort-item" data-sort-item="1"><span class="pg-sort-grip" aria-hidden="true">⠿</span> Apples</li>
        <li class="pg-sort-item" data-sort-item="2"><span class="pg-sort-grip" aria-hidden="true">⠿</span> Oranges</li>
        <li class="pg-sort-item" data-sort-item="3"><span class="pg-sort-grip" aria-hidden="true">⠿</span> Bananas</li>
        <li class="pg-sort-item" data-sort-item="4"><span class="pg-sort-grip" aria-hidden="true">⠿</span> Grapes</li>
        <li class="pg-sort-item" data-sort-item="5"><span class="pg-sort-grip" aria-hidden="true">⠿</span> Mangoes</li>
      </ul>`;

    const sortList = main.querySelector<HTMLElement>('[data-sort-list]')!;
    const items = Array.from(
      sortList.querySelectorAll<HTMLElement>('[data-sort-item]')
    );
    const originalOrder = items.map((el) => el.dataset.sortItem!);

    const currentOrder = (): string =>
      Array.from(sortList.querySelectorAll<HTMLElement>('[data-sort-item]'))
        .map((el) => el.dataset.sortItem)
        .join(', ');

    const dragSort = new DragSort(items, {
      container: sortList,
      axis: 'y',
      hideDraggee: true,
      helperOpacity: 0.95,
      insertion: () => {
        const ph = document.createElement('li');
        ph.className = 'pg-sort-insertion';
        return ph;
      },
      onInsertionPointChange() {
        log.log(
          'dragsort',
          `insertionPointChange → order now [${currentOrder()}]`
        );
      },
      onSortChange() {
        log.log('dragsort', `sortChange → new order [${currentOrder()}]`);
      },
    });

    // Surface the raw drag lifecycle too (coalesce the per-frame `drag`).
    let dragging = false;
    dragSort.on('dragStart', () => {
      dragging = false;
      log.log('dragsort', 'dragStart');
    });
    dragSort.on('drag', () => {
      if (!dragging) {
        dragging = true;
        log.log('dragsort', 'drag (reordering…)');
      }
    });
    dragSort.on('dragStop', () => log.log('dragsort', 'dragStop'));

    main
      .querySelector<HTMLButtonElement>('[data-act="reset"]')!
      .addEventListener('click', () => {
        for (const id of originalOrder) {
          const el = sortList.querySelector<HTMLElement>(
            `[data-sort-item="${id}"]`
          );
          if (el) sortList.appendChild(el);
        }
        log.log('dragsort', `Reset list order → [${currentOrder()}]`);
      });

    return storyLayout(main, log);
  },
};
