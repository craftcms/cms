import type {Meta, StoryObj} from '@storybook/html-vite';
import {Modal, type ModalSettings} from '../src/index';
import {createEventLog, wireDragEvents, storyLayout} from './_log';
import {buildModalContainer, buildDragModalContainer} from './_helpers';

/**
 * `Modal` — construct `new Modal(container)` instances and drive their lifecycle.
 *
 * Migrated from playground sections 1 (Modal) and 4 (Draggable & resizable Modal).
 * Every `show / hide / fadeIn / fadeOut / escape` (and, for draggable/resizable
 * modals, `dragStart / drag / dragStop`) event is piped into the event log.
 */

interface ModalArgs {
  autoShow: boolean;
  hideOnEsc: boolean;
  hideOnShadeClick: boolean;
  closeOtherModals: boolean;
}

const meta: Meta = {
  title: 'Modal',
};
export default meta;

type Story = StoryObj<ModalArgs>;

/** Wire every demo event of a modal into the log panel. */
function wireModalEvents(
  log: ReturnType<typeof createEventLog>,
  modal: Modal,
  label: string
): void {
  for (const evt of ['show', 'hide', 'fadeIn', 'fadeOut', 'escape'] as const) {
    modal.on(evt, () => log.log('modal', `${label}: ${evt}`));
  }
}

export const Basic: Story = {
  argTypes: {
    autoShow: {
      control: 'boolean',
      description: 'Show the modal on construction',
    },
    hideOnEsc: {control: 'boolean', description: 'Close when Esc is pressed'},
    hideOnShadeClick: {
      control: 'boolean',
      description: 'Close when the dimmed shade is clicked',
    },
    closeOtherModals: {
      control: 'boolean',
      description: 'Opening this modal hides any other visible modal',
    },
  },
  args: {
    autoShow: false,
    hideOnEsc: true,
    hideOnShadeClick: true,
    closeOtherModals: false,
  },
  render: (args) => {
    const log = createEventLog('Modal story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Construct <code>new Modal()</code> instances and drive their lifecycle.
        The settings controls (Esc / shade / closeOtherModals) feed straight into
        <code>ModalSettings</code>. Open the event log to watch events fire.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="show">new Modal() + show()</button>
        <button type="button" data-act="hide">hide()</button>
        <button type="button" data-act="quick-show">quickShow()</button>
        <button type="button" data-act="quick-hide">quickHide()</button>
        <button type="button" data-act="destroy">destroy() all modals</button>
      </div>`;

    let modal: Modal | null = null;
    const getModal = (): Modal => {
      if (modal && Modal.instances.includes(modal)) {
        return modal;
      }
      const settings: Partial<ModalSettings> = {
        autoShow: args.autoShow,
        hideOnEsc: args.hideOnEsc,
        hideOnShadeClick: args.hideOnShadeClick,
        closeOtherModals: args.closeOtherModals,
      };
      const container = buildModalContainer(
        'Basic modal',
        '<p>A plain <code>new Modal(container, settings)</code>. Try quickShow / quickHide, or toggle the settings controls and reopen.</p>'
      );
      modal = new Modal(container, settings);
      wireModalEvents(log, modal, 'basic');
      return modal;
    };

    const actions: Record<string, () => void> = {
      show: () => getModal().show(),
      hide: () => getModal().hide(),
      'quick-show': () => getModal().quickShow(),
      'quick-hide': () => getModal().quickHide(),
      destroy: () => {
        const count = Modal.instances.length;
        [...Modal.instances].forEach((m) => m.destroy());
        modal = null;
        log.log('modal', `Destroyed ${count} modal instance(s)`);
      },
    };

    main.querySelectorAll<HTMLButtonElement>('[data-act]').forEach((btn) => {
      btn.addEventListener('click', () => {
        try {
          actions[btn.dataset.act!]?.();
        } catch (err) {
          log.log('modal', `Error: ${(err as Error).message}`, true);
        }
      });
    });

    return storyLayout(main, log);
  },
};

interface DragModalArgs {
  draggable: boolean;
  headerHandleOnly: boolean;
  resizable: boolean;
}

export const DraggableAndResizable: StoryObj<DragModalArgs> = {
  argTypes: {
    draggable: {control: 'boolean', description: 'Modal can be dragged'},
    headerHandleOnly: {
      control: 'boolean',
      description: 'Restrict dragging to the header bar (dragHandleSelector)',
    },
    resizable: {control: 'boolean', description: 'Modal can be resized'},
  },
  args: {
    draggable: true,
    headerHandleOnly: true,
    resizable: true,
  },
  render: (args) => {
    const log = createEventLog('Draggable/resizable Modal story loaded.');
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Modal's <code>draggable</code> / <code>resizable</code> options (they used
        to throw). Open the modal and drag/resize it live — <code>dragStart / drag /
        dragStop</code> events pipe into the log.
      </p>
      <div class="pg-controls">
        <button type="button" data-act="open">Open modal (from current settings)</button>
        <button type="button" data-act="destroy">Destroy these modals</button>
      </div>`;

    const dragModals: Modal[] = [];

    const actions: Record<string, () => void> = {
      open: () => {
        const withHandle = args.draggable && args.headerHandleOnly;
        const container = buildDragModalContainer(
          'Drag / resize me',
          '<p>Drag the modal (the header bar if <code>dragHandleSelector</code> is set) and/or grab the corner handle to resize.</p>',
          withHandle
        );
        const settings: Partial<ModalSettings> = {
          draggable: args.draggable,
          resizable: args.resizable,
        };
        if (withHandle) {
          settings.dragHandleSelector = '[data-drag-handle]';
        }
        const modal = new Modal(container, settings);
        wireModalEvents(log, modal, 'drag-modal');
        if (modal.dragger) {
          wireDragEvents(log, modal.dragger, 'modal-drag', 'modal (move)');
        }
        if (modal.resizeDragger) {
          wireDragEvents(
            log,
            modal.resizeDragger,
            'modal-drag',
            'modal (resize)'
          );
        }
        dragModals.push(modal);
        log.log('modal', 'Opened modal — drag/resize it; watch the log.');
      },
      destroy: () => {
        const count = dragModals.length;
        dragModals.splice(0).forEach((m) => m.destroy());
        log.log('modal', `Destroyed ${count} draggable/resizable modal(s)`);
      },
    };

    main.querySelectorAll<HTMLButtonElement>('[data-act]').forEach((btn) => {
      btn.addEventListener('click', () => {
        try {
          actions[btn.dataset.act!]?.();
        } catch (err) {
          log.log('modal', `Error: ${(err as Error).message}`, true);
        }
      });
    });

    return storyLayout(main, log);
  },
};
