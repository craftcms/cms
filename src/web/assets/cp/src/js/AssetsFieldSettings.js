/** global: Craft */
/** global: Garnish */
/**
 * Assets field settings
 */
Craft.AssetsFieldSettings = Garnish.Base.extend({
  $useSingleFolderInput: null,
  $sourceInputs: null,
  $defaultUploadLocationSelect: null,
  $defaultUploadLocationOptions: null,

  init: function (useSingleFolderToggleId, sourcesFieldId, defaultUploadLocationId) {
    this.$useSingleFolderInput = $(`#${useSingleFolderToggleId}`);
    this.$sourceInputs = $(`#${sourcesFieldId} input`);
    this.$defaultUploadLocationSelect = $(`#${defaultUploadLocationId}`);
    this.$defaultUploadLocationOptions = this.$defaultUploadLocationSelect.children('option');
    this.updateDefaultUploadLocationSelect();

    // Give CheckboxSelect a chance to register its change event first
    Garnish.requestAnimationFrame(() => {
      this.addListener(this.$useSingleFolderInput, 'change', 'updateDefaultUploadLocationSelect');
      this.addListener(this.$sourceInputs, 'change', 'updateDefaultUploadLocationSelect');
    });
  },

  updateDefaultUploadLocationSelect: function() {
    if (this.$useSingleFolderInput.prop('checked')) {
      return;
    }

    const defaultUploadLocationVal = this.$defaultUploadLocationSelect.val();
    let firstEnabledValue;

    for (let i = 0; i < this.$sourceInputs.length; i++) {
      const $input = this.$sourceInputs.eq(i);
      const val = $input.val();
      const checked = $input.prop('checked');
      if (val === '*') {
        if (checked) {
          this.$defaultUploadLocationOptions.prop('disabled', false);
          return;
        }
      } else {
        this.getOption(val).prop('disabled', !checked);
        if (checked && !firstEnabledValue) {
          firstEnabledValue = val;
        }
      }
    }

    const $selectedOption = this.getOption(defaultUploadLocationVal);
    if (!$selectedOption.length || $selectedOption.prop('disabled') && firstEnabledValue) {
      this.$defaultUploadLocationSelect.val(firstEnabledValue);
    }
  },

  getOption: function(val) {
    return this.$defaultUploadLocationOptions.filter(`[value="${val}"]`);
  }
});
