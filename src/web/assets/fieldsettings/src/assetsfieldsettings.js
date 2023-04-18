Craft.AssetsFieldSettings = Garnish.Base.extend({
  adjustDefaultAssetLocation: function (defaultUploadLocationSource) {
    if (defaultUploadLocationSource.length === 0) {
      let sourcesFields = $('fieldset[id$="-sources-field"]');
      if (sourcesFields.length) {
        let sourcesCheckboxes = sourcesFields.find('input.checkbox');

        this.addListener(sourcesCheckboxes, 'change', 'selectFirstSelected');
      }
    }
  },

  selectFirstSelected: function (ev) {
    let sourcesField = $(ev.currentTarget).parents(
      'fieldset[id$="-sources-field"]'
    );
    let defaultAssetLocationField = sourcesField
      .parent()
      .find('select[name$="[defaultUploadLocationSource]"]');
    let checkedSources = sourcesField.find('input:checked');

    $(defaultAssetLocationField)
      .find('option:selected')
      .prop('selected', false);

    if (checkedSources.length > 0) {
      $(defaultAssetLocationField)
        .find('option[value="' + $(checkedSources[0]).val() + '"]')
        .prop('selected', 'selected');
    } else {
      $(defaultAssetLocationField)
        .find('option:first')
        .prop('selected', 'selected');
    }
  },
});

Craft.assetsFieldSettings = new Craft.AssetsFieldSettings();
