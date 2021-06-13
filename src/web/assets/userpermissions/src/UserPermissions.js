(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.UserPermissions = Garnish.Base.extend({
        $wrapper: null,
        $selectAllBtn: null,
        $allCheckboxes: null,

        init: function(wrapper) {
            this.$wrapper = wrapper;
            this.$selectAllBtn = $('.select-all', this.$wrapper);
            this.$allCheckboxes = $('input[type=checkbox]:not(.group-permission)', this.$wrapper);

            this.addListener(this.$selectAllBtn, 'click', 'toggleSelectAll');
            this.addListener(this.$allCheckboxes, 'click', 'toggleCheckbox');
            this.updateSelectAllBtn();
        },

        toggleSelectAll: function(ev) {
            if (this.canSelectAll()) {
                this.$allCheckboxes.filter(':not(:checked)').trigger('click');
            } else {
                this.$allCheckboxes.filter(':checked').trigger('click');
            }

            ev.preventDefault();
        },

        toggleCheckbox: function(ev) {
            let $checkbox = $(ev.currentTarget);
            if ($checkbox.prop('disabled')) {
                ev.preventDefault();
                return;
            }

            let $uls = $checkbox.parent('li').find('> ul');
            let $childrenCheckboxes = $checkbox.parent('li').find('> ul > li > input[type=checkbox]:not(.group-permission)');

            if ($checkbox.prop('checked')) {
                $childrenCheckboxes.prop('disabled', false);
            } else {
                $childrenCheckboxes.filter(':checked').trigger('click');
                $childrenCheckboxes.prop('disabled', true);
            }

            this.updateSelectAllBtn();
        },

        updateSelectAllBtn: function() {
            if (this.canSelectAll()) {
                this.$selectAllBtn.text(Craft.t('app', 'Select All'));
            } else {
                this.$selectAllBtn.text(Craft.t('app', 'Deselect All'));
            }
        },

        canSelectAll: function() {
            return !!this.$allCheckboxes.filter(':not(:checked)').length;
        }
    });

    var userPermissions = $('.user-permissions');

    $.each(userPermissions, function() {
        new Craft.UserPermissions(this);
    });
})(jQuery);
