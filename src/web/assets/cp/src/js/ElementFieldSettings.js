/** global: Craft */
/** global: Garnish */
Craft.ElementFieldSettings = Garnish.Base.extend({
  $maintainHierarchyInput: null,
  $sourcesInput: null,
  $branchLimitInput: null,
  $maxRelationsInput: null,
  $minRelationsInput: null,

  init: function (
    maintainHierarchyId,
    sourcesId,
    branchLimitId,
    minRelationsId,
    maxRelationsId
  ) {
    this.$maintainHierarchyInput = $('#' + maintainHierarchyId);
    this.$sourcesInput = $(`#${sourcesId}`);
    this.$branchLimitInput = $('#' + branchLimitId);
    this.$minRelationsInput = $('#' + minRelationsId);
    this.$maxRelationsInput = $('#' + maxRelationsId);

    this.updateLimitFields();
    this.addListener(
      this.$maintainHierarchyInput,
      'change',
      'updateLimitFields'
    );

    if (this.$sourcesInput.length) {
      this.updateMaintainHierarchyField();
      this.$sourcesInput.find('[type=checkbox]').each(
        function (index, checkbox) {
          this.addListener(
            $(checkbox),
            'change',
            'updateMaintainHierarchyField'
          );
        }.bind(this)
      );
    }
  },
  updateLimitFields: function () {
    if (this.$maintainHierarchyInput.is(':checked')) {
      this.$minRelationsInput.closest('.field').hide();
      this.$maxRelationsInput.closest('.field').hide();
      this.$branchLimitInput.closest('.field').show();
    } else {
      this.$branchLimitInput.closest('.field').hide();
      this.$minRelationsInput.closest('.field').show();
      this.$maxRelationsInput.closest('.field').show();
    }
  },
  updateMaintainHierarchyField: function () {
    const $checkedInputs = this.$sourcesInput.find('[type="checkbox"]:checked');
    const enableInput =
      $checkedInputs.length === 1 && $checkedInputs.data('structure-id');

    if (enableInput) {
      this.$maintainHierarchyInput.prop('disabled', false);
      this.$maintainHierarchyInput
        .closest('.field')
        .find('.instructions')
        .removeClass('disabled');
    } else {
      this.$maintainHierarchyInput.prop('disabled', true);
      this.$maintainHierarchyInput
        .closest('.field')
        .find('.instructions')
        .addClass('disabled');
      this.$maintainHierarchyInput.prop('checked', false);
      this.$maintainHierarchyInput.trigger('change');
    }
  },
});
