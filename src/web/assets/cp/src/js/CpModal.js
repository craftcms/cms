/** global: Craft */
/** global: Garnish */
import $ from 'jquery';

/**
 * CP Screen Slideout
 */
Craft.CpModal = Garnish.Modal.extend(
  {
    action: null,

    namespace: null,

    showingLoadSpinner: false,

    $loadSpinner: null,

    $container: null,

    $body: null,
    $content: null,

    $sidebar: null,

    $footer: null,

    $cancelBtn: null,
    $saveBtn: null,

    showingSidebar: false,

    cancelToken: null,
    ignoreFailedRequest: false,
    fieldsWithErrors: null,

    init: function (action, settings) {
      this.action = action;
      this.setSettings(settings, Craft.CpModal.defaults);

      this.fieldsWithErrors = [];

      // Body
      this.$body = $('<div/>', {class: 'cpmodal-body'});

      // Content
      this.$content = $('<div/>', {class: 'cpmodal-content'}).appendTo(
        this.$body
      );

      // Footer
      this.$footer = $('<div/>', {class: 'cpmodal-footer hidden'});

      $('<div/>', {class: 'flex-grow'}).appendTo(this.$footer);

      const $btnContainer = $('<div/>', {class: 'flex flex-nowrap'}).appendTo(
        this.$footer
      );

      this.$loadSpinner = $('<div/>', {
        class: 'spinner',
        title: Craft.t('app', 'Loading'),
        'aria-label': Craft.t('app', 'Loading'),
      }).prependTo($btnContainer);

      this.$cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Cancel'),
      }).appendTo($btnContainer);

      if (this.settings.showSubmitButton) {
        this.$saveBtn = Craft.ui
          .createSubmitButton({
            label: Craft.t('app', 'Save'),
            spinner: true,
          })
          .appendTo($btnContainer);
      }

      this.$container = $(
        `<${this.settings.containerElement}/>`,
        this.settings.containerAttributes
      );

      let $contents = this.$body.add(this.$footer);

      this.$container.append($contents);

      this.base(this.$container, {
        autoShow: false,
      });

      this.$container.data('cpModal', this);

      // Register shortcuts & events
      Garnish.uiLayerManager.registerShortcut(
        {
          keyCode: Garnish.S_KEY,
          ctrl: true,
        },
        (ev) => {
          this.handleSubmit(ev);
        }
      );
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$cancelBtn, 'click', () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$shade, 'click', () => {
        this.closeMeMaybe();
      });
      this.addListener(this.$container, 'click', (ev) => {
        const $target = $(event.target);

        if (
          this.showingSidebar &&
          !$target.closest(this.$sidebarBtn).length &&
          !$target.closest(this.$sidebar).length
        ) {
          this.hideSidebar();
        }
      });
      this.addListener(this.$container, 'submit', 'handleSubmit');

      this.load();
    },

    /**
     * @param {Object} [data={}]
     * @param {boolean} [refreshInitialData=true]
     * @returns {Promise}
     */
    load: function (data, refreshInitialData) {
      return new Promise((resolve, reject) => {
        this.trigger('beforeLoad');
        this.showLoadSpinner();

        if (this.cancelToken) {
          this.ignoreFailedRequest = true;
          this.cancelToken.cancel();
        }

        this.cancelToken = axios.CancelToken.source();

        Craft.sendActionRequest(
          'GET',
          this.action,
          $.extend(
            {
              params: Object.assign({}, this.getParams(), this.settings.params),
              cancelToken: this.cancelToken.token,
              headers: {
                'X-Craft-Container-Id': this.$container.attr('id'),
              },
            },
            this.settings.requestOptions
          )
        )
          .then((response) => {
            this.update(response.data)
              .then(() => {
                if (refreshInitialData !== false) {
                  this.$container.data('delta-names', response.data.deltaNames);
                  this.$container.data(
                    'initial-delta-values',
                    response.data.initialDeltaValues
                  );
                  this.$container.data(
                    'initialSerializedValue',
                    this.$container.serialize()
                  );
                }
                resolve();
              })
              .catch((e) => {
                reject(e);
              });
          })
          .catch((e) => {
            if (!this.ignoreFailedRequest) {
              Craft.cp.displayError();
              reject(e);
            }
            this.ignoreFailedRequest = false;
          })
          .finally(() => {
            this.hideLoadSpinner();
            this.show();
            this.cancelToken = null;
          });
      });
    },

    getParams: function () {
      return {};
    },

    showLoadSpinner: function () {
      this.$loadSpinner.removeClass('hidden');
      this.showingLoadSpinner = true;
    },

    hideLoadSpinner: function () {
      this.$loadSpinner.addClass('hidden');
      this.showingLoadSpinner = false;
    },

    /**
     * @param {Object} data
     * @returns {Promise}
     */
    update: function (data) {
      return new Promise((resolve) => {
        this.namespace = data.namespace;

        if (data.bodyClass) {
          this.$body.addClass(data.bodyClass);
        }

        this.$content.html(data.content);

        if (data.submitButtonLabel) {
          this.$saveBtn.text(data.submitButtonLabel);
        }

        if (data.formAttributes) {
          Craft.setElementAttributes(this.$container, data.formAttributes);
        }

        this.$footer.removeClass('hidden');

        Garnish.requestAnimationFrame(() => {
          Craft.appendHeadHtml(data.headHtml);
          Craft.appendBodyHtml(data.bodyHtml);

          Craft.initUiElements(this.$content);
          Craft.cp.elementThumbLoader.load($(this.$content));

          if (!Garnish.isMobileBrowser()) {
            Craft.setFocusWithin(this.$content);
          }

          resolve();
          this.trigger('load');
        });
      });
    },

    showSubmitSpinner: function () {
      this.$saveBtn.addClass('loading');
    },

    hideSubmitSpinner: function () {
      this.$saveBtn.removeClass('loading');
    },

    handleSubmit: function (ev) {
      ev.preventDefault();
      this.submit();
    },

    submit: function () {
      this.showSubmitSpinner();
      const data = Craft.findDeltaData(
        this.$container.data('initialSerializedValue'),
        this.$container.serialize(),
        null,
        this.$container.data('initial-delta-values')
      );

      Craft.sendActionRequest('POST', null, {
        data,
        headers: {
          'X-Craft-Namespace': this.namespace,
        },
      })
        .then((response) => {
          this.handleSubmitResponse(response);
        })
        .catch((error) => {
          this.handleSubmitError(error);
        })
        .finally(() => {
          this.hideSubmitSpinner();
        });
    },

    handleSubmitResponse: function (response) {
      this.clearErrors();
      const data = response.data || {};
      if (data.message) {
        Craft.cp.displaySuccess(data.message, data.notificationSettings);
      }
      if (data.modelClass && data.modelId) {
        Craft.refreshComponentInstances(data.modelClass, data.modelId);
      }
      this.trigger('submit', {
        response: response,
        data: (data.modelName && data[data.modelName]) || {},
      });
      if (this.settings.closeOnSubmit) {
        this.close();
      }
    },

    handleSubmitError: function (error) {
      if (
        !error.isAxiosError ||
        !error.response ||
        !error.response.status === 400
      ) {
        Craft.cp.displayError();
        throw error;
      }

      const data = error.response.data || {};
      Craft.cp.displayError(data.message);
      if (data.errors) {
        this.showErrors(data.errors);
      }
    },

    /**
     * @param {string[]} errors
     */
    showErrors: function (errors) {
      this.clearErrors();

      Object.entries(errors).forEach(([name, fieldErrors]) => {
        const $field = this.$container.find(`[data-attribute="${name}"]`);
        if ($field) {
          Craft.ui.addErrorsToField($field, fieldErrors);
          this.fieldsWithErrors.push($field);
        }
      });
      this.updateSizeAndPosition();
    },

    clearErrors: function () {
      this.fieldsWithErrors.forEach(($field) => {
        Craft.ui.clearErrorsFromField($field);
      });
    },

    isDirty: function () {
      const initialValue = this.$container.data('initialSerializedValue');
      if (typeof initialValue === 'undefined') {
        return false;
      }

      const serializer =
        this.$container.data('serializer') ||
        (() => this.$container.serialize());
      return initialValue !== serializer();
    },

    closeMeMaybe: function () {
      if (!this.visible) {
        return;
      }

      if (
        !this.isDirty() ||
        confirm(
          Craft.t(
            'app',
            'Are you sure you want to close this screen? Any changes will be lost.'
          )
        )
      ) {
        this.close();
      }
    },

    close: function () {
      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }
      this.trigger('close');
      this.destroy();
    },
  },
  {
    defaults: {
      params: {},
      containerElement: 'form',
      containerAttributes: {
        id: `cp-modal-${Math.floor(Math.random() * 100000000)}`,
        action: '',
        method: 'post',
        novalidate: '',
        class: 'cpmodal modal fitted',
      },
      requestOptions: {},
      closeOnSubmit: true,
      showSubmitButton: true,
    },
  }
);
