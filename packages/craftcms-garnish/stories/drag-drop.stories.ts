import type {Meta, StoryObj} from '@storybook/html-vite';
import {DragDrop} from '../src/index';
import {createEventLog, storyLayout} from './_log';

/**
 * `DragDrop` — drop targets & hit detection — playground section 8.
 *
 * A `new DragDrop({ dropTargets, ... })` wired to draggable chips and three drop
 * zones. While dragging, the zone under the cursor highlights
 * (`activeDropTargetClass`) and `dropTargetChange` logs the new active target.
 * On release, the demo reads `$activeDropTarget` in its `dragStop` handler.
 */
const meta: Meta = {
  title: 'Drag/DragDrop',
};
export default meta;

type Story = StoryObj;

export const DropTargets: Story = {
  render: () => {
    const log = createEventLog();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Drag a chip over a zone: the zone under the cursor highlights and
        <code>dropTargetChange</code> logs the new active target (or
        <code>null</code>). On release the demo reads <code>$activeDropTarget</code>
        to report where it landed. Overlap resolves to the first target in document
        order.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="reset">Reset chips</button>
      </div>
      <div class="pg-dragdrop-layout">
        <div class="pg-dragdrop-chips" data-chips>
          <div class="pg-dragdrop-chip" data-dragdrop-chip="a">Chip A</div>
          <div class="pg-dragdrop-chip" data-dragdrop-chip="b">Chip B</div>
          <div class="pg-dragdrop-chip" data-dragdrop-chip="c">Chip C</div>
        </div>
        <div class="pg-dragdrop-zones" data-zones>
          <div class="pg-dropzone" data-dropzone="inbox">Inbox</div>
          <div class="pg-dropzone" data-dropzone="archive">Archive</div>
          <div class="pg-dropzone" data-dropzone="trash">Trash</div>
        </div>
      </div>`;

    const chips = Array.from(
      main.querySelectorAll<HTMLElement>('[data-dragdrop-chip]')
    );
    const zones = Array.from(
      main.querySelectorAll<HTMLElement>('[data-dropzone]')
    );

    const dragDrop = new DragDrop({
      dropTargets: zones,
      hideDraggee: true,
      helperOpacity: 0.92,
      onDropTargetChange(activeDropTarget) {
        const name = activeDropTarget?.dataset.dropzone ?? 'none';
        log.log('dragdrop', `dropTargetChange → ${name}`);
      },
      onDragStop() {
        // Legacy contract: no `drop` event — read $activeDropTarget here.
        const target = dragDrop.$activeDropTarget;
        if (target) {
          log.log(
            'dragdrop',
            `dropped on "${target.dataset.dropzone}" (read from $activeDropTarget)`
          );
          target.classList.add('pg-dropzone--hit');
          setTimeout(() => target.classList.remove('pg-dropzone--hit'), 600);
        } else {
          log.log('dragdrop', 'dropped outside any drop target');
        }
        dragDrop.returnHelpersToDraggees();
      },
    });
    dragDrop.addItems(chips);

    // Also surface the raw drag lifecycle (coalesce the per-frame `drag`).
    let dragging = false;
    dragDrop.on('dragStart', () => {
      dragging = false;
      log.log('dragdrop', 'dragStart');
    });
    dragDrop.on('drag', () => {
      if (!dragging) {
        dragging = true;
        log.log('dragdrop', 'drag (over zones…)');
      }
    });

    main
      .querySelector<HTMLButtonElement>('[data-act="reset"]')!
      .addEventListener('click', () => {
        zones.forEach((z) => z.classList.remove('active', 'pg-dropzone--hit'));
        log.log('dragdrop', 'Reset chips + cleared zone highlights');
      });

    return storyLayout(main);
  },
};
