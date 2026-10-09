import type {Meta, StoryObj} from '@storybook/html-vite';
import {hasAttr, getDist, installActivate} from '../src/index';
import {createEventLog, storyLayout} from './_log';

/**
 * Events & utilities — playground section 10.
 *
 * Small demos of core utilities (`hasAttr`, `getDist`) and the synthetic
 * `activate` custom event (click / Space / Enter all dispatch it).
 */
const meta: Meta = {
  title: 'Utilities',
};
export default meta;

type Story = StoryObj;

export const EventsAndUtilities: Story = {
  render: () => {
    const log = createEventLog();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>Small demos of core utilities and the synthetic <code>activate</code> custom event.</p>
      <div class="pg-controls">
        <button type="button" data-act="hasattr">Garnish.hasAttr(this, "data-demo")</button>
        <button type="button" data-act="getdist">getDist(0,0,3,4)</button>
        <button type="button" data-act="activate" data-demo="yes">activate me (click / Space / Enter)</button>
      </div>`;

    const actions: Record<string, (btn: HTMLButtonElement) => void> = {
      hasattr: (btn) => {
        log.log(
          'util',
          `hasAttr(button, "data-demo") → ${hasAttr(btn, 'data-demo')}`
        );
      },
      getdist: () => {
        log.log('util', `getDist(0, 0, 3, 4) → ${getDist(0, 0, 3, 4)}`);
      },
      activate: (btn) => {
        // Install the synthetic `activate` event (idempotent), then listen.
        installActivate(btn);
        if (!btn.dataset.activateWired) {
          btn.addEventListener('activate', () => {
            log.log('util', 'activate custom event fired on the button');
          });
          btn.dataset.activateWired = 'yes';
          log.log(
            'util',
            'installActivate(button) — now click it or press Space/Enter'
          );
        }
      },
    };

    main.querySelectorAll<HTMLButtonElement>('[data-act]').forEach((btn) => {
      btn.addEventListener('click', () => actions[btn.dataset.act!]?.(btn));
    });

    return storyLayout(main);
  },
};
