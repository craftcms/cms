import type {Meta, StoryObj} from '@storybook/html-vite';
import {HUD, type HUDOrientation} from '../src/index';
import {createEventLog, storyLayout} from './_log';

/**
 * `HUD` — anchored popover — playground section 11.
 *
 * Each trigger in the arena anchors a `new HUD(trigger, …)`. Clicking a trigger
 * toggles its HUD, which picks one of four orientations from the clearance around
 * the trigger and draws a tip pointing back at it. Use the `preferredOrientation`
 * control to bias which side it tries first.
 */
const meta: Meta = {
  title: 'HUD',
};
export default meta;

interface HUDArgs {
  preferredOrientation: 'auto' | HUDOrientation;
}

type Story = StoryObj<HUDArgs>;

const ALL_ORIENTATIONS: HUDOrientation[] = ['bottom', 'top', 'right', 'left'];

export const AnchoredPopover: Story = {
  argTypes: {
    preferredOrientation: {
      control: 'select',
      options: ['auto', ...ALL_ORIENTATIONS],
      description:
        'Bias which orientation the HUD tries first (auto = the default bottom/top/right/left order)',
    },
  },
  args: {
    preferredOrientation: 'auto',
  },
  render: (args) => {
    const log = createEventLog('HUD story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Click a trigger to toggle its HUD: it picks an orientation from the
        clearance around the trigger and draws a <strong>tip</strong> pointing back
        at it. Triggers sit near different edges so you can see the side flip;
        scroll or resize to watch the HUD follow.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="destroy">Destroy all HUDs</button>
      </div>
      <div class="pg-hud-arena" data-arena>
        <button class="pg-hud-trigger" data-hud-trigger="top-left" style="top: 12px; left: 12px">Top-left</button>
        <button class="pg-hud-trigger" data-hud-trigger="top-right" style="top: 12px; right: 12px">Top-right</button>
        <button class="pg-hud-trigger" data-hud-trigger="mid-left" style="top: 50%; left: 12px">Mid-left</button>
        <button class="pg-hud-trigger" data-hud-trigger="mid-right" style="top: 50%; right: 12px">Mid-right</button>
        <button class="pg-hud-trigger" data-hud-trigger="bottom-center" style="bottom: 12px; left: 50%; transform: translateX(-50%)">Bottom-center</button>
      </div>`;

    const arena = main.querySelector<HTMLElement>('[data-arena]')!;
    const huds = new Map<HTMLElement, HUD>();

    const orientations =
      args.preferredOrientation === 'auto'
        ? ALL_ORIENTATIONS
        : [
            args.preferredOrientation,
            ...ALL_ORIENTATIONS.filter((o) => o !== args.preferredOrientation),
          ];

    const hudBody = (label: string): string => `
      <div class="pg-hud-content">
        <h4>HUD: ${label}</h4>
        <p>Orientation is chosen from the clearance around this trigger. Scroll or resize to watch the HUD follow.</p>
        <button type="button" class="pg-modal-primary" data-hud-close>Close</button>
      </div>`;

    const getHud = (trigger: HTMLElement): HUD => {
      const existing = huds.get(trigger);
      if (existing && HUD.instances.includes(existing)) {
        return existing;
      }
      const id = trigger.dataset.hudTrigger!;
      const hud = new HUD(trigger, hudBody(id), {
        showOnInit: false,
        hudClass: 'hud pg-hud',
        orientations,
      });
      hud.on('show', () => log.log('hud', `${id}: show`));
      hud.on('hide', () => log.log('hud', `${id}: hide`));
      hud.on('updateSizeAndPosition', () =>
        log.log('hud', `${id}: positioned → orientation "${hud.orientation}"`)
      );
      hud.$main
        ?.querySelector<HTMLButtonElement>('[data-hud-close]')
        ?.addEventListener('click', () => hud.hide());
      huds.set(trigger, hud);
      return hud;
    };

    arena
      .querySelectorAll<HTMLButtonElement>('[data-hud-trigger]')
      .forEach((trigger) => {
        trigger.addEventListener('click', () => getHud(trigger).toggle());
      });

    main
      .querySelector<HTMLButtonElement>('[data-act="destroy"]')!
      .addEventListener('click', () => {
        const count = HUD.instances.length;
        [...HUD.instances].forEach((h) => h.destroy());
        huds.clear();
        log.log('hud', `Destroyed ${count} HUD instance(s)`);
      });

    return storyLayout(main, log);
  },
};
