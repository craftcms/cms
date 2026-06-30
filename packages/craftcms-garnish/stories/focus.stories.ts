import type {Meta, StoryObj} from '@storybook/html-vite';
import {Modal, getFocusableElements, isKeyboardFocusable} from '../src/index';
import {createEventLog, storyLayout} from './_log';
import {buildModalContainer} from './_helpers';

/**
 * Focusable matcher & focus trap — playground section 2.
 *
 * Run `getFocusableElements` / `isKeyboardFocusable` over a mixed container to
 * highlight what they match, then open a focus-trap modal and Tab / Shift+Tab to
 * see cycling stay trapped inside.
 */
const meta: Meta = {
  title: 'Focus',
};
export default meta;

type Story = StoryObj;

export const FocusableMatcherAndTrap: Story = {
  render: () => {
    const log = createEventLog();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        The container mixes focusable and non-focusable elements. Run the matcher
        to highlight what <code>getFocusableElements</code> /
        <code>isKeyboardFocusable</code> select, then open the focus-trap modal and
        press <kbd>Tab</kbd> / <kbd>Shift+Tab</kbd>.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="highlight">Highlight focusable elements</button>
        <button type="button" data-act="clear">Clear highlights</button>
        <button type="button" data-act="trap-modal">Open focus-trap modal</button>
      </div>
      <div class="pg-focus-sample" data-focus-sample>
        <a href="#">Link with href (focusable)</a>
        <a>Link, no href (not focusable)</a>
        <button type="button">Enabled button (focusable)</button>
        <button type="button" disabled>Disabled button (not focusable)</button>
        <input type="text" placeholder="Text input (focusable)" />
        <input type="text" disabled placeholder="Disabled input" />
        <span tabindex="0">Span tabindex=0 (keyboard focusable)</span>
        <span tabindex="-1">Span tabindex=-1 (focusable, not keyboard)</span>
        <span>Plain span (not focusable)</span>
        <textarea placeholder="Textarea (focusable)"></textarea>
      </div>`;

    const sample = main.querySelector<HTMLElement>('[data-focus-sample]')!;

    const clear = (): void => {
      sample
        .querySelectorAll('.pg-highlight-focusable, .pg-highlight-keyboard')
        .forEach((el) =>
          el.classList.remove('pg-highlight-focusable', 'pg-highlight-keyboard')
        );
    };

    const actions: Record<string, () => void> = {
      highlight: () => {
        clear();
        let focusableCount = 0;
        let keyboardCount = 0;
        getFocusableElements(sample).forEach((el) => {
          el.classList.add('pg-highlight-focusable');
          focusableCount++;
          if (isKeyboardFocusable(el)) {
            el.classList.add('pg-highlight-keyboard');
            keyboardCount++;
          }
        });
        log.log(
          'focus',
          `getFocusableElements matched ${focusableCount}; ${keyboardCount} keyboard-focusable (blue ring)`
        );
      },
      clear,
      'trap-modal': () => {
        const container = buildModalContainer(
          'Focus trap',
          `<p>Tab / Shift+Tab cycles only within these three controls:</p>
           <p>
             <button type="button">First</button>
             <input type="text" placeholder="Second" />
             <a href="#">Third</a>
           </p>`
        );
        const modal = new Modal(container);
        for (const evt of ['show', 'hide', 'escape'] as const) {
          modal.on(evt, () => log.log('focus', `focusTrap: ${evt}`));
        }
        log.log(
          'focus',
          'Opened focus-trap modal — Tab cycling is trapped inside'
        );
      },
    };

    main.querySelectorAll<HTMLButtonElement>('[data-act]').forEach((btn) => {
      btn.addEventListener('click', () => actions[btn.dataset.act!]?.());
    });

    return storyLayout(main);
  },
};
