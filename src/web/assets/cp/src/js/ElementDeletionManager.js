/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Element Deletion Manager
 *
 * @since 5.10.0
 */
Craft.ElementDeletionManager = Garnish.Base.extend(
  {
    elementType: null,
    elementIds: null,
    hardDelete: false,
    modal: null,
    $blockersContainer: null,
    $submitBtn: null,
    blockers: null,
    succeeded: false,

    init: function (elementType, elementIds, settings) {
      this.elementType = elementType;
      this.elementIds = elementIds;
      this.setSettings(settings, Craft.ElementDeletionManager.defaults);
      this.blockers = [];
      this.run();
    },

    run: async function () {
      const {data} = await Craft.sendActionRequest(
        'POST',
        'delete-elements/deletion-blockers',
        {
          data: this.getParams(),
        }
      );

      this.settings.onLoadBlockers();

      if (!data.blockers.length) {
        await this.confirmAndDelete();
        return;
      }

      this.createModal(data);

      for (const blocker of data.blockers) {
        this.blockers.push(
          new Craft.ElementDeletionManager.Blocker(this, blocker)
        );
      }

      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
    },

    getParams: function () {
      return {
        elementType: this.elementType,
        elementIds: this.elementIds,
        siteId: this.settings.siteId,
        ownerId: this.settings.ownerId,
        withDescendants: this.settings.withDescendants,
        hardDelete: this.settings.hardDelete,
      };
    },

    resolveBlocker: function (blocker) {
      const i = this.blockers.indexOf(blocker);
      if (i !== -1) {
        this.blockers.splice(i, 1);
      }

      if (!this.blockers.length) {
        this.$submitBtn.removeClass('disabled');
      }
    },

    confirmAndDelete: async function () {
      const message = this.getConfirmationMessage();
      if (!confirm(message)) {
        this.settings.onCancel();
        return;
      }

      this.$submitBtn?.addClass('loading');
      try {
        await Craft.sendActionRequest('POST', 'delete-elements/delete', {
          data: this.getParams(),
        });
      } catch (e) {
        return;
      } finally {
        this.$submitBtn?.removeClass('loading');
      }

      this.succeeded = true;
      this.settings.onSuccess();
      this.modal?.hide();
    },

    getConfirmationMessage: function () {
      if (this.settings.confirmationMessage) {
        return this.settings.confirmationMessage;
      }

      const elementTypeName =
        this.elementIds.length === 1
          ? Craft.elementTypeNames[this.elementType][2]
          : Craft.elementTypeNames[this.elementType][3];

      if (this.settings.hardDelete) {
        return Craft.t(
          'app',
          'Are you sure you want to permanently delete {numElements, plural, =1{this} other{these}} {type}?',
          {
            type: elementTypeName,
            numElements: this.elementIds.length,
          }
        );
      }

      if (this.settings.withDescendants) {
        return Craft.t(
          'app',
          'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type} along with {numElements, plural, =1{its} other{their}} descendants?',
          {
            type: elementTypeName,
            numElements: this.elementIds.length,
          }
        );
      }

      return Craft.t(
        'app',
        'Are you sure you want to delete {numElements, plural, =1{this} other{these}} {type}?',
        {
          type: elementTypeName,
          numElements: this.elementIds.length,
        }
      );
    },

    createModal: function (data) {
      const $container = $('<div/>', {
        class: 'modal element-deletion-modal',
      });

      const $body = $('<div/>', {
        class: 'body',
      }).appendTo($container);

      $('<h1/>', {
        class: 'flex flex-inline items-center gap-xs',
        html: Craft.t(
          'app',
          'Before deleting {label}, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
          {
            numBlockers: data.blockers.length,
            label: data.elementPreview,
          }
        ),
      }).appendTo($body);

      this.$blockersContainer = $('<div/>', {
        class: 'edm-blockers',
      }).appendTo($body);

      const $footer = $('<div/>', {
        class: 'footer flex flex-justify',
      }).appendTo($container);
      $('<div/>', {
        class: 'flex-grow',
      }).appendTo($footer);
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

      this.modal = new Garnish.Modal($container, {
        resizable: true,
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
        await this.confirmAndDelete();
      });
    },

    destroy: function () {
      this.modal?.destroy();
      delete this.modal;

      for (const blocker of this.blockers) {
        blocker.destroy();
      }

      delete this.blockers;
    },
  },
  {
    defaults: {
      siteId: null,
      ownerId: null,
      withDescendants: false,
      hardDelete: false,
      confirmationMessage: null,
      onLoadBlockers: $.noop,
      onSuccess: $.noop,
      onCancel: $.noop,
    },
  }
);

Craft.ElementDeletionManager.Blocker = Garnish.Base.extend({
  manager: null,
  data: null,
  id: null,
  $container: null,
  buttons: null,

  init: function (manager, data) {
    this.manager = manager;
    this.data = data;
    this.buttons = [];

    this.id = `blocker-${Math.floor(Math.random() * 1000000)}`;
    this.$container = $('<div/>', {
      id: this.id,
      class: 'edm-blocker',
    }).appendTo(this.manager.$blockersContainer);

    const $headingContainer = $('<div/>', {
      class: 'flex flex-nowrap gap-0 mb-s',
    }).appendTo(this.$container);

    if (this.data.details) {
      $('<button/>', {
        type: 'button',
        class: 'fieldtoggle my-0',
        'data-target': `${this.id}-details`,
        title: `${Craft.t('app', 'Expand')} “${this.data.summary}”`,
        'aria-label': `${Craft.t('app', 'Expand')} “${this.data.summary}”`,
        html: '&nbsp;',
      }).appendTo($headingContainer);
    }

    $('<h2/>', {
      class: 'h3 mt-0',
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
        class: 'flex flex-nowrap',
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
            } catch (e) {
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
                  elementIds: this.elementIds,
                  ...(action.params || {}),
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
  },

  resolve: async function (message = null) {
    this.$container.addClass('edm-blocker--resolved');
    for (const $button of this.buttons) {
      $button.addClass('disabled');
    }
    const $messageContainer = $('<div/>', {
      class: 'edm-blocker-resolved-message flex flex-nowrap items-center',
    }).appendTo(this.$container);
    $('<div/>', {
      class: 'cp-icon teal',
    })
      .append(await Craft.ui.icon('check'))
      .appendTo($messageContainer);
    $('<p/>', {
      class: 'mt-0',
      text: message ?? Craft.t('app', 'Resolved'),
    }).appendTo($messageContainer);
    this.manager.resolveBlocker(this);
  },
});
