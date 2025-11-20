(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Nested content editor
   */
  Craft.ContentBlockEditor = Garnish.Base.extend(
    {
      $container: null,
      formObserver: null,
      visibleLayoutElements: null,
      cancelToken: null,
      ignoreFailedRequest: false,

      init: function ($container, settings = {}) {
        this.$container = $container;
        this.setSettings(settings, Craft.ContentBlockEditor.defaults);

        this.formObserver = new Craft.FormObserver(this.$container, (data) => {
          this.updateFieldLayout(data);
        });
      },

      async updateFieldLayout(data) {
        const elementEditor = this.$container
          .closest('form')
          .data('elementEditor');

        // Ignore if we're already submitting the main form
        if (elementEditor?.submittingForm) {
          throw 'Form already being submitted.';
        }

        if (this.cancelToken) {
          this.ignoreFailedRequest = true;
          this.cancelToken.cancel();
        }

        const param = (n) =>
          Craft.namespaceInputName(n, this.settings.baseInputName);
        const extraData = {
          [param('visibleLayoutElements')]: this.settings.visibleLayoutElements,
          [param('elementType')]: 'craft\\elements\\ContentBlock',
          [param('ownerId')]:
            elementEditor?.getDraftElementId(this.settings.ownerId) ??
            this.settings.ownerId,
          [param('fieldId')]: this.settings.fieldId,
          [param('siteId')]: this.settings.siteId,
          [param('elementId')]:
            elementEditor?.getDraftElementId(this.settings.elementId) ??
            this.settings.elementId,
        };

        data += `&${$.param(extraData)}`;

        this.cancelToken = axios.CancelToken.source();

        let response;

        try {
          response = await Craft.sendActionRequest(
            'POST',
            'elements/update-field-layout',
            {
              cancelToken: this.cancelToken.token,
              headers: {
                'content-type': 'application/x-www-form-urlencoded',
                'X-Craft-Namespace': this.settings.baseInputName,
              },
              data,
            }
          );
        } catch (e) {
          if (!this.ignoreFailedRequest) {
            throw e;
          }
          this.ignoreFailedRequest = false;
          return;
        } finally {
          this.cancelToken = null;
        }

        // Update the visible elements
        const visibleLayoutElements = {};
        let changedElements = false;

        const $tabContainer = this.$container.find('.flex-fields:first');

        for (const tabInfo of response.data.missingElements) {
          for (const elementInfo of tabInfo.elements) {
            if (elementInfo.html !== false) {
              if (!visibleLayoutElements[tabInfo.uid]) {
                visibleLayoutElements[tabInfo.uid] = [];
              }
              visibleLayoutElements[tabInfo.uid].push(elementInfo.uid);

              if (typeof elementInfo.html === 'string') {
                const $oldElement = $tabContainer.children(
                  `[data-layout-element="${elementInfo.uid}"]`
                );
                const $newElement = $(elementInfo.html);
                if ($oldElement.length) {
                  $oldElement.replaceWith($newElement);
                } else {
                  $newElement.appendTo($tabContainer);
                }
                Craft.initUiElements($newElement);
                changedElements = true;
              }
            } else {
              const $oldElement = $tabContainer.children(
                `[data-layout-element="${elementInfo.uid}"]`
              );
              if (
                !$oldElement.length ||
                !Garnish.hasAttr($oldElement, 'data-layout-element-placeholder')
              ) {
                const $placeholder = $('<div/>', {
                  class: 'hidden',
                  'data-layout-element': elementInfo.uid,
                  'data-layout-element-placeholder': '',
                });

                if ($oldElement.length) {
                  $oldElement.replaceWith($placeholder);
                } else {
                  $placeholder.appendTo($tabContainer);
                }

                changedElements = true;
              }
            }
          }
        }

        this.settings.visibleLayoutElements = visibleLayoutElements;

        await Craft.appendHeadHtml(response.data.headHtml);
        await Craft.appendBodyHtml(response.data.bodyHtml);

        // re-grab dismissible tips, re-attach listener, hide on re-load
        elementEditor?.handleDismissibleTips();
      },
    },
    {
      defaults: {
        baseInputName: null,
        ownerElementType: null,
        ownerId: null,
        fieldId: null,
        siteId: null,
        elementId: null,
        visibleLayoutElements: {},
      },
    }
  );
})(jQuery);
