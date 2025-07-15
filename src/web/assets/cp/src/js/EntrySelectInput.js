/** global: Craft */
/** global: Garnish */
/**
 * Entry select input
 */
Craft.EntrySelectInput = Craft.BaseElementSelectInput.extend({
  get section() {
    if (!this.settings.sectionId) {
      return null;
    }
    return Craft.publishableSections.find(
      (s) => s.id === this.settings.sectionId
    );
  },

  canCreateElements: function () {
    return !!this.section;
  },

  createElement: async function (title) {
    const response = await Craft.sendActionRequest('POST', 'entries/create', {
      data: {
        siteId: this.settings.criteria.siteId,
        section: this.section.handle,
        authorId: Craft.userId,
        title,
      },
    });

    const entry = response.data.entry;

    try {
      await this.showElementEditor(entry);
    } catch (e) {
      return null;
    }

    return entry.id;
  },

  showElementEditor: function (entry) {
    return new Promise((resolve, reject) => {
      const slideout = Craft.createElementEditor('craft\\elements\\Entry', {
        siteId: this.settings.criteria.siteId,
        elementId: entry.id,
        draftId: entry.draftId,
        params: {
          fresh: 1,
        },
      });

      let submitted = false;

      slideout.on('submit', () => {
        submitted = true;
        resolve();
      });

      slideout.on('close', () => {
        if (!submitted) {
          reject();
        }
      });
    });
  },
});
