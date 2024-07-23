/** global: Craft */
/** global: Garnish */
/**
 * Element Editor Slideout
 */
Craft.ElementEditorSlideout = Craft.CpScreenSlideout.extend(
  {
    $element: null,
    elementEditor: null,

    init: function (element, settings) {
      this.$element = $(element);

      settings = Object.assign(
        {},
        Craft.ElementEditorSlideout.defaults,
        settings,
        {
          showHeader: true,
          prevalidate: this.$element.parents('.prevalidate').length > 0,
        }
      );
      this.base('elements/edit', settings);

      this.on('load', () => {
        this.elementEditor = new Craft.ElementEditor(
          this.$container,
          Object.assign(
            {
              namespace: this.namespace,
              $contentContainer: this.$content,
              $sidebar: this.$sidebar,
              $actionBtn: this.$actionBtn,
              $spinnerContainer: this.$toolbar,
              updateTabs: (tabs) => {
                this.updateTabs(tabs);
              },
              getTabManager: () => this.tabManager,
              handleSubmitResponse: (response) => {
                this.handleSubmitResponse(response);
              },
              handleSubmitError: (error) => {
                this.handleSubmitError(error);
              },
            },
            this.$container.data('elementEditorSettings')
          )
        );
        this.elementEditor.on('beforeSubmit', () => {
          Object.keys(this.settings.saveParams).forEach((name) => {
            $('<input/>', {
              class: 'hidden',
              name: this.elementEditor.namespaceInputName(name),
              value: this.settings.saveParams[name],
            }).appendTo(this.$container);
          });
          this.showSubmitSpinner();
        });
        this.elementEditor.on('afterSubmit', () => {
          this.hideSubmitSpinner();
        });
      });

      this.on('submit', (ev) => {
        if (Craft.broadcaster) {
          Craft.broadcaster.postMessage({
            event: 'saveElement',
            id: ev.response.data.element.id,
          });
        }

        // Pass the response data off to onSaveElement() for backwards compatibility
        if (this.settings.onSaveElement) {
          const data = Object.assign(
            {},
            ev.response.data,
            ev.response.data.element
          );
          delete data.element;
          delete data.modelName;
          delete data.message;
          this.settings.onSaveElement(data);
        }

        // Refresh Live Preview
        Craft.Preview.refresh();
      });
    },

    getParams: function () {
      const params = {};

      if (this.settings.elementType) {
        params.elementType = this.settings.elementType;
      }

      if (this.settings.elementId) {
        params.elementId = this.settings.elementId;
      } else if (this.$element?.data('id')) {
        params.elementId = this.$element.data('id');
      }

      if (this.settings.draftId) {
        params.draftId = this.settings.draftId;
      } else if (this.$element?.data('draft-id')) {
        params.draftId = this.$element.data('draft-id');
      } else if (this.settings.revisionId) {
        params.revisionId = this.settings.revisionId;
      } else if (this.$element?.data('revision-id')) {
        params.revisionId = this.$element.data('revision-id');
      }

      if (this.settings.siteId) {
        params.siteId = this.settings.siteId;
      } else if (this.$element?.data('site-id')) {
        params.siteId = this.$element.data('site-id');
      }

      if (this.settings.prevalidate) {
        params.prevalidate = 1;
      }

      return params;
    },

    handleSubmit: async function (ev) {
      if (ev.type !== 'submit' && this.elementEditor.settings.canCreateDrafts) {
        // first, we have to save the draft and then fully save;
        // otherwise we'll have tab error indicator issues;
        await this.elementEditor.saveDraft();
      }

      this.elementEditor.handleSubmit(ev);
    },

    handleSubmitError: function (e) {
      this.base(e);

      // update the `error` class in nested cards
      if (e?.response?.data?.invalidNestedElementIds) {
        const $cards = this.$content.find('.element.card').removeClass('error');
        $cards
          .find('craft-element-label > span[data-icon="triangle-exclamation"]')
          .remove();
        if (e.response.data.invalidNestedElementIds.length) {
          const $errorCards = $cards
            .filter(
              e.response.data.invalidNestedElementIds
                .map((id) => `[data-id=${id}]`)
                .join(',')
            )
            .addClass('error');
          for (let i = 0; i < $errorCards.length; i++) {
            const $label = $errorCards.eq(i).find('craft-element-label');
            $('<span/>', {
              'data-icon': 'triangle-exclamation',
              'aria-label': Craft.t('app', 'Error'),
              role: 'img',
            }).appendTo($label);
          }
        }
      }
    },

    destroy: function () {
      this.elementEditor.destroy();
      delete this.elementEditor;
      this.base();
    },
  },
  {
    defaults: {
      elementId: null,
      draftId: null,
      revisionId: null,
      elementType: null,
      siteId: null,
      prevalidate: false,
      saveParams: {},
      onSaveElement: null,
      validators: [],
      expandData: [],
    },
  }
);
