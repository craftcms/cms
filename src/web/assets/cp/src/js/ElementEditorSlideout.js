/** global: Craft */
/** global: Garnish */
/**
 * Element Editor Slideout
 */
Craft.ElementEditorSlideout = Craft.CpScreenSlideout.extend(
  {
    $element: null,

    init: function (element, settings) {
      this.$element = $(element);

      settings = Object.assign(
        {},
        Craft.ElementEditorSlideout.defaults,
        settings,
        {
          showHeader: true,
        }
      );
      this.base('elements/edit', settings);

      this.on('load', () => {
        const editor = this.$container.data('elementEditor');
        if (editor) {
          editor.on('beforeSubmit', () => {
            Object.keys(this.settings.saveParams).forEach((name) => {
              $('<input/>', {
                class: 'hidden',
                name: editor.namespaceInputName(name),
                value: this.settings.saveParams[name],
              }).appendTo(this.$container);
            });
          });
        }
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
      } else if (this.$element && this.$element.data('id')) {
        params.elementId = this.$element.data('id');
      }

      if (this.settings.draftId) {
        params.draftId = this.settings.draftId;
      } else if (this.$element && this.$element.data('draft-id')) {
        params.draftId = this.$element.data('draft-id');
      } else if (this.settings.revisionId) {
        params.revisionId = this.settings.revisionId;
      } else if (this.$element && this.$element.data('revision-id')) {
        params.revisionId = this.$element.data('revision-id');
      }

      if (this.settings.siteId) {
        params.siteId = this.settings.siteId;
      } else if (this.$element && this.$element.data('site-id')) {
        params.siteId = this.$element.data('site-id');
      }

      if (this.settings.prevalidate) {
        params.prevalidate = 1;
      }

      return params;
    },

    handleSubmit: function (ev) {
      this.$container.data('elementEditor').handleSubmit(ev);
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
    },
  }
);
