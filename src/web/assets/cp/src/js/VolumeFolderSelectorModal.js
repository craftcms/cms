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
      if (this.base()) {
        return true;
      }

      // If nothing's selected, allow selecting the current folder,
      // as long as it’s not disabled
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
      if (this.hasSelection()) {
        this.base();
        return;
      }

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

    getElementInfo: function ($selectedElements) {
      const info = [];
      for (let i = 0; i < $selectedElements.length; i++) {
        const $element = $selectedElements.eq(i).find('.element:first');
        const folderId = parseInt($element.data('folder-id'));
        info.push({folderId});
      }
      return info;
    },

    getIndexSettings: function () {
      return Object.assign(this.base(), {
        foldersOnly: true,
        viewSettings: () => ({
          canSelectElement: ($element) => {
            $element = $element.find('.element:first');
            return Garnish.hasAttr($element, 'data-folder-id');
          },
        }),
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
