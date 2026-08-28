import {Base, Modal} from '@craftcms/garnish';

// jQuery-built modal/blocker markup + Craft.ui/Craft.cp seams; the modal is the
// modern @craftcms/garnish Modal (composition).
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
  modal: any = null;
  $blockersContainer: any = null;
  $submitBtn: any = null;
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
    const {data} = await Craft.sendActionRequest(
      'POST',
      'delete-elements/deletion-blockers',
      {
        data: this.getParams(),
      }
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

    await Craft.appendHeadHtml(data.headHtml);
    await Craft.appendBodyHtml(data.bodyHtml);
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

    if (!this.blockers.length) {
      this.$submitBtn.removeClass('disabled');
    }
  }

  async confirmAndDelete(totalElements: any): Promise<void> {
    const message = this.getConfirmationMessage(totalElements);
    if (!confirm(message)) {
      this.settings.onCancel();
      return;
    }

    this.$submitBtn?.addClass('loading');
    let data: any = null;
    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'delete-elements/delete',
        {
          data: this.getParams(),
        }
      );
      data = response.data;
    } catch {
      return;
    } finally {
      this.$submitBtn?.removeClass('loading');
    }

    this.succeeded = true;
    this.settings.onSuccess();
    this.modal?.hide();

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

    const elementTypeName =
      totalElements === 1
        ? Craft.elementTypeNames[this.elementType][2]
        : Craft.elementTypeNames[this.elementType][3];

    if (this.settings.hardDelete) {
      return Craft.t(
        'app',
        'Are you sure you want to permanently delete {numElements, plural, =1{this} other{these}} {type}?',
        {type: elementTypeName, numElements: totalElements}
      );
    }

    return Craft.t(
      'app',
      'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type}?',
      {type: elementTypeName, numElements: totalElements}
    );
  }

  createModal(data: any): void {
    const $container = $('<div/>', {
      class: 'modal element-deletion-modal',
    });

    const $body = $('<div/>', {class: 'body'}).appendTo($container);

    $('<h1/>', {
      class: 'cp:flex flex-inline cp:items-center gap-xs',
      html: Craft.t(
        'app',
        'Before deleting {label}, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
        {numBlockers: data.blockers.length, label: data.elementPreview}
      ),
    }).appendTo($body);

    this.$blockersContainer = $('<div/>', {class: 'edm-blockers'}).appendTo(
      $body
    );

    const $footer = $('<div/>', {
      class: 'footer cp:flex flex-justify',
    }).appendTo($container);
    $('<div/>', {class: 'cp:flex-grow'}).appendTo($footer);
    const $closeBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Close'),
    }).appendTo($footer);
    this.$submitBtn = Craft.ui
      .createSubmitButton({
        class: 'disabled',
        label: Craft.t('app', 'Delete {type}', {
          type:
            this.elementIds.length === 1
              ? Craft.elementTypeNames[this.elementType][2]
              : Craft.elementTypeNames[this.elementType][3],
        }),
        spinner: true,
      })
      .appendTo($footer);

    this.modal = new Modal($container[0], {
      resizable: true,
      onFadeIn: () => {
        Craft.cp.elementThumbLoader.load($container);
      },
      onHide: () => {
        if (!this.succeeded) {
          this.settings.onCancel();
        }
      },
      onFadeOut: () => {
        this.destroy();
      },
    });

    $closeBtn.on('activate', () => {
      this.modal.hide();
    });

    this.$submitBtn.on('activate', async () => {
      await this.confirmAndDelete(data.totalElements);
    });
  }

  override destroy(): void {
    this.modal?.destroy();
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
  buttons: any = null;

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

    const $headingContainer = $('<div/>', {
      class: 'cp:flex cp:flex-nowrap cp:gap-0 mb-s',
    }).appendTo(this.$container);

    if (this.data.details) {
      $('<button/>', {
        type: 'button',
        class: 'fieldtoggle cp:my-0',
        'data-target': `${this.id}-details`,
        title: `${Craft.t('app', 'Expand')} “${this.data.summary}”`,
        'aria-label': `${Craft.t('app', 'Expand')} “${this.data.summary}”`,
        html: '&nbsp;',
      }).appendTo($headingContainer);
    }

    $('<h2/>', {
      class: 'h3 cp:mt-0',
      text: this.data.summary,
    }).appendTo($headingContainer);

    if (this.data.details) {
      $('<div/>', {
        id: `${this.id}-details`,
        class: 'edm-details hidden mb-m',
        html: this.data.details,
      }).appendTo(this.$container);
    }

    if (this.data.actions) {
      const $actions = $('<div/>', {
        class: 'cp:flex cp:flex-nowrap',
      }).appendTo(this.$container);

      for (const action of this.data.actions) {
        const $button = Craft.ui.createButton({
          id: action.id,
          class: action.class,
          label: action.label,
          icon: action.icon,
          spinner: true,
        });
        this.buttons.push($button);

        $button.addClass('hairline-dark');
        if (action.destructive) {
          $button.addClass('error');
        }

        if (action.attributes) {
          $button.attr(action.attributes);
        }

        $button.appendTo($actions);

        $button.on('activate', async () => {
          if (action.callback) {
            let message;
            try {
              // The action callback is a server-provided code string that
              // resolves/rejects using these locals (a legacy contract).
              /* eslint-disable @typescript-eslint/no-unused-vars, @typescript-eslint/no-this-alias */
              message = await new Promise((resolve, reject) => {
                const blocker = this;
                const {
                  elementType,
                  elementIds,
                  siteId,
                  ownerId,
                  withDescendants,
                  hardDelete,
                } = this.manager.getParams();
                eval(action.callback);
              });
              /* eslint-enable @typescript-eslint/no-unused-vars, @typescript-eslint/no-this-alias */
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
            $button.addClass('loading');
            let response;
            try {
              response = await Craft.sendActionRequest('POST', action.action, {
                data: {
                  elementIds: this.manager.elementIds,
                  ...action.params,
                },
              });
            } finally {
              $button.removeClass('loading');
            }

            this.resolve(response.data.message);
          };

          if (action.requireElevatedSession) {
            Craft.elevatedSessionManager.requireElevatedSession(async () => {
              await submit();
            });
          } else {
            await submit();
          }
        });
      }
    }

    Craft.initUiElements(this.$container);
  }

  async resolve(message: any = null): Promise<void> {
    this.$container.addClass('edm-blocker--resolved');
    for (const $button of this.buttons) {
      $button.addClass('disabled');
    }
    const $messageContainer = $('<div/>', {
      class:
        'edm-blocker-resolved-message cp:flex cp:flex-nowrap cp:items-center',
    }).appendTo(this.$container);
    $('<div/>', {class: 'cp-icon teal'})
      .append(await Craft.ui.icon('check'))
      .appendTo($messageContainer);
    $('<p/>', {
      class: 'cp:mt-0',
      text: message ?? Craft.t('app', 'Resolved'),
    }).appendTo($messageContainer);
    this.manager.resolveBlocker(this);
  }
}

// Legacy static exposure (`new Craft.ElementDeletionManager.Blocker(...)`).
Object.assign(ElementDeletionManager, {Blocker});
