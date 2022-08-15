/** global: Craft */
/** global: Garnish */
/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend({
  publishableSections: null,
  $newEntryBtnGroup: null,
  $newEntryBtn: null,

  init: function (elementType, $container, settings) {
    this.publishableSections = [];
    this.on('selectSource', this.updateButton.bind(this));
    this.on('selectSite', this.updateButton.bind(this));
    this.base(elementType, $container, settings);
  },

  afterInit: function () {
    // Find which of the visible sections the user has permission to create new entries in
    this.publishableSections = Craft.publishableSections.filter(
      (s) => !!this.getSourceByKey(`section:${s.uid}`)
    );

    this.base();
  },

  getDefaultSourceKey: function () {
    // Did they request a specific section in the URL?
    if (
      this.settings.context === 'index' &&
      typeof defaultSectionHandle !== 'undefined'
    ) {
      if (defaultSectionHandle === 'singles') {
        return 'singles';
      }

      for (let i = 0; i < this.$sources.length; i++) {
        const $source = $(this.$sources[i]);
        if ($source.data('handle') === defaultSectionHandle) {
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

    let handle;

    // Get the handle of the selected source
    if (this.$source.data('key') === 'singles') {
      handle = 'singles';
    } else {
      handle = this.$source.data('handle');
    }

    // Update the New Entry button
    // ---------------------------------------------------------------------

    if (this.publishableSections.length) {
      // Remove the old button, if there is one
      if (this.$newEntryBtnGroup) {
        this.$newEntryBtnGroup.remove();
      }

      // Determine if they are viewing a section that they have permission to create entries in
      const selectedSection = this.publishableSections.find(
        (s) => s.handle === handle
      );

      this.$newEntryBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
      let $menuBtn;
      const menuId = 'new-entry-menu-' + Craft.randomString(10);

      // If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
      // Otherwise only show a menu button
      if (selectedSection) {
        const visibleLabel =
          this.settings.context === 'index'
            ? Craft.t('app', 'New entry')
            : Craft.t('app', 'New {section} entry', {
                section: selectedSection.name,
              });

        const ariaLabel =
          this.settings.context === 'index'
            ? Craft.t('app', 'New entry in the {section} section', {
                section: selectedSection.name,
              })
            : visibleLabel;

        // In index contexts, the button functions as a link
        // In non-index contexts, the button triggers a slideout editor
        const role = this.settings.context === 'index' ? 'link' : null;

        this.$newEntryBtn = Craft.ui
          .createButton({
            label: visibleLabel,
            ariaLabel: ariaLabel,
            spinner: true,
            role: role,
          })
          .addClass('submit add icon')
          .appendTo(this.$newEntryBtnGroup);

        this.addListener(this.$newEntryBtn, 'click', () => {
          this._createEntry(selectedSection.id);
        });

        if (this.publishableSections.length > 1) {
          $menuBtn = $('<button/>', {
            type: 'button',
            class: 'btn submit menubtn btngroup-btn-last',
            'aria-controls': menuId,
            'data-disclosure-trigger': '',
            'aria-label': Craft.t('app', 'New entry, choose a section'),
          }).appendTo(this.$newEntryBtnGroup);
        }
      } else {
        this.$newEntryBtn = $menuBtn = Craft.ui
          .createButton({
            label: Craft.t('app', 'New entry'),
            ariaLabel: Craft.t('app', 'New entry, choose a section'),
            spinner: true,
          })
          .addClass('submit add icon menubtn btngroup-btn-last')
          .attr('aria-controls', menuId)
          .attr('data-disclosure-trigger', '')
          .appendTo(this.$newEntryBtnGroup);
      }

      this.addButton(this.$newEntryBtnGroup);

      if ($menuBtn) {
        const $menuContainer = $('<div/>', {
          id: menuId,
          class: 'menu menu--disclosure',
        }).appendTo(this.$newEntryBtnGroup);
        const $ul = $('<ul/>').appendTo($menuContainer);

        for (const section of this.publishableSections) {
          const anchorRole =
            this.settings.context === 'index' ? 'link' : 'button';
          if (
            (this.settings.context === 'index' &&
              $.inArray(this.siteId, section.sites) !== -1) ||
            (this.settings.context !== 'index' && section !== selectedSection)
          ) {
            const $li = $('<li/>').appendTo($ul);
            const $a = $('<a/>', {
              role: anchorRole === 'button' ? 'button' : null,
              href: '#', // Allows for click listener and tab order
              type: anchorRole === 'button' ? 'button' : null,
              text: Craft.t('app', 'New {section} entry', {
                section: section.name,
              }),
            }).appendTo($li);
            this.addListener($a, 'click', () => {
              $menuBtn.data('trigger').hide();
              this._createEntry(section.id);
            });

            if (anchorRole === 'button') {
              this.addListener($a, 'keydown', (event) => {
                if (event.keyCode === Garnish.SPACE_KEY) {
                  event.preventDefault();
                  $menuBtn.data('trigger').hide();
                  this._createEntry(section.id);
                }
              });
            }
          }
        }

        new Garnish.DisclosureMenu($menuBtn);
      }
    }

    // Update the URL if we're on the Entries index
    // ---------------------------------------------------------------------

    if (this.settings.context === 'index') {
      let uri = 'entries';

      if (handle) {
        uri += '/' + handle;
      }

      Craft.setPath(uri);
    }
  },

  _createEntry: function (sectionId) {
    if (this.$newEntryBtn.hasClass('loading')) {
      console.warn('New entry creation already in progress.');
      return;
    }

    // Find the section
    const section = this.publishableSections.find((s) => s.id === sectionId);

    if (!section) {
      throw `Invalid section ID: ${sectionId}`;
    }

    this.$newEntryBtn.addClass('loading');

    Craft.sendActionRequest('POST', 'entries/create', {
      data: {
        siteId: this.siteId,
        section: section.handle,
      },
    })
      .then(({data}) => {
        if (this.settings.context === 'index') {
          document.location.href = Craft.getUrl(data.cpEditUrl, {fresh: 1});
        } else {
          const slideout = Craft.createElementEditor(this.elementType, {
            siteId: this.siteId,
            elementId: data.entry.id,
            draftId: data.entry.draftId,
            params: {
              fresh: 1,
            },
          });
          slideout.on('submit', () => {
            // Make sure the right section is selected
            const sectionSourceKey = `section:${section.uid}`;

            if (this.sourceKey !== sectionSourceKey) {
              this.selectSourceByKey(sectionSourceKey);
            }

            this.clearSearch();
            this.setSortAttribute('dateCreated');
            this.setSortDirection('desc');
            this.selectElementAfterUpdate(data.entry.id);
            this.updateElements();
          });
        }
      })
      .finally(() => {
        this.$newEntryBtn.removeClass('loading');
      });
  },
});

// Register it!
Craft.registerElementIndexClass('craft\\elements\\Entry', Craft.EntryIndex);
