/** global: Craft */
/** global: Garnish */
/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend(
  {
    elementSelect: null,
    elementSort: null,
    modal: null,
    elementEditor: null,
    modalFirstOpen: true,

    $container: null,
    $form: null,
    $elementsContainer: null,
    $elements: null,
    $addElementBtn: null,
    $spinner: null,

    searchTimeout: null,
    searchMenu: null,
    $searchContainer: null,
    $searchInput: null,
    $searchSpinner: null,
    _ignoreSearchBlur: false,

    _initialized: false,
    _$replaceElement: null,

    get thumbLoader() {
      console.warn(
        'Craft.BaseElementSelectInput::thumbLoader is deprecated. Craft.cp.elementThumbLoader should be used instead.'
      );
      return Craft.cp.elementThumbLoader;
    },

    init: function (settings) {
      // Normalize the settings and set them
      // ---------------------------------------------------------------------

      // Are they still passing in a bunch of arguments?
      if (!$.isPlainObject(settings)) {
        // Loop through all of the old arguments and apply them to the settings
        var normalizedSettings = {},
          args = [
            'id',
            'name',
            'elementType',
            'sources',
            'criteria',
            'sourceElementId',
            'limit',
            'modalStorageKey',
            'fieldId',
          ];

        for (var i = 0; i < args.length; i++) {
          if (typeof arguments[i] !== 'undefined') {
            normalizedSettings[args[i]] = arguments[i];
          } else {
            break;
          }
        }

        settings = normalizedSettings;
      }

      this.setSettings(settings, Craft.BaseElementSelectInput.defaults);

      // Apply the storage key prefix
      if (this.settings.modalStorageKey) {
        this.modalStorageKey =
          'BaseElementSelectInput.' + this.settings.modalStorageKey;
      }

      // No reason for this to be sortable if we're only allowing 1 selection
      if (this.settings.limit == 1 || this.settings.maintainHierarchy) {
        this.settings.sortable = false;
      }

      this.$container = this.getContainer();
      this.$form = this.$container.closest('form');

      // Store a reference to this class
      this.$container.data('elementSelect', this);

      this.$elementsContainer = this.getElementsContainer();
      this.$addElementBtn = this.getAddElementsBtn();
      this.$spinner = this.getSpinner();
      this.$searchContainer = this.getSearchContainer();
      this.$searchInput = this.getSearchInput();
      this.$searchSpinner = this.getSearchSpinner();

      this.initElementSelect();
      this.initElementSort();
      this.initSearch();
      this.resetElements();

      if (this.$addElementBtn.length) {
        this.addListener(this.$addElementBtn, 'activate', 'showModal');
      }

      Garnish.requestAnimationFrame(() => {
        this._initialized = true;
      });

      if (this.elementSelect) {
        this.addListener(Garnish.$win, 'mousedown', (ev) => {
          if (
            !this.$container.is(ev.target) &&
            !this.$container.find(ev.target).length &&
            !$(ev.target).closest('.menu').length
          ) {
            this.elementSelect.deselectAll();
          }
        });
      }

      setTimeout(() => {
        this.elementEditor = this.$container
          .closest('form')
          .data('elementEditor');
      }, 100);
    },

    get totalSelected() {
      return this.$elements.length;
    },

    getContainer: function () {
      return $('#' + this.settings.id);
    },

    getElementsContainer: function () {
      return this.$container.children('.elements');
    },

    getElements: function () {
      if (this.$elementsContainer.hasClass('structure')) {
        return this.$elementsContainer.find('> li .row .element');
      } else {
        return this.$elementsContainer.find('> li > .element');
      }
    },

    getSearchContainer: function () {
      return this.$container.find('.elementselect__search-input-wrapper');
    },

    getSearchInput: function () {
      return this.$searchContainer.find('input');
    },

    getSearchSpinner: function () {
      return this.$searchContainer.find('.spinner');
    },

    getAddElementsBtn: function () {
      return this.$container.find('.btn.add:first');
    },

    getSpinner: function () {
      return this.$container.find('.spinner');
    },

    initElementSelect: function () {
      if (this.settings.selectable) {
        this.elementSelect = new Garnish.Select(this.elementSelectSettings());
      }
    },

    elementSelectSettings() {
      return {
        multi: this.settings.sortable,
        filter: (target) => {
          return !$(target).closest('a[href],button,[role=button]').length;
        },
        // prevent keyboard focus since element selection is only needed for drag-n-drop
        makeFocusable: false,
      };
    },

    initElementSort: function () {
      if (this.settings.sortable) {
        this.elementSort = new Garnish.DragSort({
          container: this.$elementsContainer,
          filter: this.settings.selectable
            ? () => {
                // Only return all the selected items if the target item is selected
                if (
                  this.elementSort.$targetItem
                    .children('.element')
                    .hasClass('sel')
                ) {
                  return this.elementSelect.getSelectedItems().parent('li');
                } else {
                  return this.elementSort.$targetItem;
                }
              }
            : null,
          ignoreHandleSelector: '.delete',
          handle: (() => {
            switch (this.settings.viewMode) {
              case 'list':
              case 'large':
                return '> .element > .chip-content > .chip-actions > .move-btn';
              case 'cards':
                return '> .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn';
              default:
                return null;
            }
          })(),
          axis: this.getElementSortAxis(),
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          onBeforeDragStart: () => {
            this.elementEditor?.pause();

            // Disable all craft-element-labels so connectedCallback()
            // doesn't get fired constantly during drag
            this.$elementsContainer
              .find('craft-element-label')
              .attr('disabled', true);
          },
          onDragStop: () => {
            this.elementEditor?.resume();

            // Put things back where we found them.
            this.$elementsContainer
              .find('craft-element-label')
              .removeAttr('disabled');
          },
          onSortChange: () => {
            this.onSortChange();
          },
        });
      }
    },

    getElementSortAxis: function () {
      if (
        ['list'].includes(this.settings.viewMode) &&
        !this.getElementsContainer().hasClass('inline-chips')
      ) {
        return 'y';
      }
      return null;
    },

    canAddMoreElements: function () {
      return (
        this.settings.allowAdd &&
        (!this.settings.limit || this.$elements.length < this.settings.limit)
      );
    },

    updateAddElementsBtn: function () {
      if (this.canAddMoreElements()) {
        this.enableAddElementsBtn();
      } else {
        this.disableAddElementsBtn();
      }
    },

    enableAddElementsBtn: function () {
      if (this.settings.allowAdd && this.$addElementBtn.length) {
        this.$addElementBtn.removeClass('hidden');
        this.$searchContainer.removeClass('hidden');
      }

      this.updateButtonContainer();
    },

    disableAddElementsBtn: function () {
      if (this.$addElementBtn.length) {
        this.$addElementBtn.addClass('hidden');
        this.$searchContainer.addClass('hidden');
      }

      this.updateButtonContainer();
    },

    showSpinner: function () {
      if (this.$spinner) {
        this.$spinner.removeClass('hidden');
      }

      this.updateButtonContainer();
    },

    hideSpinner: function () {
      if (this.$spinner) {
        this.$spinner.addClass('hidden');
      }

      this.updateButtonContainer();
    },

    updateButtonContainer: function () {
      const $container =
        this.$addElementBtn.length && this.$addElementBtn.parent('.flex');
      if ($container?.length) {
        if ($container.children(':not(.hidden)').length) {
          $container.removeClass('hidden');
        } else {
          $container.addClass('hidden');
        }
      }
    },

    focusNextLogicalElement: function () {
      if (this.canAddMoreElements()) {
        // If can add more elements, focus on search input or add button
        if (
          this.$searchContainer.length &&
          !this.$searchContainer.hasClass('hidden')
        ) {
          this.$searchInput.focus();
        } else if (
          this.$addElementBtn.length &&
          !this.$addElementBtn.hasClass('hidden')
        ) {
          this.$addElementBtn.focus();
        }
      } else {
        // If can't add more elements, focus on the final remove
        this.focusLastRemoveBtn();
      }
    },

    /** @deprecated */
    focusLastRemoveBtn: function () {
      this.focusLastActionBtn();
    },

    focusLastActionBtn: function () {
      this.$container.find('.action-btn').last().focus();
    },

    resetElements: function () {
      if (this.$elements !== null) {
        this.removeElements(this.$elements);
      } else {
        this.$elements = $();
      }

      this.addElements(this.getElements());
    },

    addElements: function ($elements) {
      // add the action triggers
      for (let i = 0; i < $elements.length; i++) {
        const $element = $elements.eq(i);
        const actions = this.defineElementActions($element);

        if (actions.length) {
          Craft.addActionsToChip($element, actions);

          const disclosureMenu = $element
            .find(
              '> .chip-content > .chip-actions .action-btn, > .card-titlebar > .card-actions-container > .card-actions .action-btn'
            )
            .data('disclosureMenu');
          const moveForwardBtn = disclosureMenu.$container.find(
            '[data-move-forward]'
          )[0];
          const moveBackwardBtn = disclosureMenu.$container.find(
            '[data-move-backward]'
          )[0];

          disclosureMenu.on('show', () => {
            const $li = $element.parent();
            const $prev = $li.prev();
            const $next = $li.next();

            if (moveForwardBtn) {
              disclosureMenu.toggleItem(moveForwardBtn, $prev.length);
            }
            if (moveBackwardBtn) {
              disclosureMenu.toggleItem(moveBackwardBtn, $next.length);
            }
          });
        }

        if (this.settings.sortable) {
          Craft.ui
            .createButton({
              class: 'chromeless small move-btn',
              icon: 'move',
            })
            .attr({
              title: Craft.t('app', 'Reorder'),
              'aria-label': Craft.t('app', 'Reorder'),
              'aria-describedby': $element.find('.label').attr('id'),
            })
            .appendTo(
              $element.find(
                '> .chip-content > .chip-actions, > .card-titlebar > .card-actions-container > .card-actions'
              )
            );
        }
      }

      Craft.cp.elementThumbLoader.load($elements);

      if (this.settings.selectable) {
        this.elementSelect.addItems($elements);
      }

      if (this.settings.sortable) {
        this.elementSort.addItems($elements.parent('li'));
      }

      if (this.settings.editable) {
        this._handleShowElementEditor = (ev) => {
          // don't open the edit slideout if we are tapholding to drag
          if (ev.type === 'taphold' && ev.target.nodeName === 'BUTTON') {
            return;
          }

          var $element = $(ev.currentTarget);
          if (
            Garnish.hasAttr($element, 'data-editable') &&
            !$element.hasClass('disabled') &&
            !$element.hasClass('loading')
          ) {
            this.createElementEditor($element);
          }
        };

        this.addListener($elements, 'dblclick', this._handleShowElementEditor);

        if ($.isTouchCapable()) {
          this.addListener($elements, 'taphold', this._handleShowElementEditor);
        }
      }

      $elements.on('keydown', (ev) => {
        if ([Garnish.BACKSPACE_KEY, Garnish.DELETE_KEY].includes(ev.keyCode)) {
          ev.stopPropagation();
          ev.preventDefault();
          const $elements = this.elementSelect.getSelectedItems();
          for (let i = 0; i < $elements.length; i++) {
            this.removeElement($elements.eq(i));
          }
        }
      });

      this.$elements = this.$elements.add($elements);

      this.updateAddElementsBtn();
      this.onAddElements();
      this.onSortChange();
    },

    defineElementActions: function ($element) {
      const actions = [];

      if (this.settings.sortable) {
        const axis = this.getElementSortAxis();
        actions.push({
          icon: async () =>
            await Craft.ui.icon(
              axis === 'y'
                ? 'arrow-up'
                : Craft.orientation === 'ltr'
                  ? 'arrow-left'
                  : 'arrow-right'
            ),
          label:
            axis === 'y'
              ? Craft.t('app', 'Move up')
              : Craft.t('app', 'Move forward'),
          callback: () => {
            this.moveElementForward($element);
          },
          attributes: {
            'data-move-forward': true,
          },
        });
        actions.push({
          icon: async () =>
            await Craft.ui.icon(
              axis === 'y'
                ? 'arrow-down'
                : Craft.orientation === 'ltr'
                  ? 'arrow-right'
                  : 'arrow-left'
            ),
          label:
            axis === 'y'
              ? Craft.t('app', 'Move down')
              : Craft.t('app', 'Move backward'),
          callback: () => {
            this.moveElementBackward($element);
          },
          attributes: {
            'data-move-backward': true,
          },
        });
      }

      if (this.settings.allowRemove) {
        if (this.settings.elementType) {
          actions.push({
            icon: async () => await Craft.ui.icon('arrows-rotate'),
            label: Craft.t('app', 'Replace'),
            callback: () => {
              this._$replaceElement = $element;
              this.showModal();
            },
          });
        }

        actions.push({
          icon: async () => await Craft.ui.icon('remove'),
          label: Craft.t('app', 'Remove'),
          callback: () => {
            // If the element is selected, remove *all* the selected elements
            if (this.elementSelect?.isSelected($element)) {
              this.removeElement(this.elementSelect.getSelectedItems());
            } else {
              this.removeElement($element);
            }
          },
        });
      }

      return actions;
    },

    createElementEditor: function ($element, settings) {
      settings = Object.assign(
        {
          elementSelectInput: this,
          prevalidate: this.settings.prevalidate,
        },
        settings
      );

      return Craft.createElementEditor(
        this.settings.elementType,
        $element,
        settings
      );
    },

    replaceElement: function (elementId, replacementId) {
      return new Promise((resolve, reject) => {
        const $existing = this.$elements.filter(`[data-id="${elementId}"]`);

        if (!$existing.length) {
          reject(`No element selected with an ID of ${elementId}.`);
          return;
        }

        this.showSpinner();

        Craft.sendActionRequest('POST', 'app/render-elements', {
          data: {
            elements: [
              {
                type: this.settings.elementType,
                id: replacementId,
                siteId: this.settings.criteria.siteId,
                instances: [
                  {
                    context: 'field',
                    ui: ['list', 'large'].includes(this.settings.viewMode)
                      ? 'chip'
                      : 'card',
                    size:
                      this.settings.viewMode === 'large' ? 'large' : 'small',
                    showActionMenu: this.settings.showActionMenu,
                  },
                ],
              },
            ],
          },
        })
          .then(async ({data}) => {
            this.removeElement($existing);
            const elementInfo = Craft.getElementInfo(
              data.elements[replacementId][0]
            );
            this.selectElements([elementInfo]).then(resolve);
            await Craft.appendHeadHtml(data.headHtml);
            await Craft.appendBodyHtml(data.bodyHtml);
          })
          .catch((e) => {
            Craft.cp.displayError(e?.response?.data?.message);
            reject(e?.response?.data?.message);
          })
          .finally(() => {
            this.hideSpinner();
          });
      });
    },

    onSortChange() {
      this.elementSelect?.resetItemOrder();
      this.$elements = $().add(this.$elements);
    },

    moveElementForward($element) {
      const $li = $element.closest('li');
      const $prev = $li.prev();
      if ($prev.length) {
        $li.insertBefore($prev);
        this.onSortChange();
      }
    },

    moveElementBackward($element) {
      const $li = $element.closest('li');
      const $next = $li.next();
      if ($next.length) {
        $li.insertAfter($next);
        this.onSortChange();
      }
    },

    /**
     * Removes elements from the field value, without actually removing their DOM nodes.
     * @param $elements
     */
    removeElements: function ($elements) {
      if (this.settings.selectable) {
        this.elementSelect.removeItems($elements);
      }

      if (this.modal) {
        var ids = [];

        for (var i = 0; i < $elements.length; i++) {
          var id = $elements.eq(i).data('id');

          if (id) {
            ids.push(id);
          }
        }

        if (ids.length) {
          this.modal.elementIndex.enableElementsById(ids);
        }
      }

      // Disable the hidden input in case the form is submitted before this element gets removed from the DOM
      $elements.children('input').prop('disabled', true);

      // Move the focus to the next element in the list, if there is one
      let $nextElement;
      if (this.settings.selectable) {
        const lastElementIndex = this.$elements.index($elements.last());
        $nextElement = this.$elements.eq(lastElementIndex + 1);
      }
      if ($nextElement?.length) {
        $nextElement.focus();
      } else {
        setTimeout(() => {
          this.focusNextLogicalElement();
        }, 200);
      }

      this.$elements = this.$elements.not($elements);
      this.updateAddElementsBtn();
      this.onSortChange();
      this.onRemoveElements();
    },

    /**
     * Completely removes an element(s) from the UI and field value.
     * @param $elements
     */
    removeElement: function ($elements) {
      if (this.settings.maintainHierarchy) {
        // Find any descendants the elements have
        let $descendants = $();
        for (let i = 0; i < $elements.length; i++) {
          $descendants = $descendants.add(
            $elements.eq(i).parent().siblings('ul').find('.element')
          );
        }
        $elements = $elements.add($descendants);
      }

      // Remove any inputs from the form data
      $('[name]', $elements).removeAttr('name');

      // Remove our record of them all at once
      this.removeElements($elements);

      if (this.settings.maintainHierarchy) {
        for (let i = 0; i < $elements.length; i++) {
          this._animateStructureElementAway($elements, i);
        }
      } else {
        for (let i = 0; i < $elements.length; i++) {
          const $element = $elements.eq(i);
          const $li = $element.parent('li');
          this.animateElementAway($element);
          $li.remove();
        }
      }
    },

    animateElementAway: async function ($element, callback) {
      const offset = $element.offset();
      const width = $element.width();

      $element.appendTo(Garnish.$bod).css({
        'z-index': 0,
        position: 'absolute',
        top: offset.top,
        left: offset.left,
        maxWidth: width + 'px',
      });

      await Craft.animate($element, {
        opacity: -1,
        left: offset.left + 100 * (Craft.orientation === 'ltr' ? -1 : 1),
      });

      $element.remove();
      if (callback) {
        callback();
      }
    },

    showModal: function () {
      // Make sure we haven't reached the limit
      if (!this._$replaceElement && !this.canAddMoreElements()) {
        return;
      }

      if (!this.modal) {
        this.modal = this.createModal();
        this.modalFirstOpen = false;
      } else {
        this.modal.show();
      }
    },

    createModal: function () {
      return Craft.createElementSelectorModal(
        this.settings.elementType,
        this.getModalSettings()
      );
    },

    getModalSettings: function () {
      const settings = $.extend(
        {
          closeOtherModals: false,
          storageKey: this.modalStorageKey,
          sources: this.settings.sources,
          condition: this.settings.condition,
          referenceElementId: this.settings.referenceElementId
            ? this.elementEditor?.getDraftElementId(
                this.settings.referenceElementId
              ) || this.settings.referenceElementId
            : null,
          referenceElementOwnerId: this.settings.referenceElementOwnerId
            ? this.elementEditor?.getDraftElementId(
                this.settings.referenceElementOwnerId
              ) || this.settings.referenceElementOwnerId
            : null,
          referenceElementSiteId: this.settings.referenceElementSiteId,
          criteria: Object.assign({}, this.settings.criteria),
          multiSelect: this.settings.limit != 1,
          hideOnSelect: false,
          showSiteMenu: this.settings.showSiteMenu,
          disabledElementIds: this.getDisabledElementIds(),
          onSelect: this.onModalSelect.bind(this),
          onHide: this.onModalHide.bind(this),
          triggerElement: this.$addElementBtn,
          modalTitle: Craft.t('app', 'Choose'),
        },
        this.settings.modalSettings
      );

      // make sure the previously-selected source is retained each time the
      // modal is re-opened
      if (!this.modalFirstOpen) {
        settings.preferStoredSource = true;
      }

      return settings;
    },

    getSelectedElementIds: function () {
      const ids = [];

      for (let i = 0; i < this.$elements.length; i++) {
        ids.push(this.$elements.eq(i).data('id'));
      }

      return ids;
    },

    getDisabledElementIds: function () {
      var ids = this.getSelectedElementIds();

      if (!this.settings.allowSelfRelations && this.settings.sourceElementId) {
        ids.push(this.settings.sourceElementId);
      }

      if (this.settings.disabledElementIds) {
        ids.push(...this.settings.disabledElementIds);
      }

      return ids;
    },

    onModalSelect: async function (elements) {
      // Disable the modal
      this.modal?.disable();
      this.modal?.disableCancelBtn();
      this.modal?.disableSelectBtn();
      this.modal?.showFooterSpinner();

      if (this._$replaceElement) {
        this.removeElement(this._$replaceElement);
        this._$replaceElement = null;
      }

      // re-render the elements even if the view modes match, to be sure we have all the correct settings
      const [inputUiType, inputUiSize] = (() => {
        switch (this.settings.viewMode) {
          case 'large':
            return ['chip', 'large'];
          case 'cards':
            return ['card', null];
          default:
            return ['chip', 'small'];
        }
      })();
      const {data} = await Craft.sendActionRequest(
        'POST',
        'app/render-elements',
        {
          data: {
            elements: [
              {
                type: this.settings.elementType,
                id: elements.map((e) => e.id),
                siteId: elements[0].siteId,
                instances: [
                  {
                    context: 'field',
                    ui: inputUiType,
                    size: inputUiSize,
                    showActionMenu: this.settings.showActionMenu,
                  },
                ],
              },
            ],
          },
        }
      );

      for (let i = 0; i < elements.length; i++) {
        if (typeof data.elements[elements[i].id] !== 'undefined') {
          elements[i].$modalElement = elements[i].$element;
          elements[i].$element = $(data.elements[elements[i].id][0]);
        }
      }

      if (this.settings.maintainHierarchy) {
        await this.selectStructuredElements(elements);
      } else {
        if (this.settings.limit) {
          // Cut off any excess elements
          var slotsLeft = this.settings.limit - this.$elements.length;

          if (elements.length > slotsLeft) {
            elements = elements.slice(0, slotsLeft);
          }
        }

        await this.selectElements(elements);
        this.updateDisabledElementsInModal();
      }

      // Re-enable and hide the modal
      this.modal?.enable();
      this.modal?.enableCancelBtn();
      this.modal?.enableSelectBtn();
      this.modal?.hideFooterSpinner();
      this.modal?.hide();

      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
    },

    onModalHide: function () {
      // If the modal has a condition and a reference element, recreate it each time it’s opened
      // in case something about the edited element is going to affect the condition
      if (
        this.modal &&
        this.settings.condition &&
        this.settings.referenceElementId
      ) {
        this.modal.destroy();
        this.modal = null;
      }

      this._$replaceElement = null;

      // If we can't add any more elements, don't focus on the “Add” button
      if (!this.canAddMoreElements()) {
        setTimeout(() => {
          this.focusNextLogicalElement();
        }, 200);
      }
    },

    selectElements: async function (elements) {
      const animateElements = [];

      for (let i = 0; i < elements.length; i++) {
        const elementInfo = elements[i];
        const $inputElement = this.createNewElement(elementInfo);

        this.appendElement($inputElement);
        this.addElements($inputElement);

        // Override the element reference with the new one
        elementInfo.$element = $inputElement;

        const $modalElement = elementInfo.$modalElement || elementInfo.$element;

        if ($modalElement && $modalElement.parent().length) {
          animateElements.push({$modalElement, $inputElement});
        }
      }

      this.animateElementsIntoPlace(animateElements);
      this.onSelectElements(elements);
    },

    selectStructuredElements: async function (elements) {
      // Get the new element HTML
      var selectedElementIds = this.getSelectedElementIds();

      for (var i = 0; i < elements.length; i++) {
        selectedElementIds.push(elements[i].id);
      }

      var data = {
        elementIds: selectedElementIds,
        siteId: elements[0].siteId,
        containerId: this.settings.id,
        name: this.settings.name,
        branchLimit: this.settings.branchLimit,
        selectionLabel: this.settings.selectionLabel,
        elementType: this.settings.elementType,
      };

      const response = await Craft.sendActionRequest(
        'POST',
        'relational-fields/structured-input-html',
        {data}
      );

      const $newInput = $(response.data.html),
        $newElementsContainer = $newInput.children('.elements');

      this.$elementsContainer.replaceWith($newElementsContainer);
      this.$elementsContainer = $newElementsContainer;

      await Craft.appendHeadHtml(response.data.headHtml);
      await Craft.appendBodyHtml(response.data.bodyHtml);

      this.resetElements();

      const filteredElements = [];
      const animateElements = [];

      for (let i = 0; i < elements.length; i++) {
        const element = elements[i];
        const $inputElement = this.getElementById(element.id);

        if ($inputElement) {
          filteredElements.push(element);
          animateElements.push({
            $modalElement: element.$element,
            $inputElement,
          });
        }
      }

      this.animateElementsIntoPlace(animateElements);
      this.updateDisabledElementsInModal();
      this.onSelectElements(filteredElements);
    },

    createNewElement: function (elementInfo) {
      var $element = elementInfo.$element.clone();
      var removeText = Craft.t('app', 'Remove {label}', {
        label: Craft.escapeHtml(elementInfo.label),
      });
      // Make a couple tweaks
      Craft.setElementSize(
        $element,
        this.settings.viewMode === 'large' ? 'large' : 'small'
      );
      $element.addClass('removable').append(
        $('<input/>', {
          type: 'hidden',
          name: this.settings.name + (this.settings.single ? '' : '[]'),
          value: elementInfo.id,
        })
      );

      return $element;
    },

    appendElement: function ($element) {
      const $li = $('<li/>').append($element);
      if (this.settings.defaultPlacement === 'beginning') {
        $li.prependTo(this.$elementsContainer);
      } else {
        $li.appendTo(this.$elementsContainer);
      }
    },

    animateElementIntoPlace: async function ($modalElement, $inputElement) {
      await this.animateElementsIntoPlace([{$modalElement, $inputElement}]);
    },

    animateElementsIntoPlace: async function (elements) {
      const animations = [];

      for (const element of elements) {
        const {$modalElement, $inputElement} = element;
        const oldOffset = $modalElement.offset();
        const newOffset = $inputElement.offset();

        const $helper = $inputElement
          .clone()
          .appendTo(Garnish.$bod)
          .width($inputElement.width());

        $inputElement.css('visibility', 'hidden');

        $helper.css({
          position: 'absolute',
          zIndex: 10000,
          top: oldOffset.top,
          left: oldOffset.left,
        });

        animations.push([
          $helper,
          {
            top: newOffset.top,
            left: newOffset.left,
          },
        ]);
      }

      await Craft.animateAll(animations);

      for (const [$helper] of animations) {
        $helper.remove();
      }

      for (const {$inputElement} of elements) {
        $inputElement.css('visibility', '');
      }
    },

    updateDisabledElementsInModal: function () {
      if (this.modal?.elementIndex) {
        this.modal.elementIndex.disableElementsById(
          this.getDisabledElementIds()
        );
      }
    },

    getElementById: function (id) {
      for (var i = 0; i < this.$elements.length; i++) {
        var $element = this.$elements.eq(i);

        if ($element.data('id') == id) {
          return $element;
        }
      }
    },

    onSelectElements: function (elements) {
      this.trigger('selectElements', {elements});
      this.settings.onSelectElements(elements);
      this.$container.trigger('change');
    },

    onAddElements: function () {
      this.trigger('addElements');
      this.settings.onAddElements();
      this.$container.trigger('change');
    },

    onRemoveElements: function () {
      this.trigger('removeElements');
      this.settings.onRemoveElements();
      this.$container.trigger('change');
    },

    _animateStructureElementAway: function ($allElements, i) {
      let callback;

      // Is this the last one?
      if (i === $allElements.length - 1) {
        const $li = $allElements.first().parent().parent();
        const $ul = $li.parent();
        callback = () => {
          if ($ul[0] === this.$elementsContainer[0] || $li.siblings().length) {
            $li.remove();
          } else {
            $ul.remove();
          }
        };
      }

      const func = () => {
        this.animateElementAway($allElements.eq(i), callback);
      };

      if (i === 0) {
        func();
      } else {
        setTimeout(func, 100 * i);
      }
    },

    canCreateElements: function () {
      return false;
    },

    createElement: async function (title) {},

    initSearch() {
      if (!this.$searchInput.length) {
        return;
      }

      this.addListener(this.$searchInput, 'input', () => {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(() => {
          this.searchForTags();
        }, 500);
      });

      this.addListener(this.$searchInput, 'keydown', function (ev) {
        if (ev.keyCode === Garnish.RETURN_KEY) {
          ev.preventDefault();
        }

        switch (ev.keyCode) {
          case Garnish.RETURN_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              this.selectSearchResult(
                this.searchMenu.$options.filter('.hover')
              );
            }
            return;
          }

          case Garnish.DOWN_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              let $hoverOption = this.searchMenu.$options.filter('.hover');
              if ($hoverOption.length) {
                let $nextOption = $hoverOption
                  .parent()
                  .nextAll()
                  .find('.menu-item:not(.disabled)')
                  .first();
                if ($nextOption.length) {
                  this.focusOption($nextOption);
                }
              } else {
                this.focusOption(this.searchMenu.$options.eq(0));
              }
            }
            return;
          }

          case Garnish.UP_KEY: {
            ev.preventDefault();
            if (this.searchMenu) {
              let $hoverOption = this.searchMenu.$options.filter('.hover');
              if ($hoverOption.length) {
                let $prevOption = $hoverOption
                  .parent()
                  .prevAll()
                  .find('.menu-item:not(.disabled)')
                  .last();
                if ($prevOption.length) {
                  this.focusOption($prevOption);
                }
              } else {
                this.focusOption(
                  this.searchMenu.$options.eq(
                    this.searchMenu.$options.length - 1
                  )
                );
              }
            }
            return;
          }
        }
      });

      this.addListener(this.$searchInput, 'focus', function () {
        if (this.searchMenu) {
          this.searchMenu.show();
        }
      });

      this.addListener(this.$searchInput, 'blur', function () {
        if (this._ignoreSearchBlur) {
          this._ignoreSearchBlur = false;
          return;
        }

        setTimeout(() => {
          if (this.searchMenu) {
            this.searchMenu.hide();
          }
        }, 1);
      });
    },

    searchForTags: async function () {
      if (this.searchMenu) {
        this.killSearchMenu();
      }

      const val = this.$searchInput.val();

      if (val) {
        this.$searchSpinner.removeClass('hidden');
        Craft.cp.announce(Craft.t('app', 'Loading'));

        const excludeIds = [];

        for (let i = 0; i < this.$elements.length; i++) {
          const id = $(this.$elements[i]).data('id');
          if (id) {
            excludeIds.push(id);
          }
        }

        // take allowSelfRelations into consideration too
        if (
          this.settings.sourceElementId &&
          !this.settings.allowSelfRelations
        ) {
          excludeIds.push(this.settings.sourceElementId);
        }

        let response;

        try {
          response = await Craft.sendActionRequest(
            'POST',
            'element-search/search',
            {
              data: {
                elementType: this.settings.elementType,
                siteId: this.settings.criteria.siteId,
                search: val,
                criteria: this.settings.searchCriteria,
                condition: this.settings.condition,
                referenceElementId: this.settings.referenceElementId
                  ? this.elementEditor?.getDraftElementId(
                      this.settings.referenceElementId
                    ) || this.settings.referenceElementId
                  : null,
                referenceElementOwnerId: this.settings.referenceElementOwnerId
                  ? this.elementEditor?.getDraftElementId(
                      this.settings.referenceElementOwnerId
                    ) || this.settings.referenceElementOwnerId
                  : null,
                referenceElementSiteId: this.settings.referenceElementSiteId,
                excludeIds,
              },
            }
          );
        } finally {
          // Just in case
          if (this.searchMenu) {
            this.killSearchMenu();
          }
          this.$searchSpinner.addClass('hidden');
          Craft.cp.announce(Craft.t('app', 'Loading complete'));
        }

        const $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod);
        const $ul = $('<ul/>').appendTo($menu);

        for (const element of response.data.elements) {
          const $li = $('<li/>').appendTo($ul);
          const optionLabel = `${Craft.t('app', 'Existing {type}', {
            type:
              Craft.elementTypeNames[this.settings.elementType][2] ??
              Craft.t('app', 'element'),
          })}: ${element.title}`;
          $li.attr('aria-label', optionLabel);

          const $item = $('<div class="menu-item"/>')
            .appendTo($li)
            .html(element.html)
            .data('id', element.id);

          if (element.exclude) {
            $item.addClass('disabled');
          }
        }

        if (!response.data.exactMatch && this.canCreateElements()) {
          const $li = $('<li/>').appendTo($ul);
          const optionLabel = `${Craft.t('app', 'Create {type}', {
            type:
              Craft.elementTypeNames[this.settings.elementType][2] ??
              Craft.t('app', 'element'),
          })}: ${val}`;
          $li.attr('aria-label', optionLabel);

          $('<div class="menu-item" data-icon="plus"/>')
            .appendTo($li)
            .text(val);
        }

        $ul.find('.menu-item:not(.disabled):first').addClass('hover');

        this.searchMenu = new Garnish.CustomSelect($menu, {
          anchor: this.$searchInput,
          onOptionSelect: (option) => {
            this.selectSearchResult(option);
          },
        });

        // Add required ARIA attributes
        this.$searchInput.attr('aria-controls', this.searchMenu.menuId);

        this.searchMenu.on('show', () => {
          this.$searchInput.attr('aria-expanded', 'true');
          this.focusSelectedOption();
          Craft.cp.elementThumbLoader.load($menu);
        });

        this.searchMenu.on('hide', () => {
          this.$searchInput.attr('aria-expanded', 'false');
          this.$searchInput.removeAttr('aria-activedescendant');
        });

        this.addListener($menu, 'mousedown', () => {
          this._ignoreSearchBlur = true;
        });

        this.searchMenu.show();
      } else {
        // No need to update the live region here
        this.$searchSpinner.addClass('hidden');
      }
    },

    focusOption: function ($option) {
      this.searchMenu.$options.removeClass('hover');
      this.searchMenu.$ariaOptions.attr('aria-selected', 'false');

      $option.addClass('hover');
      this.$searchInput.attr(
        'aria-activedescendant',
        $option.parent('li').attr('id')
      );
    },

    focusSelectedOption: function () {
      let $option = this.searchMenu.$options.filter('.hover:first');

      if ($option.length) {
        this.focusOption($option);
      } else {
        this.focusFirstOption();
      }
    },

    focusFirstOption: function () {
      const $option = this.searchMenu.$options.first();
      this.focusOption($option);
    },

    selectSearchResult: async function (option) {
      const $option = $(option);

      if ($option.hasClass('disabled')) {
        return;
      }

      let id = $option.data('id');

      if (!id && this.canCreateElements()) {
        id = await this.createElement($option.text());
      }

      if (id) {
        await this.onModalSelect([
          {
            id,
            siteId: this.settings.criteria.siteId,
          },
        ]);
      }

      this.killSearchMenu();
      this.$searchInput.val('');
      setTimeout(() => {
        this.focusNextLogicalElement();
      }, 200);
    },

    killSearchMenu: function () {
      this.searchMenu.hide();
      this.searchMenu.destroy();
      this.searchMenu = null;
    },
  },
  {
    ADD_FX_DURATION: 200,
    REMOVE_FX_DURATION: 200,

    defaults: {
      id: null,
      name: null,
      fieldId: null,
      elementType: null,
      sources: null,
      condition: null,
      referenceElementId: null,
      referenceElementOwnerId: null,
      referenceElementSiteId: null,
      criteria: {},
      searchCriteria: null,
      allowAdd: true,
      allowRemove: true,
      allowSelfRelations: false,
      sourceElementId: null,
      disabledElementIds: null,
      viewMode: 'list',
      single: false,
      maintainHierarchy: false,
      branchLimit: null,
      limit: null,
      defaultPlacement: 'end',
      showSiteMenu: false,
      modalStorageKey: null,
      modalSettings: {},
      onAddElements: $.noop,
      onSelectElements: $.noop,
      onRemoveElements: $.noop,
      sortable: true,
      selectable: true,
      showActionMenu: true,
      editable: true,
      prevalidate: false,
      editorSettings: {},
    },
  }
);
