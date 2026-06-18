import type {Meta, StoryObj} from '@storybook/html-vite';
import {Drag} from '../src/index';
import {createEventLog, storyLayout, type EventLog} from './_log';

/**
 * `Drag` with helpers — playground section 7.
 *
 * Grabbing an item builds a floating helper clone that trails the cursor with
 * lag (`createHelper` + the lag-follow RAF loop). On drop the helper animates
 * back to its source via the Web Animations API (`returnHelpersToDraggees`) — or
 * fades out (`fadeOutHelpers`), selectable via the `dropMode` control.
 */
const meta: Meta = {
  title: 'Drag/Drag',
};
export default meta;

interface DragArgs {
  dropMode: 'return' | 'fade';
}

type Story = StoryObj<DragArgs>;

/** Wire a Drag's lifecycle into the log, including `returnHelpersToDraggees`. */
function wireHelperDragEvents(
  log: EventLog,
  dragger: Drag,
  label: string
): void {
  let dragging = false;
  dragger.on('dragStart', () => {
    dragging = false;
    log.log('draghelper', `${label}: dragStart (helper clone created)`);
  });
  dragger.on('drag', () => {
    if (!dragging) {
      dragging = true;
      log.log('draghelper', `${label}: drag (helper trailing cursor…)`);
    }
  });
  dragger.on('dragStop', () => log.log('draghelper', `${label}: dragStop`));
  dragger.on('returnHelpersToDraggees', () =>
    log.log('draghelper', `${label}: returnHelpersToDraggees (helpers home)`)
  );
}

export const Helpers: Story = {
  argTypes: {
    dropMode: {
      control: 'inline-radio',
      options: ['return', 'fade'],
      description: 'On drop: return helpers to source, or fade them out',
    },
  },
  args: {
    dropMode: 'return',
  },
  render: (args) => {
    const log = createEventLog('Drag (helpers) story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        These rows use <code>new Drag(items, {...})</code>. Grabbing an item builds
        a floating <strong>helper clone</strong> that trails the cursor. On drop the
        helper either animates back to its source or fades out — pick via the
        <code>dropMode</code> control.
      </p>
      <div class="pg-helper-list" data-helper-list>
        <div class="pg-helper-item" data-helper-item="1"><span class="pg-helper-grip" aria-hidden="true">⠿</span> Draggable item #1 — grab &amp; drag me</div>
        <div class="pg-helper-item" data-helper-item="2"><span class="pg-helper-grip" aria-hidden="true">⠿</span> Draggable item #2 — a helper clone trails the cursor</div>
        <div class="pg-helper-item" data-helper-item="3"><span class="pg-helper-grip" aria-hidden="true">⠿</span> Draggable item #3 — release to animate home</div>
        <div class="pg-helper-item" data-helper-item="4"><span class="pg-helper-grip" aria-hidden="true">⠿</span> Draggable item #4</div>
      </div>`;

    const list = main.querySelector<HTMLElement>('[data-helper-list]')!;
    const items = Array.from(
      list.querySelectorAll<HTMLElement>('[data-helper-item]')
    );

    items.forEach((item) => {
      const dragger = new Drag(item, {
        // Hide the source while its helper clone is in flight.
        hideDraggee: true,
        helperOpacity: 0.92,
        onDragStop() {
          // Default Drag does NOT auto-return; the consumer decides.
          if (args.dropMode === 'fade') {
            dragger.fadeOutHelpers();
            log.log(
              'draghelper',
              `${item.dataset.helperItem}: fadeOutHelpers()`
            );
          } else {
            dragger.returnHelpersToDraggees();
          }
        },
      });
      wireHelperDragEvents(log, dragger, `item ${item.dataset.helperItem}`);
    });

    return storyLayout(main, log);
  },
};
