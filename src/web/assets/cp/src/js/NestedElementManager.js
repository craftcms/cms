/** global: Craft */

/**
 * Nested element manager
 */
Craft.NestedElementManager = Garnish.Base.extend(
  {
    $container: null,
    elementType: null,
    $createBtn: null,

    // cards
    $elements: null,
    elementSort: null,

    // index
    elementIndex: null,

    /**
     * @type {Craft.ElementEditor|null}
     */
    elementEditor: null,

    init: function (container, elementType, settings) {
      this.$container = $(container);
      this.elementType = elementType;
      this.setSettings(settings, Craft.NestedElementManager.defaults);

      // Is this already a nested element manager?
      if (this.$container.data('nestedElementManager')) {
        console.warn(
          'Double-instantiating a nested element manager on an element'
        );
        this.$container.data('nestedElementManager').destroy();
      }

      this.$container.data('nestedElementManager', this);

      if (this.settings.mode === 'cards') {
        if (this.$container.is(':has(.elements)')) {
          this.initCards();
        }
      } else {
        this.initElementIndex();
      }

      if (this.settings.canCreate) {
        this.$createBtn = Craft.ui
          .createButton({
            label: this.settings.createButtonLabel,
            spinner: true,
          })
          .addClass('add icon disabled');

        if (this.settings.mode === 'cards') {
          const $btnContainer = $('<div/>').appendTo(this.$container);
          this.$createBtn.addClass('dashed').appendTo($btnContainer);
          this.updateCreateBtn();
        } else {
          this.$createBtn.appendTo(this.elementIndex.$toolbar);
        }

        if (Array.isArray(this.settings.createAttributes)) {
          const createMenuId = `menu-${Math.floor(Math.random() * 1000000)}`;
          const $menu = $('<div/>', {
            id: createMenuId,
            class: 'menu menu--disclosure',
          }).insertAfter(this.$createBtn);
          const $ul = $('<ul/>').appendTo($menu);
          for (let type of this.settings.createAttributes) {
            const $li = $('<li/>').appendTo($ul);
            let buttonHtml = '';
            if (type.icon) {
              const $icon = $(`<span class="icon">${type.icon}</span>`);
              if (type.color) {
                $icon.addClass(type.color);
              }
              buttonHtml += $icon.prop('outerHTML');
            }
            buttonHtml += `<span class="label">${type.label}</span>`;
            const $button = $('<button/>', {
              type: 'button',
              class: 'menu-item',
              html: buttonHtml,
            }).appendTo($li);
            this.addListener($button, 'activate', (ev) => {
              ev.preventDefault();
              this.$createBtn.data('disclosureMenu').hide();
              this.createElement(type.attributes);
            });
          }
          this.$createBtn
            .attr('aria-controls', createMenuId)
            .attr('data-disclosure-trigger', 'true')
            .addClass('menubtn')
            .disclosureMenu();
        } else {
          this.addListener(this.$createBtn, 'activate', (ev) => {
            ev.preventDefault();
            this.createElement(this.settings.createAttributes);
          });
        }
      }

      setTimeout(() => {
        this.elementEditor = this.$container
          .closest('form')
          .data('elementEditor');

        if (this.elementEditor) {
          this.elementEditor.on('update', () => {
            this.settings.ownerId = this.elementEditor.getDraftElementId(
              this.settings.ownerId
            );

            if (this.elementIndex) {
              this.elementIndex.settings.criteria[this.settings.ownerIdParam] =
                this.settings.ownerId;
            }
          });
        }

        this.trigger('afterInit');
      }, 100);
    },

    initCards() {
      this.$elements = this.$container.children('.elements');

      // Was .elements just created?
      if (!this.$elements.length) {
        this.$elements = $('<ul/>', {
          class: `elements ${this.settings.showInGrid ? 'card-grid' : 'cards'}`,
        }).prependTo(this.$container);
        this.$container.children('.zilch').addClass('hidden');
      }

      if (this.settings.sortable) {
        this.elementSort = new Garnish.DragSort({
          container: this.$elements,
          handle:
            '> .element > .card-actions-container > .card-actions > .move',
          ignoreHandleSelector: null,
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          onSortChange: () => {
            this.onSortChange(this.elementSort.$draggee);
          },
        });
      }

      for (let element of this.$elements.children().toArray()) {
        this.initElement($(element).children('.element'));
      }
    },

    deinitCards() {
      if (!this.$elements) {
        return;
      }

      this.$elements.remove();
      this.$elements = null;
      this.elementSort.destroy();
      this.elementSort = null;
      this.$container.children('.zilch').removeClass('hidden');
    },

    initElementIndex() {
      this.elementIndex = Craft.createElementIndex(
        this.elementType,
        this.$container,
        Object.assign(
          {
            context: 'embedded-index',
            sortable: this.settings.sortable,
            prevalidate: this.settings.prevalidate,
          },
          this.settings.indexSettings,
          {
            canDuplicateElements: ($selectedItems) => {
              return this.canCreate($selectedItems.length);
            },
            canDeleteElements: ($selectedItems) => {
              return this.canDelete($selectedItems.length);
            },
            onBeforeDuplicateElements: async () => {
              await this.markAsDirty();
            },
            onDuplicateElements: async () => {
              await this.markAsDirty();
            },
            onBeforeDeleteElements: async () => {
              await this.markAsDirty();
            },
            onDeleteElements: async () => {
              await this.markAsDirty();
            },
            onBeforeUpdateElements: () => {
              if (this.$createBtn) {
                this.$createBtn.addClass('disabled');
              }
            },
            onCountResults: () => {
              this.updateCreateBtn();
            },
            onSortChange: async ($draggee) => {
              await this.onSortChange($draggee);
            },
          }
        )
      );
    },

    async markAsDirty() {
      if (this.elementEditor && this.settings.baseInputName) {
        await this.elementEditor.setFormValue(this.settings.baseInputName, '*');
      }
    },

    async getBaseActionData() {
      // this could end up updating this.settings.ownerId
      await this.markAsDirty();

      return {
        ownerElementType: this.settings.ownerElementType,
        ownerId: this.settings.ownerId,
        ownerSiteId: this.settings.ownerSiteId,
        attribute: this.settings.attribute,
      };
    },

    async onSortChange($draggee) {
      const id = parseInt($draggee.find('.element').data('id'));
      const allIds = this.getElementIds();

      const data = Object.assign(await this.getBaseActionData(), {
        elementIds: [id],
        offset: this.getBaseElementOffset() + allIds.indexOf(id),
      });

      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'nested-elements/reorder',
          {data}
        );
        Craft.cp.displayNotice(response.data.message);
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
      }

      await this.markAsDirty();
    },

    updateCreateBtn() {
      if (!this.$createBtn) {
        return;
      }

      if (this.canCreate()) {
        this.$createBtn.removeClass('disabled');
      } else {
        this.$createBtn.addClass('disabled');
      }
    },

    canCreate(num) {
      if (!this.settings.canCreate) {
        return false;
      }

      if (!this.settings.maxElements) {
        return true;
      }

      const total = this.getTotalElements();

      return total !== null && total + (num || 1) <= this.settings.maxElements;
    },

    canDelete(num) {
      if (!this.settings.minElements) {
        return true;
      }

      return this.getTotalElements() !== null;
    },

    getElementIds() {
      let elements;

      if (this.settings.mode === 'cards') {
        elements = this.$elements.find('> li > .element').toArray();
      } else {
        elements = this.elementIndex.view
          .getAllElements()
          .toArray()
          .map((container) => container.querySelector('.element'));
      }

      return elements
        .map((element) => element.getAttribute('data-id'))
        .filter((id) => id)
        .map((id) => parseInt(id));
    },

    getTotalElements() {
      if (this.settings.mode === 'cards') {
        return this.$elements ? this.$elements.children().length : 0;
      }

      if (this.elementIndex.isIndexBusy) {
        return null;
      }
      return this.elementIndex.totalUnfilteredResults;
    },

    getBaseElementOffset() {
      if (this.settings.mode === 'cards') {
        return 0;
      }

      return (
        this.elementIndex.settings.batchSize * (this.elementIndex.page - 1)
      );
    },

    createElement: async function (attributes) {
      if (this.$createBtn) {
        this.$createBtn.addClass('loading');
      }

      try {
        await this.markAsDirty();

        attributes = Object.assign(
          {
            elementType: this.elementType,
            ownerId: this.settings.ownerId,
            siteId: this.settings.ownerSiteId,
          },
          attributes
        );

        const {data} = await Craft.sendActionRequest(
          'POST',
          'elements/create',
          {
            data: attributes,
          }
        );

        const slideout = Craft.createElementEditor(this.elementType, {
          siteId: data.element.siteId,
          elementId: data.element.id,
          draftId: data.element.draftId,
          params: {
            fresh: 1,
          },
        });

        slideout.on('submit', async () => {
          if (this.settings.mode === 'cards') {
            this.addElementCard(data.element);
          } else {
            this.elementIndex.clearSearch();
            this.elementIndex.updateElements();
          }

          await this.markAsDirty();
        });
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
      } finally {
        if (this.$createBtn) {
          this.$createBtn.removeClass('loading');
        }
      }
    },

    initElement($element) {
      if (Garnish.hasAttr($element, 'data-editable')) {
        this.addListener($element, 'dblclick,taphold', (ev) => {
          if ($(ev.target).closest('a[href],button,[role=button]').length) {
            // Let the link/button do its thing
            return;
          }
          Craft.createElementEditor(this.elementType, $element);
        });
      }

      if (this.settings.sortable) {
        this.elementSort.addItems($element.parent());
      }

      const $actionMenuBtn = $element.find('.action-btn');
      const disclosureMenu = $actionMenuBtn
        .disclosureMenu()
        .data('disclosureMenu');

      if (Garnish.hasAttr($element, 'data-deletable')) {
        const ul = disclosureMenu.addGroup();
        disclosureMenu.addItem(
          {
            icon: 'trash',
            label: this.settings.deleteLabel || Craft.t('app', 'Delete'),
            destructive: true,
            onActivate: () => {
              if (confirm(this.settings.deleteConfirmationMessage)) {
                this.deleteElement($element);
              }
            },
          },
          ul
        );
      }
    },

    async deleteElement($element) {
      const data = Object.assign(await this.getBaseActionData(), {
        elementId: $element.data('id'),
      });

      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'nested-elements/delete',
          {data}
        );
        Craft.cp.displayNotice(response.data.message);
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
        throw e;
      }

      if (this.settings.sortable) {
        this.elementSort.removeItems($element);
      }

      $element.parent().remove();

      // :empty isn't reliable due to text nodes
      if (this.$elements.children().length === 0) {
        this.deinitCards();
      }

      if (this.$createBtn) {
        this.updateCreateBtn();
        if (this.canCreate()) {
          this.$createBtn.focus();
        }
      }

      await this.markAsDirty();
    },

    addElementCard(element) {
      if (this.$createBtn) {
        this.$createBtn.addClass('loading');
      }

      Craft.sendActionRequest('POST', 'app/render-elements', {
        data: {
          elements: [
            {
              type: this.elementType,
              id: element.id,
              siteId: element.siteId,
              instances: [
                {
                  context: 'field',
                  ui: 'card',
                  sortable: this.settings.sortable,
                  showActionMenu: true,
                },
              ],
            },
          ],
        },
      })
        .then(async ({data}) => {
          if (!this.$elements) {
            this.initCards();
          }

          const $li = $('<li/>').appendTo(this.$elements);
          const $element = $(data.elements[element.id][0]).appendTo($li);
          this.initElement($element);
          await Craft.appendHeadHtml(data.headHtml);
          await Craft.appendBodyHtml(data.bodyHtml);
          Craft.cp.elementThumbLoader.load($element);
          this.updateCreateBtn();
        })
        .catch((e) => {
          Craft.cp.displayError(e?.response?.data?.message);
          throw e?.response?.data?.message ?? e;
        })
        .finally(() => {
          if (this.$createBtn) {
            this.$createBtn.removeClass('loading').focus();
          }
        });
    },

    destroy: function () {
      this.$container.removeData('nestedElementManager');
      this.base();
    },
  },
  {
    ownerId: null,
    defaults: {
      mode: 'cards',
      showInGrid: false,
      ownerElementType: null,
      ownerId: null,
      ownerSiteId: null,
      attribute: null,
      sortable: false,
      indexSettings: {},
      canCreate: false,
      minElements: null,
      maxElements: null,
      createButtonLabel: Craft.t('app', 'Create'),
      ownerIdParam: null,
      createAttributes: null,
      fieldHandle: null,
      baseInputName: null,
      deleteLabel: null,
      deleteConfirmationMessage: null,
      prevalidate: false,
    },
  }
);
