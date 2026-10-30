import {Base} from '@craftcms/garnish';
import type CraftButton from '@craftcms/ui/components/button/button';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';
import './element-deletion-manager.css';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui/utilities/dom';
import {elementTypeName} from '@/common/craft/element-types';
import {elevatedSessionManager} from '@/modules/auth/elevated-session/manager';
import {create} from '@craftcms/ui/utilities/create';

// jQuery-built blocker markup + Craft.ui/Craft.cp seams, shown in a
// `craft-dialog`.
declare const $: any;
declare const Craft: any;

const DEFAULTS = {
  siteId: null,
  ownerId: null,
  withDescendants: false,
  hardDelete: false,
  confirmationMessage: null,
  onLoadBlockers: () => {},
  onSuccess: () => {},
  onCancel: () => {},
};

/**
 * ElementDeletionManager — a port of `Craft.ElementDeletionManager` onto
 * `@craftcms/garnish` `Base`. Fetches an element's deletion blockers; if there
 * are none it confirms + deletes, otherwise it shows a modal listing each
 * blocker (a {@link Blocker}) with resolve actions. Booted by the element index
 * / PHP, so exposed on `window.Craft`.
 */
export class ElementDeletionManager extends Base {
  declare settings: any;

  elementType: any = null;
  elementIds: any = null;
  hardDelete = false;
  modal: CraftDialog | null = null;
  $blockersContainer: any = null;
  submitButton: CraftButton | null = null;
  blockers: any = null;
  succeeded = false;

  constructor(elementType?: any, elementIds?: any, settings?: any) {
    super();
    if (new.target === ElementDeletionManager) {
      this.init(elementType, elementIds, settings);
    }
  }

  init(elementType: any, elementIds: any, settings: any): void {
    this.elementType = elementType;
    this.elementIds = elementIds;
    this.setSettings(settings, DEFAULTS);
    this.blockers = [];
    this.run();
  }

  async run(): Promise<void> {
    const {data} = await actionClient.post(
      'delete-elements/deletion-blockers',
      this.getParams()
    );

    this.settings.onLoadBlockers();

    if (!data.blockers.length) {
      await this.confirmAndDelete(data.totalElements);
      return;
    }

    this.createModal(data);

    for (const blocker of data.blockers) {
      this.blockers.push(new Blocker(this, blocker));
    }

    await appendHeadHtml(data.headHtml);
    await appendBodyHtml(data.bodyHtml);
  }

  getParams(): any {
    return {
      elementType: this.elementType,
      elementIds: this.elementIds,
      siteId: this.settings.siteId,
      ownerId: this.settings.ownerId,
      withDescendants: this.settings.withDescendants,
      hardDelete: this.settings.hardDelete,
    };
  }

  resolveBlocker(blocker: any): void {
    const i = this.blockers.indexOf(blocker);
    if (i !== -1) {
      this.blockers.splice(i, 1);
    }

    if (!this.blockers.length && this.submitButton) {
      this.submitButton.disabled = false;
    }
  }

  async confirmAndDelete(totalElements: any): Promise<void> {
    const message = this.getConfirmationMessage(totalElements);
    if (!confirm(message)) {
      this.settings.onCancel();
      return;
    }

    if (this.submitButton) {
      this.submitButton.loading = true;
    }
    let data: any = null;
    try {
      const response = await actionClient.post(
        'delete-elements/delete',
        this.getParams()
      );
      data = response.data;
    } catch {
      return;
    } finally {
      if (this.submitButton) {
        this.submitButton.loading = false;
      }
    }

    this.succeeded = true;
    this.settings.onSuccess();
    this.hideModal();

    if (data) {
      if (data.showAsFailure) {
        Craft.cp.displayError(data.message);
      } else {
        Craft.cp.displaySuccess(data.message);
      }
    }
  }

  getConfirmationMessage(totalElements: any): string {
    if (this.settings.confirmationMessage) {
      return this.settings.confirmationMessage;
    }

    const typeName = elementTypeName(this.elementType, totalElements !== 1);

    if (this.settings.hardDelete) {
      return t(
        'Are you sure you want to permanently delete {numElements, plural, =1{this} other{these}} {type}?',
        {type: typeName, numElements: totalElements}
      );
    }

    return t(
      'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type}?',
      {type: typeName, numElements: totalElements}
    );
  }

  /**
   * Shows the blockers in a `craft-dialog`, which is styled in both the
   * Inertia and legacy control panels; Garnish's modal relies on the legacy
   * stylesheet. It's non-modal because a blocker's actions can open legacy
   * modals of their own, which would otherwise sit beneath it.
   */
  createModal(data: any): void {
    const dialog = document.createElement('craft-dialog') as CraftDialog;
    dialog.classList.add('element-deletion-modal');
    dialog.setAttribute('no-header', '');
    dialog.nonModal = true;
    dialog.resizable = true;

    const $heading = $('<h1/>', {
      class: 'edm-heading',
      html: t(
        'Before deleting {label}, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
        {numBlockers: data.blockers.length, label: data.elementPreview}
      ),
    }).appendTo(dialog);
    dialog.label = $heading.text().replace(/\s+/g, ' ').trim();

    this.$blockersContainer = $('<div/>', {class: 'edm-blockers'}).appendTo(
      dialog
    );

    const footer = document.createElement('div');
    footer.slot = 'footer';
    footer.className = 'edm-footer';
    const closeButton = create('craft-button', {type: 'button'}, t('Close'));
    this.submitButton = create(
      'craft-button',
      {type: 'button', variant: 'danger'},
      t('Delete {type}', {
        type: elementTypeName(this.elementType, this.elementIds.length !== 1),
      })
    );
    this.submitButton.disabled = true;
    footer.append(closeButton, this.submitButton);
    dialog.append(footer);

    dialog.addEventListener('craft-after-show', () => {
      Craft.cp?.elementThumbLoader?.load($(dialog));
    });
    dialog.addEventListener('craft-hide', () => {
      if (!this.succeeded) {
        this.settings.onCancel();
      }
    });
    dialog.addEventListener('craft-after-hide', () => {
      this.destroy();
    });

    document.body.append(dialog);
    dialog.opened = true;
    this.modal = dialog;

    closeButton.addEventListener('click', () => {
      this.hideModal();
    });

    this.submitButton.addEventListener('click', async () => {
      await this.confirmAndDelete(data.totalElements);
    });
  }

  hideModal(): void {
    if (this.modal) {
      this.modal.opened = false;
    }
  }

  override destroy(): void {
    this.modal?.remove();
    this.modal = null;

    for (const blocker of this.blockers) {
      blocker.destroy();
    }

    this.blockers = null;
  }
}

/**
 * A single deletion blocker row inside the modal — a summary, optional
 * expandable details, and resolve action buttons.
 */
class Blocker extends Base {
  manager: any = null;
  data: any = null;
  id: any = null;
  $container: any = null;
  buttons: CraftButton[] = [];

  constructor(manager?: any, data?: any) {
    super();
    if (new.target === Blocker) {
      this.init(manager, data);
    }
  }

  init(manager: any, data: any): void {
    this.manager = manager;
    this.data = data;
    this.buttons = [];

    this.id = `blocker-${Math.floor(Math.random() * 1000000)}`;
    this.$container = $('<div/>', {
      id: this.id,
      class: 'edm-blocker',
    }).appendTo(this.manager.$blockersContainer);

    const $heading = $('<h2/>', {class: 'edm-blocker-heading'}).appendTo(
      this.$container
    );

    if (this.data.details) {
      // A disclosure: the heading's button shows and hides the details. It's
      // drawn like craft-disclosure's own trigger, with the chevron after the
      // label, pointing toward the end of the line while closed and down
      // while open. craft-disclosure itself would need the button as its
      // direct child, outside the heading.
      const toggle = document.createElement('craft-button') as CraftButton;
      toggle.setAttribute('type', 'button');
      toggle.setAttribute('variant', 'plain');
      toggle.setAttribute('flush', '');
      toggle.setAttribute('aria-controls', `${this.id}-details`);
      toggle.iconPosition = 'suffix';
      toggle.append(this.data.summary);
      $heading.append(toggle);

      const $details = $('<div/>', {
        id: `${this.id}-details`,
        class: 'edm-details',
        hidden: true,
        html: this.data.details,
      }).appendTo(this.$container);

      const setExpanded = (expanded: boolean): void => {
        const rtl = getComputedStyle(toggle).direction === 'rtl';
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.icon = expanded
          ? 'chevron-down'
          : rtl
            ? 'chevron-left'
            : 'chevron-right';
        $details.prop('hidden', !expanded);
      };

      setExpanded(false);
      toggle.addEventListener('click', () => {
        setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
      });
    } else {
      $heading.text(this.data.summary);
    }

    if (this.data.actions) {
      const $actions = $('<div/>', {class: 'edm-actions'}).appendTo(
        this.$container
      );

      for (const action of this.data.actions) {
        const button = create(
          'craft-button',
          {
            type: 'button',
            icon: action.icon,
            variant: action.destructive ? 'danger' : 'outline',
          },
          action.label
        );
        const $button = $(button);
        this.buttons.push(button);

        if (action.id) {
          button.id = action.id;
        }

        if (action.class) {
          $button.addClass(action.class);
        }

        if (action.attributes) {
          $button.attr(action.attributes);
        }

        $button.appendTo($actions);

        button.addEventListener('click', async () => {
          if (action.callback) {
            let message;
            try {
              message = await new Promise((resolve, reject) => {
                const params = this.manager.getParams();
                // oxlint-disable-next-line @typescript-eslint/no-implied-eval -- the action callback is a server-provided code string that resolves/rejects using these named arguments (a legacy contract)
                const callback = new Function(
                  'elementType',
                  'elementIds',
                  'siteId',
                  'ownerId',
                  'withDescendants',
                  'hardDelete',
                  'resolve',
                  'reject',
                  'blocker',
                  'action',
                  action.callback
                );
                callback.call(
                  this,
                  params.elementType,
                  params.elementIds,
                  params.siteId,
                  params.ownerId,
                  params.withDescendants,
                  params.hardDelete,
                  resolve,
                  reject,
                  this,
                  action
                );
              });
            } catch {
              return;
            }
            await this.resolve(message);
            return;
          }

          if (action.confirm && !confirm(action.confirm)) {
            return;
          }

          if (!action.action) {
            return;
          }

          const submit = async () => {
            button.loading = true;
            let response;
            try {
              response = await actionClient.post(action.action, {
                elementIds: this.manager.elementIds,
                ...action.params,
              });
            } finally {
              button.loading = false;
            }

            this.resolve(response.data.message);
          };

          if (
            !action.requireElevatedSession ||
            (await elevatedSessionManager.require())
          ) {
            await submit();
          }
        });
      }
    }

    Craft.initUiElements(this.$container);
  }

  async resolve(message: any = null): Promise<void> {
    this.$container.addClass('edm-blocker--resolved');
    for (const button of this.buttons) {
      button.disabled = true;
    }
    const $messageContainer = $('<div/>', {
      class: 'edm-blocker-resolved-message',
    }).appendTo(this.$container);
    $('<craft-icon/>', {name: 'check'}).appendTo($messageContainer);
    $('<p/>', {text: message ?? t('Resolved')}).appendTo($messageContainer);
    this.manager.resolveBlocker(this);
  }
}

// Legacy static exposure (`new Craft.ElementDeletionManager.Blocker(...)`).
Object.assign(ElementDeletionManager, {Blocker});
