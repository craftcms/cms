/** global: Craft */
/** global: Garnish */
/**
 * Volume folder selector modal class
 */
Craft.VolumeFolderSelectorModal = Craft.BaseElementSelectorModal.extend({
  init: function (settings) {
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
        ].folderId !== 'undefined')
    );
  },

  getElementInfo: function ($selectedElements) {
    return [
      {
        folderId: $selectedElements.length
          ? parseInt($selectedElements.find('.element:first').data('folder-id'))
          : this.elementIndex.sourcePath[
              this.elementIndex.sourcePath.length - 1
            ].folderId,
      },
    ];
  },

  getIndexSettings: function () {
    return Object.assign(this.base(), {
      foldersOnly: true,
    });
  },
});
