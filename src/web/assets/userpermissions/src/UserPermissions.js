import './user-permissions.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.UserPermissions = Garnish.Base.extend({
    $wrapper: null,
    $selectAllBtn: null,
    $allCheckboxes: null,

    init: function (wrapper) {
      this.$wrapper = $(wrapper);
      this.$selectAllBtn = this.$wrapper.find('.select-all');
      this.$allCheckboxes = this.$wrapper.find('input[type=checkbox]');

      this.addListener(this.$selectAllBtn, 'click', 'toggleSelectAll');
      this.addListener(this.$allCheckboxes, 'click', 'toggleCheckbox');
      this.updateSelectAllBtn();
    },

    toggleSelectAll: function (ev) {
      if (this.canSelectAll()) {
        this.$allCheckboxes.filter(':not(:checked)').trigger('click');
      } else {
        this.$allCheckboxes.filter(':checked').trigger('click');
      }

      ev.preventDefault();
    },

    toggleCheckbox: function (ev) {
      let $checkbox = $(ev.currentTarget);
      if ($checkbox.prop('disabled')) {
        ev.preventDefault();
        return;
      }

      let $childrenCheckboxes = $checkbox
        .parent('li')
        .find('> ul > li > input[type=checkbox]');

      if ($checkbox.prop('checked')) {
        $childrenCheckboxes.prop('disabled', false);
      } else {
        $childrenCheckboxes.filter(':checked').trigger('click');
        $childrenCheckboxes.prop('disabled', true);
      }

      this.updateSelectAllBtn();
    },

    updateSelectAllBtn: function () {
      if (this.canSelectAll()) {
        this.$selectAllBtn.text(Craft.t('app', 'Select All'));
      } else {
        this.$selectAllBtn.text(Craft.t('app', 'Deselect All'));
      }
    },

    canSelectAll: function () {
      return !!this.$allCheckboxes.filter(':not(:checked)').length;
    },
  });
})(jQuery);
