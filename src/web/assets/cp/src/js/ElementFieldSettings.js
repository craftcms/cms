/** global: Craft */
/** global: Garnish */
Craft.ElementFieldSettings = Garnish.Base.extend({
  allowMultipleSources: null,
  $maintainHierarchyField: null,
  $maintainHierarchyButton: null,
  $sourcesField: null,
  $sourceSelect: null,
  $branchLimitField: null,
  $maxRelationsField: null,
  $minRelationsField: null,
  $defaultPlacementField: null,
  $viewModeField: null,

  init: function (
    allowMultipleSources,
    maintainHierarchyFieldId,
    sourcesFieldId,
    branchLimitFieldId,
    minRelationsFieldId,
    maxRelationsFieldId,
    defaultPlacementFieldId,
    viewModeFieldId
  ) {
    this.allowMultipleSources = allowMultipleSources;
    this.$maintainHierarchyField = $(`#${maintainHierarchyFieldId}`);
    this.$maintainHierarchyButton = this.$maintainHierarchyField.find('button');
    this.$sourcesField = $(`#${sourcesFieldId}`);
    if (!this.allowMultipleSources) {
      this.$sourceSelect = this.$sourcesField.find('select');
    }
    this.$branchLimitField = $(`#${branchLimitFieldId}`);
    this.$minRelationsField = $(`#${minRelationsFieldId}`);
    this.$maxRelationsField = $(`#${maxRelationsFieldId}`);
    this.$defaultPlacementField = $(`#${defaultPlacementFieldId}`);
    this.$viewModeField = $(`#${viewModeFieldId}`);

    this.updateLimitFields();
    this.addListener(
      this.$maintainHierarchyButton,
      'change',
      'updateLimitFields'
    );

    if (this.allowMultipleSources) {
      this.$sourcesField.find('[type=checkbox]').each(
        function (index, checkbox) {
          this.addListener(
            $(checkbox),
            'change',
            'updateMaintainHierarchyField'
          );
        }.bind(this)
      );
    } else {
      this.addListener(
        this.$sourceSelect,
        'change',
        'updateMaintainHierarchyField'
      );
    }
    this.updateMaintainHierarchyField();
  },

  updateLimitFields: function () {
    if (
      !this.$maintainHierarchyField.hasClass('hidden') &&
      this.$maintainHierarchyButton.hasClass('on')
    ) {
      this.$minRelationsField.addClass('hidden');
      this.$maxRelationsField.addClass('hidden');
      this.$branchLimitField.removeClass('hidden');
      this.$defaultPlacementField.addClass('hidden');
      this.$viewModeField.find('select').val('list').trigger('change');
      this.$viewModeField.addClass('hidden');
    } else {
      this.$branchLimitField.addClass('hidden');
      this.$minRelationsField.removeClass('hidden');
      this.$maxRelationsField.removeClass('hidden');
      this.$defaultPlacementField.removeClass('hidden');
      this.$viewModeField.removeClass('hidden');
    }
  },

  updateMaintainHierarchyField: function () {
    let showField;
    if (this.allowMultipleSources) {
      const $checkedInputs = this.$sourcesField.find(
        '[type="checkbox"]:checked'
      );
      showField =
        $checkedInputs.length === 1 && $checkedInputs.data('structure-id');
    } else {
      showField = this.$sourceSelect
        .children('option:selected')
        .data('structure-id');
    }

    if (showField) {
      this.$maintainHierarchyField.removeClass('hidden');
    } else {
      this.$maintainHierarchyField.addClass('hidden');
    }

    this.updateLimitFields();
  },
});
