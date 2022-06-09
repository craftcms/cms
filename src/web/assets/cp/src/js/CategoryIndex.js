/** global: Craft */
/** global: Garnish */
/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend({
  editableGroups: null,
  $newCategoryBtnGroup: null,
  $newCategoryBtn: null,

  init: function (elementType, $container, settings) {
    this.editableGroups = [];
    this.on('selectSource', this.updateButton.bind(this));
    this.on('selectSite', this.updateButton.bind(this));
    this.base(elementType, $container, settings);
  },

  afterInit: function () {
    // Find which of the visible groups the user has permission to create new categories in
    this.editableGroups = Craft.editableCategoryGroups.filter(
      (g) => !!this.getSourceByKey(`group:${g.uid}`)
    );

    this.base();
  },

  getDefaultSourceKey: function () {
    // Did they request a specific category group in the URL?
    if (
      this.settings.context === 'index' &&
      typeof defaultGroupHandle !== 'undefined'
    ) {
      for (let i = 0; i < this.$sources.length; i++) {
        const $source = $(this.$sources[i]);
        if ($source.data('handle') === defaultGroupHandle) {
          return $source.data('key');
        }
      }
    }

    return this.base();
  },

  updateButton: function () {
    if (!this.$source) {
      return;
    }

    // Get the handle of the selected source
    const selectedSourceHandle = this.$source.data('handle');

    // Update the New Category button
    // ---------------------------------------------------------------------

    if (this.editableGroups.length) {
      // Remove the old button, if there is one
      if (this.$newCategoryBtnGroup) {
        this.$newCategoryBtnGroup.remove();
      }

      // Determine if they are viewing a group that they have permission to create categories in
      const selectedGroup = this.editableGroups.find(
        (g) => g.handle === selectedSourceHandle
      );

      this.$newCategoryBtnGroup = $(
        '<div class="btngroup submit" data-wrapper/>'
      );
      let $menuBtn;
      const menuId = 'new-category-menu-' + Craft.randomString(10);

      // If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
      // Otherwise only show a menu button
      if (selectedGroup) {
        const visibleLabel =
          this.settings.context === 'index'
            ? Craft.t('app', 'New category')
            : Craft.t('app', 'New {group} category', {
                group: selectedGroup.name,
              });
        const ariaLabel =
          this.settings.context === 'index'
            ? Craft.t('app', 'New category in the {group} category group', {
                group: selectedGroup.name,
              })
            : visibleLabel;

        const role = this.settings.context === 'index' ? 'link' : null;

        this.$newCategoryBtn = Craft.ui
          .createButton({
            label: visibleLabel,
            ariaLabel: ariaLabel,
            spinner: true,
            role: role,
          })
          .addClass('submit add icon')
          .appendTo(this.$newCategoryBtnGroup);

        this.addListener(this.$newCategoryBtn, 'click', () => {
          this._createCategory(selectedGroup.id);
        });

        if (this.editableGroups.length > 1) {
          $menuBtn = $('<button/>', {
            type: 'button',
            class: 'btn submit menubtn btngroup-btn-last',
            'aria-controls': menuId,
            'data-disclosure-trigger': '',
            'aria-label': Craft.t(
              'app',
              'New category, choose a category group'
            ),
          }).appendTo(this.$newCategoryBtnGroup);
        }
      } else {
        this.$newCategoryBtn = $menuBtn = Craft.ui
          .createButton({
            label: Craft.t('app', 'New category'),
            ariaLabel: Craft.t('app', 'New category, choose a category group'),
            spinner: true,
          })
          .addClass('submit add icon menubtn btngroup-btn-last')
          .attr('aria-controls', menuId)
          .attr('data-disclosure-trigger', '')
          .appendTo(this.$newCategoryBtnGroup);
      }

      this.addButton(this.$newCategoryBtnGroup);

      if ($menuBtn) {
        const $menuContainer = $('<div/>', {
          id: menuId,
          class: 'menu menu--disclosure',
        }).appendTo(this.$newCategoryBtnGroup);
        const $ul = $('<ul/>').appendTo($menuContainer);

        for (const group of this.editableGroups) {
          const anchorRole =
            this.settings.context === 'index' ? 'link' : 'button';
          if (this.settings.context === 'index' || group !== selectedGroup) {
            const $li = $('<li/>').appendTo($ul);
            const $a = $('<a/>', {
              role: anchorRole === 'button' ? 'button' : null,
              href: '#', // Allows for click listener and tab order
              type: anchorRole === 'button' ? 'button' : null,
              text: Craft.t('app', 'New {group} category', {
                group: group.name,
              }),
            }).appendTo($li);
            this.addListener($a, 'click', () => {
              $menuBtn.data('trigger').hide();
              this._createCategory(group.id);
            });

            if (anchorRole === 'button') {
              this.addListener($a, 'keydown', (event) => {
                if (event.keyCode === Garnish.SPACE_KEY) {
                  event.preventDefault();
                  $menuBtn.data('trigger').hide();
                  this._createCategory(group.id);
                }
              });
            }
          }
        }

        new Garnish.DisclosureMenu($menuBtn);
      }
    }

    // Update the URL if we're on the Categories index
    // ---------------------------------------------------------------------

    if (this.settings.context === 'index') {
      let uri = 'categories';

      if (selectedSourceHandle) {
        uri += '/' + selectedSourceHandle;
      }

      Craft.setPath(uri);
    }
  },

  _createCategory: function (groupId) {
    if (this.$newCategoryBtn.hasClass('loading')) {
      console.warn('New category creation already in progress.');
      return;
    }

    // Find the group
    const group = this.editableGroups.find((s) => s.id === groupId);

    if (!group) {
      throw `Invalid category group ID: ${groupId}`;
    }

    this.$newCategoryBtn.addClass('loading');

    Craft.sendActionRequest('POST', 'elements/create', {
      data: {
        elementType: this.elementType,
        siteId: this.siteId,
        groupId: groupId,
      },
    })
      .then((ev) => {
        if (this.settings.context === 'index') {
          document.location.href = Craft.getUrl(ev.data.cpEditUrl, {fresh: 1});
        } else {
          const slideout = Craft.createElementEditor(this.elementType, {
            siteId: this.siteId,
            elementId: ev.data.element.id,
            draftId: ev.data.element.draftId,
            params: {
              fresh: 1,
            },
          });
          slideout.on('submit', () => {
            // Make sure the right group is selected
            const groupSourceKey = `group:${group.uid}`;

            if (this.sourceKey !== groupSourceKey) {
              this.selectSourceByKey(groupSourceKey);
            }

            this.clearSearch();
            this.selectElementAfterUpdate(ev.data.element.id);
            this.updateElements();
          });
        }
      })
      .finally(() => {
        this.$newCategoryBtn.removeClass('loading');
      });
  },
});

// Register it!
Craft.registerElementIndexClass(
  'craft\\elements\\Category',
  Craft.CategoryIndex
);
