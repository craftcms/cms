/** global: Craft */
/** global: Garnish */
/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend(
  {
    thumbLoader: null,
    elementSelect: null,
    elementSort: null,
    modal: null,
    elementEditor: null,

    fieldLabel: null,

    $container: null,
    $form: null,
    $elementsContainer: null,
    $elements: null,
    $addElementBtn: null,
    $spinner: null,

    _initialized: false,

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
      if (this.settings.limit == 1) {
        this.settings.sortable = false;
      }

      this.$container = this.getContainer();
      this.$form = this.$container.closest('form');
      this.fieldLabel = this.getFieldLabel();

      // Store a reference to this class
      this.$container.data('elementSelect', this);

      this.$elementsContainer = this.getElementsContainer();
      this.$addElementBtn = this.getAddElementsBtn();
      this.$spinner = this.getSpinner();

      this.thumbLoader = new Craft.ElementThumbLoader();

      this.initElementSelect();
      this.initElementSort();
      this.resetElements();

      if (this.$addElementBtn) {
        this.addListener(this.$addElementBtn, 'activate', 'showModal');
      }

      Garnish.requestAnimationFrame(() => {
        this._initialized = true;
      });
    },

    get totalSelected() {
      return this.$elements.length;
    },

    getContainer: function () {
      return $('#' + this.settings.id);
    },

    getFieldLabel: function () {
      if (!this.$container) return;

      const $fieldset = this.$container.closest('fieldset');
      return $fieldset.find('legend').first().data('label');
    },

    getElementsContainer: function () {
      return this.$container.children('.elements');
    },

    getElements: function () {
      return this.$elementsContainer.children();
    },

    getAddElementsBtn: function () {
      return this.$container.find('.btn.add:first');
    },

    getSpinner: function () {
      return this.$container.find('.spinner');
    },

    initElementSelect: function () {
      if (this.settings.selectable) {
        this.elementSelect = new Garnish.Select({
          multi: this.settings.sortable,
          filter: ':not(.delete)',
        });
      }
    },

    initElementSort: function () {
      if (this.settings.sortable) {
        this.elementSort = new Garnish.DragSort({
          container: this.$elementsContainer,
          filter: this.settings.selectable
            ? () => {
                // Only return all the selected items if the target item is selected
                if (this.elementSort.$targetItem.hasClass('sel')) {
                  return this.elementSelect.getSelectedItems();
                } else {
                  return this.elementSort.$targetItem;
                }
              }
            : null,
          ignoreHandleSelector: '.delete',
          axis: this.getElementSortAxis(),
          collapseDraggees: true,
          magnetStrength: 4,
          helperLagBase: 1.5,
          onSortChange: this.settings.selectable
            ? () => {
                this.elementSelect.resetItemOrder();
              }
            : null,
        });
      }
    },

    getElementSortAxis: function () {
      return this.settings.viewMode === 'list' ? 'y' : null;
    },

    canAddMoreElements: function () {
      return (
        !this.settings.limit || this.$elements.length < this.settings.limit
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
      if (this.$addElementBtn) {
        this.$addElementBtn.removeClass('hidden');
      }

      this.updateButtonContainer();
    },

    disableAddElementsBtn: function () {
      if (this.$addElementBtn) {
        this.$addElementBtn.addClass('hidden');
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
        this.$addElementBtn && this.$addElementBtn.parent('.flex');
      if ($container && $container.length) {
        if ($container.children(':not(.hidden)').length) {
          $container.removeClass('hidden');
        } else {
          $container.addClass('hidden');
        }
      }
    },

    focusNextLogicalElement: function () {
      if (this.canAddMoreElements()) {
        // If can add more elements, focus ADD button
        let $btn = this.$addElementBtn;

        if ($btn) {
          $btn.get(0).focus();
        }
      } else {
        // If can't add more elements, focus on the final remove
        this.focusLastRemoveBtn();
      }
    },

    focusLastRemoveBtn: function () {
      const $removeBtns = this.$container.find('.delete');

      if (!$removeBtns.length) return;

      $removeBtns.last()[0].focus();
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
      this.thumbLoader.load($elements);

      if (this.settings.selectable) {
        this.elementSelect.addItems($elements);
      }

      if (this.settings.sortable) {
        this.elementSort.addItems($elements);
      }

      if (this.settings.editable) {
        this._handleShowElementEditor = (ev) => {
          var $element = $(ev.currentTarget);
          if (
            Garnish.hasAttr($element, 'data-editable') &&
            !$element.hasClass('disabled') &&
            !$element.hasClass('loading')
          ) {
            this.elementEditor = this.createElementEditor($element);
          }
        };

        this.addListener($elements, 'dblclick', this._handleShowElementEditor);

        if ($.isTouchCapable()) {
          this.addListener($elements, 'taphold', this._handleShowElementEditor);
        }
      }

      $elements.find('.delete').on('click dblclick', (ev) => {
        this.removeElement($(ev.currentTarget).closest('.element'));
        // Prevent this from acting as one of a double-click
        ev.stopPropagation();
      });

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
    },

    createElementEditor: function ($element, settings) {
      settings = Object.assign(
        {
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

        const data = {
          elementId: replacementId,
          siteId: this.settings.criteria.siteId,
          thumbSize: this.settings.viewMode,
        };

        Craft.sendActionRequest('POST', 'elements/get-element-html', {data})
          .then((response) => {
            this.removeElement($existing);
            const elementInfo = Craft.getElementInfo(response.data.html);
            this.selectElements([elementInfo]);
            resolve();
          })
          .catch(({response}) => {
            if (response && response.data && response.data.message) {
              alert(response.data.message);
            } else {
              Craft.cp.displayError();
            }

            reject(response.data.message);
          })
          .finally(() => {
            this.hideSpinner();
          });
      });
    },

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

      this.$elements = this.$elements.not($elements);
      this.updateAddElementsBtn();

      this.onRemoveElements();
    },

    removeElement: function ($element) {
      this.removeElements($element);
      this.animateElementAway($element, () => {
        $element.remove();
      });
    },

    animateElementAway: function ($element, callback) {
      $element.css('z-index', 0);

      var animateCss = {
        opacity: -1,
      };
      animateCss['margin-' + Craft.left] = -(
        $element.outerWidth() + parseInt($element.css('margin-' + Craft.right))
      );

      if (this.settings.viewMode === 'list' || this.$elements.length === 0) {
        animateCss['margin-bottom'] = -(
          $element.outerHeight() + parseInt($element.css('margin-bottom'))
        );
      }

      // Pause the draft editor
      if (this.$form.data('elementEditor')) {
        this.$form.data('elementEditor').pause();
      }

      $element.velocity(
        animateCss,
        Craft.BaseElementSelectInput.REMOVE_FX_DURATION,
        () => {
          callback();

          // Resume the draft editor
          if (this.$form.data('elementEditor')) {
            this.$form.data('elementEditor').resume();
          }
        }
      );
    },

    showModal: function () {
      // Make sure we haven't reached the limit
      if (!this.canAddMoreElements()) {
        return;
      }

      if (!this.modal) {
        this.modal = this.createModal();
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
      return $.extend(
        {
          closeOtherModals: false,
          storageKey: this.modalStorageKey,
          sources: this.settings.sources,
          condition: this.settings.condition,
          criteria: this.settings.criteria,
          multiSelect: this.settings.limit != 1,
          showSiteMenu: this.settings.showSiteMenu,
          disabledElementIds: this.getDisabledElementIds(),
          onSelect: this.onModalSelect.bind(this),
          onHide: this.onModalHide.bind(this),
          triggerElement: this.$addElementBtn,
          modalTitle: Craft.t('app', 'Select {element}', {
            element: this.fieldLabel,
          }),
        },
        this.settings.modalSettings
      );
    },

    getSelectedElementIds: function () {
      var ids = [];

      for (var i = 0; i < this.$elements.length; i++) {
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

    onModalSelect: function (elements) {
      if (this.settings.limit) {
        // Cut off any excess elements
        var slotsLeft = this.settings.limit - this.$elements.length;

        if (elements.length > slotsLeft) {
          elements = elements.slice(0, slotsLeft);
        }
      }

      this.selectElements(elements);
      this.updateDisabledElementsInModal();
    },

    onModalHide: function () {
      // If can add more elements, do default behavior of focus on "Add" button
      if (this.canAddMoreElements()) return;

      setTimeout(() => {
        this.focusNextLogicalElement();
      }, 200);
    },

    selectElements: function (elements) {
      for (let i = 0; i < elements.length; i++) {
        let elementInfo = elements[i],
          $element = this.createNewElement(elementInfo);

        this.appendElement($element);
        this.addElements($element);
        this.animateElementIntoPlace(elementInfo.$element, $element);

        // Override the element reference with the new one
        elementInfo.$element = $element;
      }

      this.onSelectElements(elements);
    },

    createNewElement: function (elementInfo) {
      var $element = elementInfo.$element.clone();
      var removeText = Craft.t('app', 'Remove {label}', {
        label: elementInfo.label,
      });
      // Make a couple tweaks
      Craft.setElementSize(
        $element,
        this.settings.viewMode === 'large' ? 'large' : 'small'
      );
      $element.addClass('removable');
      $element.prepend(
        `<input type="hidden" name="${this.settings.name}${
          this.settings.single ? '' : '[]'
        }" value="${elementInfo.id}">` +
          '<button type="button" class="delete icon" title="' +
          Craft.t('app', 'Remove') +
          '" aria-label="' +
          removeText +
          '"></button>'
      );

      return $element;
    },

    appendElement: function ($element) {
      $element.appendTo(this.$elementsContainer);
    },

    animateElementIntoPlace: function ($modalElement, $inputElement) {
      var origOffset = $modalElement.offset(),
        destOffset = $inputElement.offset(),
        $helper = $inputElement.clone().appendTo(Garnish.$bod);

      $inputElement.css('visibility', 'hidden');

      $helper.css({
        position: 'absolute',
        zIndex: 10000,
        top: origOffset.top,
        left: origOffset.left,
      });

      var animateCss = {
        top: destOffset.top,
        left: destOffset.left,
      };

      $helper.velocity(
        animateCss,
        Craft.BaseElementSelectInput.ADD_FX_DURATION,
        function () {
          $helper.remove();
          $inputElement.css('visibility', 'visible');
        }
      );
    },

    updateDisabledElementsInModal: function () {
      if (this.modal.elementIndex) {
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
    },

    onRemoveElements: function () {
      this.trigger('removeElements');
      this.settings.onRemoveElements();
      this.focusNextLogicalElement();
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
      criteria: {},
      allowSelfRelations: false,
      sourceElementId: null,
      disabledElementIds: null,
      viewMode: 'list',
      single: false,
      limit: null,
      showSiteMenu: false,
      modalStorageKey: null,
      modalSettings: {},
      onAddElements: $.noop,
      onSelectElements: $.noop,
      onRemoveElements: $.noop,
      sortable: true,
      selectable: true,
      editable: true,
      prevalidate: false,
      editorSettings: {},
    },
  }
);
