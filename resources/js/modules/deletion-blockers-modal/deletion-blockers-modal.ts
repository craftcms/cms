import {Base} from '@craftcms/garnish';
import type CraftButton from '@craftcms/ui/components/button/button';
import type CraftDialog from '@craftcms/ui/components/dialog/dialog';
import './deletion-blockers-modal.css';
import {t} from '@craftcms/ui/utilities/translate';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {
  appendBodyHtml,
  appendElementHtml,
  appendHeadHtml,
} from '@craftcms/ui/utilities/dom';
import {setElementAttributes} from '@/common/craft/attributes';
import {elevatedSessionManager} from '@/modules/auth/elevated-session/manager';
import {create} from '@craftcms/ui/utilities/create';
import {showError} from '@/modules/messages/messages';
import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';
import {initLegacyUi} from '@/common/craft/legacy-ui';

/**
 * What {@link DeletionBlockersModal.confirm} resolves to: `false` when the
 * user closed the modal or the blockers couldn't be loaded, `true` when there
 * were no blockers, or `{confirmed: true}` when the user addressed the
 * blockers and confirmed the deletion in the modal.
 */
export type DeletionBlockersResult = boolean | {confirmed: true};

/**
 * Shows the deletion blockers for a subject registered with
 * `CraftCms\Cms\DeletionBlocker\SubjectTypeRegistry`, such as a field or
 * volume, and resolves once the user addresses each one.
 *
 * {@link ElementDeletionManager} extends it for element deletions. Blocker
 * callbacks come from the server and call it, so `globals.ts` registers it on
 * `window.Craft`.
 */
export class DeletionBlockersModal extends Base {
  declare settings: any;

  type: string | null = null;
  id: number | string | null = null;
  modal: CraftDialog | null = null;
  heading: HTMLElement | null = null;
  blockersContainer: HTMLElement | null = null;
  submitButton: CraftButton | null = null;
  blockers: any = null;
  confirmed = false;

  #resolve: ((confirmed: boolean) => void) | null = null;

  constructor(type?: string, id?: number | string, settings?: any) {
    super();
    if (new.target === DeletionBlockersModal) {
      this.init(type!, id!, settings);
    }
  }

  init(type: string, id: number | string, settings?: any): void {
    this.type = type;
    this.id = id;
    this.setSettings(settings, {});
    this.blockers = [];
  }

  /**
   * Shows the blockers for a subject to be deleted, such as before an admin
   * table deletes a row.
   */
  static async confirm(
    type: string,
    id: number | string
  ): Promise<DeletionBlockersResult> {
    const modal = new DeletionBlockersModal(type, id);
    if (!(await modal.run())) {
      return false;
    }

    return modal.confirmed ? {confirmed: true} : true;
  }

  /**
   * Resolves to `true` if there are no blockers or the user addresses them and
   * confirms the deletion, or `false` if the user closes the modal or the
   * blockers fail to load.
   */
  async run(): Promise<boolean> {
    let data;
    try {
      data = await this.loadBlockers();
    } catch (e: any) {
      showError(e?.response?.data?.message);
      return false;
    }

    if (!data.blockers.length) {
      return true;
    }

    return new Promise((resolve) => {
      this.#resolve = resolve;
      void this.showModal(data);
    });
  }

  async loadBlockers(): Promise<any> {
    const {data} = await actionClient.post(
      this.getBlockersAction(),
      this.getParams()
    );

    return data;
  }

  getBlockersAction(): string {
    return 'deletion-blockers';
  }

  /** The deletion params, which blocker actions and callbacks can read. */
  getParams(): any {
    return {type: this.type, id: this.id};
  }

  async showModal(data: any): Promise<void> {
    this.createModal(data);
    await this.showBlockers(data);
  }

  async showBlockers(data: any): Promise<void> {
    for (const blocker of data.blockers) {
      this.blockers.push(new Blocker(this, blocker));
    }

    await appendHeadHtml(data.headHtml);
    await appendBodyHtml(data.bodyHtml);
  }

  /**
   * Reloads the blockers, such as after a blocker action changed what the
   * deletion affects. Blocker callbacks can call it with
   * `blocker.manager.refresh()`.
   */
  async refresh(): Promise<void> {
    let data;
    try {
      data = await this.loadBlockers();
    } catch (e: any) {
      showError(e?.response?.data?.message);
      return;
    }

    // The modal was closed while the blockers were loading
    if (!this.modal || !this.blockers) {
      return;
    }

    for (const blocker of this.blockers) {
      blocker.destroy();
    }
    this.blockers = [];
    this.blockersContainer?.replaceChildren();

    if (this.submitButton) {
      this.submitButton.disabled = data.blockers.length !== 0;
    }

    if (this.heading) {
      if (data.blockers.length) {
        this.renderHeading(this.heading, data);
      } else {
        this.heading.textContent = t(
          'Nothing is blocking the deletion anymore.'
        );
      }
      this.updateLabel();
      // The action that triggered the refresh is gone, so move focus to the
      // heading, which also announces the new state.
      this.heading.focus();
    }

    if (data.blockers.length) {
      await this.showBlockers(data);
    }
  }

  /** Names the dialog after its heading. */
  updateLabel(): void {
    if (this.modal && this.heading) {
      this.modal.label = (this.heading.textContent ?? '')
        .replace(/\s+/g, ' ')
        .trim();
    }
  }

  /**
   * Deletes another subject after addressing its own blockers, such as a
   * section that a site's blocker lists, and then reloads the blockers.
   */
  async deleteSubject(
    type: string,
    id: number | string,
    url: string,
    message: string
  ): Promise<void> {
    const result = await DeletionBlockersModal.confirm(type, id);
    if (!result || (result === true && !confirm(message))) {
      return;
    }

    try {
      await actionClient.delete(url);
    } catch (e: any) {
      showError(e?.response?.data?.message);
      return;
    }

    await this.refresh();
  }

  renderHeading(heading: HTMLElement, data: any): void {
    heading.textContent = t(
      'Before deleting “{name}”, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
      {numBlockers: data.blockers.length, name: data.label}
    );
  }

  getSubmitLabel(_data: any): string {
    return t('Delete');
  }

  async onSubmit(_data: any): Promise<void> {
    this.confirmed = true;
    this.hideModal();
  }

  onHide(): void {
    this.#resolve?.(this.confirmed);
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
    dialog.storageKey = 'ElementDeletionModal.size';

    this.heading = document.createElement('h1');
    this.heading.className = 'edm-heading';
    this.heading.tabIndex = -1;
    this.renderHeading(this.heading, data);
    dialog.append(this.heading);

    this.blockersContainer = document.createElement('div');
    this.blockersContainer.className = 'edm-blockers';
    dialog.append(this.blockersContainer);

    const footer = document.createElement('div');
    footer.slot = 'footer';
    footer.className = 'edm-footer';
    const closeButton = create('craft-button', {type: 'button'}, t('Close'));
    this.submitButton = create(
      'craft-button',
      {type: 'button', variant: 'danger'},
      this.getSubmitLabel(data)
    );
    this.submitButton.disabled = true;
    footer.append(closeButton, this.submitButton);
    dialog.append(footer);

    dialog.addEventListener('craft-after-show', () => {
      loadElementThumbs(dialog);
    });
    dialog.addEventListener('craft-hide', () => {
      this.onHide();
    });
    dialog.addEventListener('craft-after-hide', () => {
      this.destroy();
    });

    this.modal = dialog;
    this.updateLabel();
    document.body.append(dialog);
    dialog.open = true;

    closeButton.addEventListener('click', () => {
      this.hideModal();
    });

    this.submitButton.addEventListener('click', async () => {
      if (this.submitButton?.disabled) {
        return;
      }
      await this.onSubmit(data);
    });
  }

  hideModal(): void {
    if (this.modal) {
      this.modal.open = false;
    }
  }

  override destroy(): void {
    this.modal?.remove();
    this.modal = null;

    for (const blocker of this.blockers ?? []) {
      blocker.destroy();
    }

    this.blockers = null;
  }
}

/**
 * One blocker in the modal. It has a summary, details the user can expand,
 * and buttons that resolve it.
 */
export class Blocker extends Base {
  manager: any = null;
  data: any = null;
  id: any = null;
  container: HTMLElement | null = null;

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
    const container = document.createElement('div');
    container.id = this.id;
    container.className = 'edm-blocker';
    this.manager.blockersContainer.append(container);
    this.container = container;

    const heading = document.createElement('h2');
    heading.className = 'edm-blocker-heading';
    container.append(heading);

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
      heading.append(toggle);

      const details = document.createElement('div');
      details.id = `${this.id}-details`;
      details.className = 'edm-details';
      details.hidden = true;
      container.append(details);
      void appendElementHtml(this.data.details, details);

      const setExpanded = (expanded: boolean): void => {
        const rtl = getComputedStyle(toggle).direction === 'rtl';
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.icon = expanded
          ? 'chevron-down'
          : rtl
            ? 'chevron-left'
            : 'chevron-right';
        details.hidden = !expanded;
      };

      setExpanded(!!this.data.expanded);
      toggle.addEventListener('click', () => {
        setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
      });
    } else {
      heading.textContent = this.data.summary;
    }

    if (this.data.actions) {
      const actions = document.createElement('div');
      actions.className = 'edm-actions';
      container.append(actions);

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
        this.buttons.push(button);

        if (action.id) {
          button.id = action.id;
        }

        if (action.class) {
          button.classList.add(
            ...String(action.class).split(/\s+/).filter(Boolean)
          );
        }

        if (action.attributes) {
          setElementAttributes(button, action.attributes);
        }

        actions.append(button);

        button.addEventListener('click', async () => {
          if (action.callback) {
            let message;
            try {
              message = await new Promise((resolve, reject) => {
                const params = this.manager.getParams();
                // oxlint-disable-next-line @typescript-eslint/no-implied-eval -- the action callback is a server-provided code string that resolves/rejects using these named arguments (a legacy contract)
                const callback = new Function(
                  'type',
                  'id',
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
                  params.type,
                  params.id,
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
                ...this.manager.getParams(),
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

    initLegacyUi(container);
  }

  async resolve(message: any = null): Promise<void> {
    this.container?.classList.add('edm-blocker--resolved');
    for (const button of this.buttons) {
      button.disabled = true;
    }
    const messageContainer = document.createElement('div');
    messageContainer.className = 'edm-blocker-resolved-message';
    const icon = document.createElement('craft-icon');
    icon.setAttribute('name', 'check');
    const text = document.createElement('p');
    text.textContent = message ?? t('Resolved');
    messageContainer.append(icon, text);
    this.container?.append(messageContainer);
    this.manager.resolveBlocker(this);
  }
}

// Lets legacy code create blockers with `new Craft.DeletionBlockersModal.Blocker()`.
Object.assign(DeletionBlockersModal, {Blocker});
