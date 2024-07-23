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
    const includedSections = this.$sources
      .toArray()
      .map((source) => $(source).data('handle'))
      .filter((handle) => !!handle);
    this.publishableSections = Craft.publishableSections.filter((section) =>
      includedSections.includes(section.handle)
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
      // Remove the old button, if there is one
      if (this.$newEntryBtnGroup) {
        this.$newEntryBtnGroup.remove();
      }
      return;
    }

    let sectionHandle, entryTypeHandle;

    // Get the handle of the selected source
    if (this.$source.data('key') === 'singles') {
      sectionHandle = 'singles';
    } else {
      sectionHandle = this.$source.data('handle');
      entryTypeHandle = this.$source.data('entry-type');
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
        (s) => s.handle === sectionHandle
      );

      this.$newEntryBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
      let $menuBtn;
      const menuId = 'new-entry-menu-' + Craft.randomString(10);

      // check if any publishable sections are available for this site
      const publishableSectionsForSite = this.publishableSections.filter(
        (section) => section.sites.includes(this.siteId)
      );

      // If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
      // Otherwise only show a menu button
      if (selectedSection) {
        const visibleLabel =
          this.settings.context === 'index'
            ? Craft.t('app', 'New {type}', {
                type: Craft.elementTypeNames['craft\\elements\\Entry'][2],
              })
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

        this.addListener(this.$newEntryBtn, 'click mousedown', (ev) => {
          // If this is the element index, check for Ctrl+clicks and middle button clicks
          if (
            this.settings.context === 'index' &&
            ((ev.type === 'click' && Garnish.isCtrlKeyPressed(ev)) ||
              (ev.type === 'mousedown' && ev.originalEvent.button === 1))
          ) {
            const params = {};
            if (entryTypeHandle) {
              params.type = entryTypeHandle;
            }
            window.open(
              Craft.getUrl(`entries/${selectedSection.handle}/new`, params)
            );
          } else if (ev.type === 'click') {
            this._createEntry(selectedSection.id, entryTypeHandle);
          }
        });

        if (publishableSectionsForSite.length > 1) {
          $menuBtn = $('<button/>', {
            type: 'button',
            class: 'btn submit menubtn btngroup-btn-last',
            'aria-controls': menuId,
            'data-disclosure-trigger': '',
            'aria-label': Craft.t('app', 'New entry, choose a section'),
          }).appendTo(this.$newEntryBtnGroup);
        }
      } else if (publishableSectionsForSite.length > 0) {
        // only add the New Entry button if there are any sections for this site
        this.$newEntryBtn = $menuBtn = Craft.ui
          .createButton({
            label: Craft.t('app', 'New {type}', {
              type: Craft.elementTypeNames['craft\\elements\\Entry'][2],
            }),
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
              section.sites.includes(this.siteId)) ||
            (this.settings.context !== 'index' &&
              section !== selectedSection &&
              section.sites.includes(this.siteId))
          ) {
            const $li = $('<li/>').appendTo($ul);
            const $a = $('<a/>', {
              role: anchorRole === 'button' ? 'button' : null,
              href: Craft.getUrl(`entries/${section.handle}/new`),
              type: anchorRole === 'button' ? 'button' : null,
              text: Craft.t('app', 'New {section} entry', {
                section: section.name,
              }),
            }).appendTo($li);
            this.addListener($a, 'activate', () => {
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

      if (sectionHandle) {
        uri += '/' + sectionHandle;
      }

      Craft.setPath(uri);
    }
  },

  _createEntry: function (sectionId, entryTypeHandle) {
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
        type: entryTypeHandle,
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
            this.clearSearch();
            this.setSelectedSortAttribute('dateCreated', 'desc');
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
