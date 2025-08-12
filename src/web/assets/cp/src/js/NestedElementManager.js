/** global: Craft */

/**
 * Nested element manager
 */
Craft.NestedElementManager = Garnish.Base.extend(
  {
    $container: null,
    $btnContainer: null,
    elementType: null,
    $createBtn: null,
    $pasteBtn: null,

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
            icon: 'plus',
            label: this.settings.createButtonLabel,
            spinner: true,
          })
          .addClass('icon disabled');

        if (this.settings.mode === 'cards') {
          this.$createBtn.addClass('dashed wrap');
          this.updateCreateBtn();
        }

        this.addButton(this.$createBtn);

        if (Array.isArray(this.settings.createAttributes)) {
          const createMenuId = `menu-${Math.floor(Math.random() * 1000000)}`;
          $('<div/>', {
            id: createMenuId,
            class: 'menu menu--disclosure',
            'data-with-search-input':
              this.settings.createAttributes.length > 5 ? 'true' : null,
          }).insertAfter(this.$createBtn);
          this.$createBtn
            .attr('aria-controls', createMenuId)
            .attr('data-disclosure-trigger', 'true')
            .addClass('menubtn')
            .disclosureMenu();
          const disclosureMenu = this.$createBtn.data('disclosureMenu');

          // can't use Object.groupBy() here because the group order matters
          const groupedCreateAttributes = {};
          const groupOrder = [];
          this.settings.createAttributes.forEach((attributes) => {
            const group = attributes.group || Craft.t('app', 'General');
            if (!groupedCreateAttributes[group]) {
              groupedCreateAttributes[group] = [];
              groupOrder.push(group);
            }
            groupedCreateAttributes[group].push(attributes);
          });
          const withHeadings = groupOrder.length > 1;

          groupOrder.forEach((group) => {
            if (withHeadings) {
              disclosureMenu.addHr();
              disclosureMenu.addGroup(group, false);
            }

            groupedCreateAttributes[group].forEach((attributes) => {
              disclosureMenu.addItem({
                icon: attributes.icon ? $(attributes.icon)[0] : null,
                label: attributes.label,
                iconColor: attributes.color,
                onActivate: () => {
                  this.createElement(attributes.attributes);
                },
              });
            });
          });
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

      Craft.cp.onCopyElements((elementInfo, buttonLabel) => {
        this.updatePasteButton(elementInfo);
        if (this.$pasteBtn && buttonLabel) {
          this.$pasteBtn.find('.label').text(buttonLabel);
        }
      });
    },

    addButton($button) {
      if (this.settings.mode === 'cards') {
        if (!this.$btnContainer) {
          this.$btnContainer = $btnContainer = $('<div/>', {
            class: 'flex flex-inline',
          }).appendTo(this.$container);
        }
        $button.appendTo(this.$btnContainer);
        this.updateCreateBtn();
      } else {
        $button.appendTo(this.elementIndex.$toolbar);
      }
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
            '> .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn',
          ignoreHandleSelector: null,
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          onSortChange: () => {
            this.onSortChange(this.elementSort.$draggee);
          },
        });
      }

      for (const element of this.$elements.children().toArray()) {
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
            onBeforeMoveElementsToPage: async () => {
              await this.markAsDirty();
            },
            onMoveElementsToPage: async () => {
              await this.markAsDirty();
            },
            onBeforeReorderElements: async () => {
              await this.markAsDirty();
            },
            onReorderElements: async () => {
              await this.markAsDirty();
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
              if (!(await this.markAsDirty())) {
                // save the element anyway in case any conditional fields should be shown/hidden
                this.elementEditor?.checkForm(true);
              }
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
      if (!this.elementEditor || !this.settings.baseInputName) {
        return false;
      }
      return await this.elementEditor.setFormValue(
        this.settings.baseInputName,
        '*'
      );
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
      const elementId = parseInt($draggee.find('.element').data('id'));

      try {
        const response = await this.updateSortOrder(elementId);
        Craft.cp.displayNotice(response.data.message);
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
      }

      if (!(await this.markAsDirty())) {
        // Refresh Live Preview
        Craft.Preview.refresh();
      }
    },

    async updateSortOrder(elementId) {
      elementId = parseInt(elementId);
      const allIds = this.getElementIds();

      const data = Object.assign(await this.getBaseActionData(), {
        elementIds: [elementId],
        offset: this.getBaseElementOffset() + allIds.indexOf(elementId),
      });

      return await Craft.sendActionRequest('POST', 'nested-elements/reorder', {
        data,
      });
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

      this.updatePasteButton();
    },

    updatePasteButton(elementInfo = null) {
      elementInfo = elementInfo || Craft.cp.getCopiedElements();
      if (this.canPaste(elementInfo)) {
        if (!this.$pasteBtn) {
          this.$pasteBtn = Craft.ui.createPasteButton();
          this.addButton(this.$pasteBtn);
          this.addListener(this.$pasteBtn, 'activate', 'pasteElements');
        } else {
          this.$pasteBtn.removeClass('hidden');
        }
      } else {
        this.$pasteBtn?.addClass('hidden');
      }
    },

    canCreate(num = 1) {
      if (!this.settings.canCreate || num === 0) {
        return false;
      }

      if (!this.settings.maxElements) {
        return true;
      }

      const total = this.getTotalElements();

      return total !== null && total + num <= this.settings.maxElements;
    },

    canDelete() {
      if (!this.settings.minElements) {
        return true;
      }

      return this.getTotalElements() !== null;
    },

    canPaste(elementInfo) {
      if (!this.settings.canPaste || !this.canCreate(elementInfo.length)) {
        return false;
      }

      for (const e of elementInfo) {
        if (e.type !== this.elementType) {
          return false;
        }
      }

      if (typeof this.settings.canPaste === 'function') {
        return this.settings.canPaste(elementInfo);
      }

      if (typeof this.settings.canPaste === 'string') {
        return eval(this.settings.canPaste)(elementInfo);
      }

      return true;
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
        if (this.$createBtn.hasClass('loading')) {
          return;
        }

        this.$createBtn.addClass('loading');
        Craft.cp.announce(Craft.t('app', 'Loading'));
      }

      try {
        await this.markAsDirty();

        attributes = Object.assign(
          {
            elementType: this.elementType,
            ownerId: this.settings.ownerId,
            fieldId: this.settings.fieldId,
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

        let shownElement = false;
        let $card;

        const showElement = async (data) => {
          if (!shownElement) {
            shownElement = true;

            if (this.settings.mode === 'cards') {
              $card = await this.addElementCard(data);
            } else {
              this.elementIndex.clearSearch();
              this.elementIndex.updateElements();
            }

            await this.markAsDirty();
          }
        };

        slideout.on('load', () => {
          slideout.elementEditor.once('afterSaveDraft', (ev) => {
            showElement(data.element);
          });
        });

        slideout.on('submit', async () => {
          await showElement(data.element);
        });

        slideout.on('close', () => {
          if (this.$createBtn) {
            this.$createBtn.focus();
          }

          // save the element in case any conditional fields should be shown/hidden
          this.elementEditor?.checkForm(true);
        });
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
      } finally {
        if (this.$createBtn) {
          this.$createBtn.removeClass('loading');
        }
      }
    },

    duplicateElement: async function ($element) {
      Craft.cp.announce(Craft.t('app', 'Loading'));
      await this.markAsDirty();

      let data;
      try {
        const elementId = $element.data('id');
        const response = await Craft.sendActionRequest(
          'POST',
          'elements/duplicate',
          {
            data: {
              elementType: this.elementType,
              ownerId: this.settings.ownerId,
              siteId: this.settings.ownerSiteId,
              elementId:
                this.elementEditor?.getDraftElementId(elementId) || elementId,
            },
          }
        );
        data = response.data;
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
      }

      const $card = await this.addElementCard(data.element);
      $card.parent().insertAfter($element.parent());
      await this.updateSortOrder(data.element.id);
      // save the element in case any conditional fields should be shown/hidden
      this.elementEditor?.checkForm(true);
    },

    async pasteElements() {
      Craft.cp.announce(Craft.t('app', 'Loading'));
      this.$pasteBtn.addClass('loading');

      try {
        await this.markAsDirty();
        const newElementInfo = await Craft.cp.pasteElements(
          Object.assign(
            {
              primaryOwnerId: this.settings.ownerId,
              ownerId: this.settings.ownerId,
              fieldId: this.settings.fieldId,
              siteId: this.settings.ownerSiteId,
            },
            this.settings.pasteAttributes || {}
          )
        );

        if (!newElementInfo.length) {
          return;
        }

        if (this.settings.mode === 'cards') {
          const $cards = await this.addElementCards(newElementInfo, false);
          await this.updateSortOrder(newElementInfo[0].id);
          Garnish.firstFocusableElement($cards).focus();
        } else {
          this.elementIndex.clearSearch();
          await this.elementIndex.updateElements();
        }
      } finally {
        this.$pasteBtn.removeClass('loading');
      }

      // save the element in case any conditional fields should be shown/hidden
      this.elementEditor?.checkForm(true);
    },

    initElement($element) {
      setTimeout(() => {
        const editable = Garnish.hasAttr($element, 'data-editable');

        if (editable) {
          // "Edit" button
          const $editBtn = $element.find('.edit-btn');
          if ($editBtn.length) {
            // Override the default event listener
            $editBtn.off('activate');
            this.addListener($editBtn, 'activate', (ev) => {
              // focus on the button so that when the slideout is closed, it's returned to the button
              $editBtn.focus();
              const cpUrl = $element.data('cpUrl');
              if (cpUrl && Garnish.isCtrlKeyPressed(ev.originalEvent)) {
                window.open(cpUrl);
              } else {
                this.createElementEditor($element);
              }
            });
          }

          // Double-clicks
          this.addListener($element, 'dblclick,taphold', (ev) => {
            if (!$(ev.target).closest('a[href],button,[role=button]').length) {
              this.createElementEditor($element);
            }
          });
        }

        const actionDisclosure = $element
          .find('.action-btn')
          .removeClass('hidden')
          .disclosureMenu()
          .data('disclosureMenu');

        if (actionDisclosure) {
          const $actionMenu = actionDisclosure.$container;

          const destructiveGroup = actionDisclosure.getFirstDestructiveGroup();
          let moveUpButton, moveDownButton, duplicateButton;

          const $li = $element.parent();
          const getPrev = () => $li.prev('li');
          const getNext = () => $li.next('li');

          if (this.settings.sortable) {
            this.elementSort.addItems($li);

            const ul = actionDisclosure.addGroup(null, true, destructiveGroup);

            // Move up/forward
            moveUpButton = actionDisclosure.addItem(
              {
                icon: async () =>
                  await Craft.ui.icon(
                    this.settings.showInGrid
                      ? Craft.orientation === 'ltr'
                        ? 'arrow-left'
                        : 'arrow-right'
                      : 'arrow-up'
                  ),
                label: this.settings.showInGrid
                  ? Craft.t('app', 'Move forward')
                  : Craft.t('app', 'Move up'),
                onActivate: () => {
                  const $prev = getPrev();
                  if ($prev.length) {
                    $li.insertBefore($prev);
                    this.onSortChange($li);
                  }
                },
              },
              ul
            );

            // Move down/backward
            moveDownButton = actionDisclosure.addItem(
              {
                icon: async () =>
                  await Craft.ui.icon(
                    this.settings.showInGrid
                      ? Craft.orientation === 'ltr'
                        ? 'arrow-right'
                        : 'arrow-left'
                      : 'arrow-down'
                  ),
                label: this.settings.showInGrid
                  ? Craft.t('app', 'Move backward')
                  : Craft.t('app', 'Move down'),
                onActivate: () => {
                  const $next = getNext();
                  if ($next.length) {
                    $li.insertAfter($next);
                    this.onSortChange($li);
                  }
                },
              },
              ul
            );
          }

          const duplicatable = Garnish.hasAttr($element, 'data-duplicatable');
          const copyable = Garnish.hasAttr($element, 'data-copyable');

          if (duplicatable || copyable) {
            const ul = actionDisclosure.addGroup(null, true, destructiveGroup);

            if (duplicatable) {
              // Duplicate
              duplicateButton = actionDisclosure.addItem(
                {
                  icon: async () => await Craft.ui.icon('clone'),
                  label: Craft.t('app', 'Duplicate'),
                  onActivate: () => {
                    this.duplicateElement($element);
                  },
                },
                ul
              );
            }

            if (copyable) {
              // Copy
              const $oldCopyBtn = $actionMenu.find('[data-copy-action]');
              if ($oldCopyBtn.length) {
                actionDisclosure.removeItem($oldCopyBtn[0]);
              }

              actionDisclosure.addItem(
                {
                  icon: async () => await Craft.ui.icon('clone-dashed'),
                  iconColor: 'fuchsia',
                  label: Craft.t('app', 'Copy'),
                  onActivate: () => {
                    Craft.cp.copyElements($element);
                  },
                },
                ul
              );
            }
          }

          if (Garnish.hasAttr($element, 'data-deletable')) {
            const ul = actionDisclosure.addGroup();
            actionDisclosure.addItem(
              {
                icon: async () => await Craft.ui.icon('trash'),
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

          actionDisclosure.on('show', () => {
            if (moveUpButton) {
              actionDisclosure.toggleItem(moveUpButton, getPrev().length);
            }

            if (moveDownButton) {
              actionDisclosure.toggleItem(moveDownButton, getNext().length);
            }

            if (duplicateButton) {
              actionDisclosure.toggleItem(duplicateButton, this.canCreate());
            }
          });
        }
      }, 1);
    },

    createElementEditor($element) {
      const slideout = Craft.createElementEditor(this.elementType, $element, {
        ownerId: this.elementEditor?.getDraftElementId(
          $element.data('ownerId')
        ),
        onLoad: () => {
          slideout.elementEditor.on('update', () => {
            Craft.Preview.refresh();
          });
        },
        onBeforeSubmit: async () => {
          // If the nested element is primarily owned by the same owner element it was queried for,
          // then ensure we're working with a draft and save the nested element changes to the draft
          // note: this workflow doesn't apply to elements nested directly in global sets as globals don't use element editor
          if (
            typeof this.elementEditor !== 'undefined' &&
            Garnish.hasAttr($element, 'data-owner-is-canonical') &&
            !Garnish.hasAttr($element, 'data-is-unpublished-draft') &&
            !this.elementEditor.settings.isUnpublishedDraft
          ) {
            await slideout.elementEditor.checkForm(true, true);
            await this.markAsDirty();
            if (
              this.elementEditor.settings.draftId &&
              slideout.elementEditor.settings.draftId
            ) {
              if (!slideout.elementEditor.settings.saveParams) {
                slideout.elementEditor.settings.saveParams = {};
              }
              slideout.elementEditor.settings.saveParams.action =
                'elements/save-nested-element-for-derivative';
              slideout.elementEditor.settings.saveParams.newOwnerId =
                this.settings.ownerId;
            }
          }
        },
        onSubmit: (ev) => {
          if (ev.data.id != $element.data('id')) {
            // swap the element with the new one
            $element
              .attr('data-id', ev.data.id)
              .data('id', ev.data.id)
              .data('owner-id', ev.data.ownerId);
            Craft.refreshElementInstances(ev.data.id);
          }
        },
      });
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

      if (!(await this.markAsDirty())) {
        // save the element anyway in case any conditional fields should be shown/hidden
        this.elementEditor?.checkForm(true);
      }
    },

    async addElementCard(element, showSpinner = true) {
      return await this.addElementCards([element], showSpinner);
    },

    async addElementCards(elements, showSpinner = true) {
      if (this.$createBtn?.hasClass('loading')) {
        return null;
      }

      if (showSpinner && this.$createBtn) {
        this.$createBtn.addClass('loading');
        Craft.cp.announce(Craft.t('app', 'Loading'));
      }

      let data;
      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'app/render-elements',
          {
            data: {
              elements: elements.map((element) => ({
                type: this.elementType,
                id: element.id,
                siteId: element.siteId,
                instances: [
                  {
                    context: 'field',
                    ui: 'card',
                    sortable: this.settings.sortable,
                    showActionMenu: true,
                    hyperlink: false,
                  },
                ],
              })),
            },
          }
        );
        data = response.data;
      } catch (e) {
        Craft.cp.displayError(e?.response?.data?.message);
        throw e?.response?.data?.message ?? e;
      } finally {
        if (showSpinner && this.$createBtn) {
          this.$createBtn.removeClass('loading');
        }
      }

      if (!this.$elements) {
        this.initCards();
      }

      let $cards = $();

      for (const elementInfo of elements) {
        for (const card of data.elements[elementInfo.id] || []) {
          const $li = $('<li/>').appendTo(this.$elements);
          const $card = $(card).appendTo($li);
          $cards = $cards.add($card);
          this.initElement($card);
          Craft.cp.elementThumbLoader.load($card);
        }
      }

      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
      this.updateCreateBtn();

      return $cards;
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
      canPaste: false,
      minElements: null,
      maxElements: null,
      createButtonLabel: Craft.t('app', 'Create'),
      ownerIdParam: null,
      createAttributes: null,
      pasteAttributes: null,
      fieldId: null,
      fieldHandle: null,
      baseInputName: null,
      deleteLabel: null,
      deleteConfirmationMessage: null,
      prevalidate: false,
    },
  }
);
