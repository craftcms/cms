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

    hasSelection: function () {
      return (
        this.base() ||
        (this.elementIndex &&
          this.elementIndex.sourcePath.length &&
          typeof this.elementIndex.sourcePath[
            this.elementIndex.sourcePath.length - 1
          ].folderId !== 'undefined' &&
          !this.settings.disabledFolderIds.includes(
            this.elementIndex.sourcePath[
              this.elementIndex.sourcePath.length - 1
            ].folderId
          ))
      );
    },

    getElementInfo: function ($selectedElements) {
      return [
        {
          folderId: $selectedElements.length
            ? parseInt(
                $selectedElements.find('.element:first').data('folder-id')
              )
            : this.elementIndex.sourcePath[
                this.elementIndex.sourcePath.length - 1
              ].folderId,
        },
      ];
    },

    getIndexSettings: function () {
      return Object.assign(this.base(), {
        foldersOnly: true,
        canSelectElement: ($element) => {
          const folderId = $element.find('.element:first').data('folder-id');
          return (
            folderId && !this.settings.disabledFolderIds.includes(folderId)
          );
        },
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
