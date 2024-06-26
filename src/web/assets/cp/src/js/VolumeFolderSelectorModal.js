/** global: Craft */
/** global: Garnish */
/**
 * Volume folder selector modal class
 */
Craft.VolumeFolderSelectorModal = Craft.BaseElementSelectorModal.extend(
  {
    init: function (settings) {
      settings = Object.assign(
        {},
        Craft.VolumeFolderSelectorModal.defaults,
        settings,
        {
          showSiteMenu: false,
        }
      );
      settings.indexSettings.disabledFolderIds = settings.disabledFolderIds;
      this.base('craft\\elements\\Asset', settings);
    },

    getElementIndexParams: function () {
      return Object.assign({}, this.base(), {
        foldersOnly: true,
      });
    },

    shouldEnableSelectBtn: function () {
      // Allow selecting the current folder, as long as it’s not disabled
      return (
        this.elementIndex?.sourcePath.length &&
        typeof this.elementIndex.sourcePath[
          this.elementIndex.sourcePath.length - 1
        ].folderId !== 'undefined' &&
        !this.settings.disabledFolderIds.includes(
          this.elementIndex.sourcePath[this.elementIndex.sourcePath.length - 1]
            .folderId
        )
      );
    },

    selectElements: function (ev) {
      if (
        this.$selectBtn &&
        ev?.currentTarget === this.$selectBtn[0] &&
        this.shouldEnableSelectBtn()
      ) {
        const {folderId} =
          this.elementIndex.sourcePath[this.elementIndex.sourcePath.length - 1];
        this.onSelect([{folderId}]);

        if (this.settings.hideOnSelect) {
          this.hide();
        }
      }
    },

    getIndexSettings: function () {
      return Object.assign(this.base(), {
        foldersOnly: true,
      });
    },
  },
  {
    defaults: {
      disabledFolderIds: [],
      indexSettings: {},
    },
  }
);
