import type {Meta, StoryObj} from '@storybook/html-vite';
import {installGarnishCompat} from '../src/compat';
import {createEventLog, storyLayout} from './_log';
import {buildModalContainer} from './_helpers';

/**
 * Compat upgrade path — playground section 3.
 *
 * Calls `installGarnishCompat()`, then defines a subclass with the legacy
 * `Garnish.Modal.extend({ init, onShow })` API and a `this.base()` super-call —
 * proving the legacy authoring contract works in the browser against the modern
 * `class Modal`. This is the `.extend()` / `this.base` upgrade story.
 */
const meta: Meta = {
  title: 'Compat',
};
export default meta;

type Story = StoryObj;

interface LegacyCtorLike {
  extend(instance: Record<string, unknown>, statics?: object): LegacyCtorLike;
  new (...args: unknown[]): unknown;
}

interface GarnishGlobalLike {
  Modal: LegacyCtorLike;
  [key: string]: unknown;
}

export const ExtendUpgradePath: Story = {
  render: () => {
    const log = createEventLog('Compat story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Calls <code>installGarnishCompat()</code>, then defines a subclass with the
        legacy <code>Garnish.Modal.extend({ init, onShow })</code> API and a
        <code>this.base()</code> super-call — proving the legacy authoring contract
        works against the modern <code>class Modal</code>.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="install">installGarnishCompat()</button>
        <button type="button" data-act="extend">Instantiate Garnish.Modal.extend(...) subclass</button>
      </div>`;

    let compatGarnish: GarnishGlobalLike | null = null;

    const actions: Record<string, () => void> = {
      install: () => {
        compatGarnish = installGarnishCompat() as unknown as GarnishGlobalLike;
        const onWindow =
          (window as unknown as {Garnish?: unknown}).Garnish !== undefined;
        log.log(
          'compat',
          `installGarnishCompat() ran. window.Garnish present: ${onWindow}; Garnish.Modal.extend is ${typeof compatGarnish.Modal.extend}`
        );
      },
      extend: () => {
        if (!compatGarnish) {
          actions.install!();
        }
        const Garnish = compatGarnish!;

        // Define the subclass exactly the legacy way: init() trampoline + this.base().
        const DemoModalSubclass = Garnish.Modal.extend({
          init(this: {
            base: (...a: unknown[]) => unknown;
            $container: HTMLElement | null;
          }) {
            const container = buildModalContainer(
              'Legacy .extend() subclass',
              '<p>This modal was created via <code>Garnish.Modal.extend({ init, onShow })</code> and uses <code>this.base()</code> in <code>onShow</code>.</p>'
            );
            // Call the modern Modal constructor through the init trampoline.
            this.base(container, {autoShow: false});
            log.log(
              'compat',
              'subclass init() ran, called this.base(container)'
            );
          },
          onShow(this: {base: () => void}) {
            // this.base() dispatches to Modal.prototype.onShow (fires 'show').
            this.base();
            log.log('compat', 'subclass onShow() ran, then called this.base()');
          },
        }) as LegacyCtorLike;

        const instance = new DemoModalSubclass() as {
          on(evt: string, fn: () => void): void;
          show(): void;
        };
        instance.on('show', () =>
          log.log('compat', "subclass 'show' event observed")
        );
        instance.show();
        log.log('compat', 'Instantiated + show()ed the .extend() subclass');
      },
    };

    main.querySelectorAll<HTMLButtonElement>('[data-act]').forEach((btn) => {
      btn.addEventListener('click', () => {
        try {
          actions[btn.dataset.act!]?.();
        } catch (err) {
          log.log('compat', `Error: ${(err as Error).message}`, true);
        }
      });
    });

    return storyLayout(main, log);
  },
};
