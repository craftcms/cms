import type {Meta, StoryObj} from '@storybook/html-vite';
import {BaseDrag, DragMove} from '../src/index';
import {createEventLog, wireDragEvents, storyLayout} from './_log';

/**
 * Standalone `BaseDrag` / `DragMove` — playground sections 5 (standalone boxes)
 * and 6 (auto-scroll while dragging).
 */
const meta: Meta = {
  title: 'Drag/BaseDrag',
};
export default meta;

type Story = StoryObj;

export const StandaloneBoxes: Story = {
  render: () => {
    const log = createEventLog();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Free-floating boxes inside the bounded area. The green boxes use
        <code>new DragMove(box)</code> directly; the blue box uses a raw
        <code>new BaseDrag(box, {onDrag})</code> that sets <code>left/top</code>
        itself. The third green box is X-axis-locked (<code>{axis: 'x'}</code>).
      </p>
      <div class="pg-controls">
        <button type="button" data-act="reset">Reset box positions</button>
      </div>
      <div class="pg-drag-arena" data-arena>
        <div class="pg-drag-box pg-drag-box--move" data-box="move-1">DragMove #1</div>
        <div class="pg-drag-box pg-drag-box--move" data-box="move-2">DragMove #2</div>
        <div class="pg-drag-box pg-drag-box--move pg-drag-box--xlock" data-box="move-x">DragMove (x-axis only)</div>
        <div class="pg-drag-box pg-drag-box--base" data-box="base-1">BaseDrag + onDrag</div>
      </div>`;

    const arena = main.querySelector<HTMLElement>('[data-arena]')!;

    const boxHomes: Record<string, {left: number; top: number}> = {
      'move-1': {left: 16, top: 16},
      'move-2': {left: 170, top: 16},
      'move-x': {left: 16, top: 100},
      'base-1': {left: 170, top: 100},
    };

    const placeBox = (box: HTMLElement): void => {
      const home = boxHomes[box.dataset.box!];
      if (home) {
        box.style.left = `${home.left}px`;
        box.style.top = `${home.top}px`;
      }
    };

    const boxes = Array.from(arena.querySelectorAll<HTMLElement>('[data-box]'));
    boxes.forEach(placeBox);

    // Green boxes: DragMove drives left/top for us.
    arena
      .querySelectorAll<HTMLElement>(
        '.pg-drag-box--move:not(.pg-drag-box--xlock)'
      )
      .forEach((box) => {
        const dragger = new DragMove(box);
        wireDragEvents(log, dragger, 'drag', box.dataset.box!);
      });

    // X-axis-locked DragMove.
    const xBox = arena.querySelector<HTMLElement>('.pg-drag-box--xlock');
    if (xBox) {
      const dragger = new DragMove(xBox, {axis: 'x'});
      wireDragEvents(log, dragger, 'drag', `${xBox.dataset.box} (x-locked)`);
    }

    // Blue box: raw BaseDrag with an onDrag that positions it ourselves.
    const baseBox = arena.querySelector<HTMLElement>('.pg-drag-box--base');
    if (baseBox) {
      let start = {left: 0, top: 0};
      const dragger: BaseDrag = new BaseDrag(baseBox, {
        onDragStart: () => {
          start = {left: baseBox.offsetLeft, top: baseBox.offsetTop};
        },
        onDrag: () => {
          baseBox.style.left = `${start.left + dragger.mouseDistX!}px`;
          baseBox.style.top = `${start.top + dragger.mouseDistY!}px`;
        },
      });
      wireDragEvents(log, dragger, 'drag', 'base-1 (manual onDrag)');
    }

    main
      .querySelector<HTMLButtonElement>('[data-act="reset"]')!
      .addEventListener('click', () => {
        boxes.forEach(placeBox);
        log.log('drag', 'Reset box positions');
      });

    return storyLayout(main);
  },
};

export const AutoScroll: Story = {
  render: () => {
    const log = createEventLog();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        The orange item lives inside a tall scrollable container. Grab it and drag
        toward the top or bottom edge (within ~25px) — the container should
        auto-scroll via the RAF loop.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="reset">Reset</button>
      </div>
      <div class="pg-scroll-container" data-scroll-container>
        <div class="pg-scroll-tall">
          <div class="pg-drag-box pg-drag-box--scroll" data-box="scroll-1">Drag me near an edge</div>
          <p class="pg-scroll-filler">↑ tall scrollable content ↑</p>
          <p class="pg-scroll-filler">scroll me by dragging to the edges</p>
          <p class="pg-scroll-filler">↓ keep going ↓</p>
        </div>
      </div>`;

    const scrollContainer = main.querySelector<HTMLElement>(
      '[data-scroll-container]'
    )!;
    const scrollBox = scrollContainer.querySelector<HTMLElement>(
      '.pg-drag-box--scroll'
    )!;

    const dragger = new DragMove(scrollBox);
    wireDragEvents(log, dragger, 'autoscroll', 'scroll-box');

    main
      .querySelector<HTMLButtonElement>('[data-act="reset"]')!
      .addEventListener('click', () => {
        scrollBox.style.left = '20px';
        scrollBox.style.top = '20px';
        scrollContainer.scrollTop = 0;
        log.log('autoscroll', 'Reset scroll item + scroll position');
      });

    return storyLayout(main);
  },
};
