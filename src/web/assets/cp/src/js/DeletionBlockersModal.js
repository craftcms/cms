/** global: Craft */
/** global: Garnish */
/** global: $ */

/**
 * Deletion Blockers Modal
 *
 * Shows the deletion blockers for anything registered with `craft\services\DeletionBlockers`, and resolves once
 * each one is addressed.
 *
 * `Craft.ElementDeletionManager` extends it for element deletions.
 *
 * @since 5.13.0
 */
Craft.DeletionBlockersModal = Garnish.Base.extend(
  {
    type: null,
    id: null,
    modal: null,
    $blockersContainer: null,
    $submitBtn: null,
    blockers: null,
    confirmed: false,
    _resolve: null,

    /**
     * @param {string} type The subject type, such as `field` or `volume`
     * @param {number|string} id The ID of the thing to be deleted (or its handle or UID, depending on the type)
     * @param {Object} settings
     */
    init: function (type, id, settings) {
      this.type = type;
      this.id = id;
      this.setSettings(settings, Craft.DeletionBlockersModal.defaults);
      this.blockers = [];
    },

    /**
     * Resolves to `true` once the blockers are addressed (or if there are none), or `false` if the modal is closed.
     *
     * @returns {Promise<boolean>}
     */
    run: async function () {
      const data = await this.loadBlockers();

      if (!data.blockers.length) {
        return true;
      }

      return new Promise(async (resolve) => {
        this._resolve = resolve;
        await this.showModal(data);
      });
    },

    loadBlockers: async function () {
      const {data} = await Craft.sendActionRequest(
        'POST',
        this.getBlockersAction(),
        {
          data: this.getParams(),
        }
      );
      return data;
    },

    getBlockersAction: function () {
      return 'deletion-blockers/get';
    },

    // Blocker actions and callbacks can read these
    getParams: function () {
      return {type: this.type, id: this.id};
    },

    showModal: async function (data) {
      this.createModal(data);

      for (const blocker of data.blockers) {
        this.blockers.push(
          new Craft.DeletionBlockersModal.Blocker(this, blocker)
        );
      }

      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
    },

    getHeadingHtml: function (data) {
      return Craft.escapeHtml(
        Craft.t(
          'app',
          'Before deleting “{name}”, please address the following {numBlockers, plural, =1{issue} other{issues}}:',
          {
            numBlockers: data.blockers.length,
            name: data.label,
          }
        )
      );
    },

    getSubmitLabel: function (data) {
      return Craft.t('app', 'Delete');
    },

    onSubmit: function (data) {
      this.confirmed = true;
      this.modal.hide();
    },

    onHide: function () {
      this._resolve?.(this.confirmed);
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

    createModal: function (data) {
      const $container = $('<div/>', {
        class: 'modal element-deletion-modal',
      });

      const $body = $('<div/>', {
        class: 'body',
      }).appendTo($container);

      $('<h1/>', {
        class: 'flex flex-inline items-center gap-xs',
        html: this.getHeadingHtml(data),
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
          label: this.getSubmitLabel(data),
          spinner: true,
        })
        .appendTo($footer);

      this.modal = new Garnish.Modal($container, {
        resizable: true,
        onFadeIn: () => {
          Craft.cp.elementThumbLoader.load($container);
        },
        onHide: () => {
          this.onHide();
        },
        onFadeOut: () => {
          this.destroy();
        },
      });

      $closeBtn.on('activate', () => {
        this.modal.hide();
      });

      this.$submitBtn.on('activate', async () => {
        if (this.$submitBtn.hasClass('disabled')) {
          return;
        }
        await this.onSubmit(data);
      });
    },

    destroy: function () {
      this.modal?.destroy();
      delete this.modal;

      for (const blocker of this.blockers || []) {
        blocker.destroy();
      }

      delete this.blockers;
    },
  },
  {
    defaults: {},

    /**
     * Shows the blockers for something to be deleted, for an admin table’s `beforeDelete` setting.
     *
     * Resolves to `{confirmed: true}` if the user addressed the blockers and confirmed the deletion, `true` if there
     * were no blockers, or `false` if the user closed the modal.
     *
     * @param {string} type
     * @param {number|string} id
     * @returns {Promise<boolean|Object>}
     */
    confirm: async function (type, id) {
      const modal = new Craft.DeletionBlockersModal(type, id);
      if (!(await modal.run())) {
        return false;
      }
      return modal.confirmed ? {confirmed: true} : true;
    },
  }
);

Craft.DeletionBlockersModal.Blocker = Garnish.Base.extend({
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
        class: `fieldtoggle my-0${this.data.expanded ? ' expanded' : ''}`,
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
        class: `edm-details${this.data.expanded ? '' : ' hidden'} mb-m`,
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
                  type,
                  id,
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
                  ...this.manager.getParams(),
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
