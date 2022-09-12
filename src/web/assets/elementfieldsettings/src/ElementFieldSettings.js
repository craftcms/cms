(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.ElementFieldSettings = Garnish.Base.extend({
    $relatedAncestorsInput: null,
    $sourcesInput: null,
    $branchLimitInput: null,
    $maxRelationsInput: null,
    $minRelationsInput: null,

    init: function (
      relateAncestorsId,
      sourcesId,
      branchLimitId,
      minRelationsId,
      maxRelationsId
    ) {
      this.$relatedAncestorsInput = $('#' + relateAncestorsId);
      this.$sourcesInput = $(`#${sourcesId}`);
      this.$branchLimitInput = $('#' + branchLimitId);
      this.$minRelationsInput = $('#' + minRelationsId);
      this.$maxRelationsInput = $('#' + maxRelationsId);

      this.updateLimitFields();
      this.addListener(
        this.$relatedAncestorsInput,
        'change',
        'updateLimitFields'
      );

      if (this.$sourcesInput.length) {
        this.updateRelateAncestorsField();
        this.$sourcesInput.find('[type=checkbox]').each(
          function (index, checkbox) {
            this.addListener(
              $(checkbox),
              'change',
              'updateRelateAncestorsField'
            );
          }.bind(this)
        );
      }
    },
    updateLimitFields: function () {
      if (this.$relatedAncestorsInput.is(':checked')) {
        this.$minRelationsInput.closest('.field').hide();
        this.$maxRelationsInput.closest('.field').hide();
        this.$branchLimitInput.closest('.field').show();
      } else {
        this.$branchLimitInput.closest('.field').hide();
        this.$minRelationsInput.closest('.field').show();
        this.$maxRelationsInput.closest('.field').show();
      }
    },
    updateRelateAncestorsField: function () {
      let checkedInputs = this.$sourcesInput.find('[type="checkbox"]:checked');
      let disableRelatAncestors = false;

      if (checkedInputs.length > 1) {
        disableRelatAncestors = true;
      }

      if (disableRelatAncestors) {
        this.$relatedAncestorsInput.prop('disabled', true);
        this.$relatedAncestorsInput
          .closest('.field')
          .find('.instructions')
          .addClass('disabled');
        this.$relatedAncestorsInput.prop('checked', false);
        this.$relatedAncestorsInput.trigger('change');
      } else {
        this.$relatedAncestorsInput.prop('disabled', false);
        this.$relatedAncestorsInput
          .closest('.field')
          .find('.instructions')
          .removeClass('disabled');
      }
    },
  });
})(jQuery);
