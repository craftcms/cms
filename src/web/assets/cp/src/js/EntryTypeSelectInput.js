/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Base component select input
 */
Craft.EntryTypeSelectInput = Craft.ComponentSelectInput.extend(
  {
    init: function (settings = {}) {
      this.base(
        Object.assign({}, Craft.EntryTypeSelectInput.defaults, settings)
      );
    },

    addComponentInternal: function ($component) {
      if (this.settings.allowOverrides) {
        const disclosureMenu = this.getDisclosureMenu($component);
        const $editBtn = disclosureMenu.$container.find('[data-edit-action]');
        if ($editBtn.length) {
          const $ul = $editBtn.closest('ul');
          $editBtn.closest('li').remove();
          if ($ul.find('li').length === 0) {
            $ul.remove();
          }
        }
        Craft.addActionsToChip(
          $component,
          [
            {
              icon: 'gear',
              label: Craft.t('app', 'Settings'),
              callback: () => {
                this.createSettings($component);
              },
              attributes: {
                'data-edit-action': true,
              },
            },
          ],
          true
        );
      }

      this.base($component);
    },

    createSettings: async function ($component) {
      let data;
      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'entry-types/render-override-settings',
          {
            data: JSON.parse($component.find('input').val()),
          }
        );
        data = response.data;
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
        throw e;
      }

      const settingsNamespace = data.namespace;
      const slideout = await this.createSlideout(data);

      slideout.$container.on('submit', (ev) => {
        ev.preventDefault();
        this.applySettings($component, slideout, settingsNamespace);
      });
      slideout.on('close', () => {
        slideout.destroy();
      });

      this.trigger('createSettings');
    },

    async createSlideout(data) {
      const $body = $('<div/>', {class: 'entry-type-override-settings-body'});
      $('<div/>', {class: 'fields', html: data.settingsHtml}).appendTo($body);
      const $footer = $('<div/>', {
        class: 'entry-type-override-settings-footer',
      });
      $('<div/>', {class: 'flex-grow'}).appendTo($footer);
      const $cancelBtn = Craft.ui
        .createButton({
          label: Craft.t('app', 'Close'),
          spinner: true,
        })
        .appendTo($footer);
      Craft.ui
        .createSubmitButton({
          class: 'secondary',
          label: Craft.t('app', 'Apply'),
          spinner: true,
        })
        .appendTo($footer);
      const $contents = $body.add($footer);

      const slideout = new Craft.Slideout($contents, {
        containerElement: 'form',
        containerAttributes: {
          action: '',
          method: 'post',
          novalidate: '',
          class: 'entry-type-override-settings',
        },
      });
      slideout.on('open', () => {
        // Hold off a sec until it's positioned...
        Garnish.requestAnimationFrame(() => {
          // Focus on the first text input
          slideout.$container.find('.text:first').focus();
        });
      });

      $cancelBtn.on('click', () => {
        slideout.close();
      });

      if (data.headHtml) {
        await Craft.appendHeadHtml(data.headHtml);
      }
      if (data.bodyHtml) {
        await Craft.appendBodyHtml(data.bodyHtml);
      }

      Craft.initUiElements(slideout.$container);

      return slideout;
    },

    async applySettings($component, slideout, settingsNamespace) {
      // update the UI
      let $submitBtn = slideout.$container
        .find('button[type=submit]')
        .addClass('loading');

      // clear errors
      slideout.$container.find('.field.has-errors').each((i, field) => {
        const $field = $(field);
        $field.removeClass('has-errors');
        $field.children('.input').removeClass('errors prevalidate');
        $field.children('ul.errors').remove();
      });

      try {
        let data;

        try {
          const response = await Craft.sendActionRequest(
            'POST',
            'entry-types/apply-override-settings',
            {
              data: {
                id: $component.data('id'),
                settingsNamespace,
                settings: slideout.$container.serialize(),
              },
            }
          );
          data = response.data;
        } catch (e) {
          let errors = e?.response?.data?.errors;
          if (errors) {
            debugger;
            Object.entries(errors).forEach(([name, fieldErrors]) => {
              const $field = slideout.$container.find(
                `[data-error-key="${name}"]`
              );
              if ($field.length) {
                Craft.ui.addErrorsToField($field, fieldErrors);
              }
            });
          }

          Craft.cp.displayError(e?.response?.data?.message);
          throw e;
        }

        const $newContainer = $(data.chipHtml);
        $component
          .find('.chip-label')
          .replaceWith($newContainer.find('.chip-label'));
        $component.find('input').val(JSON.stringify(data.config));

        slideout.close();
        slideout.destroy();
      } finally {
        $submitBtn.removeClass('loading');
      }
    },

    renderSettings: function (id) {
      return Object.assign(this.base(), {
        inputValue: this.settings.allowOverrides ? JSON.stringify({id}) : null,
      });
    },
  },
  {
    defaults: {
      allowOverrides: false,
    },
  }
);
