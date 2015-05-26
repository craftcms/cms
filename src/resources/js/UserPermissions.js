(function($) {


Craft.UserPermissions = Garnish.Base.extend(
{
    $wrapper: null,
    $selectAllBtn: null,
    $allCheckboxes: null,
    $rootCheckboxes: null,

    init: function(wrapper)
    {
        this.$wrapper = wrapper;
        this.$selectAllBtn = $('.select-all', this.$wrapper);
        this.$allCheckboxes = $('input[type=checkbox]', this.$wrapper);
        this.$rootCheckboxes = $(this.$wrapper).find('> ul > li > input[type=checkbox]');

        this.addListener(this.$selectAllBtn, 'click', 'toggleSelectAll');
        this.addListener(this.$allCheckboxes, 'click', 'toggleCheckbox');

        this.updateSelectAllBtn();
    },

    toggleSelectAll: function(ev)
    {
        if(this.$allCheckboxes.filter(':checked').length < this.$allCheckboxes.length)
        {
            this.$allCheckboxes.filter(':not(:checked)').trigger('click');
        }
        else
        {
            this.$rootCheckboxes.filter(':checked').trigger('click');
        }

        ev.preventDefault();
    },

    toggleCheckbox: function(ev)
    {
        var checkbox = $(ev.currentTarget);
        var uls = checkbox.parent('li').find('> ul');
        var childrenCheckboxes = checkbox.parent('li').find('> ul > li > input[type=checkbox]');

        if(checkbox.prop('checked'))
        {
            childrenCheckboxes.prop('disabled', false);
        }
        else
        {
            childrenCheckboxes.filter(':checked').trigger('click');
            childrenCheckboxes.prop('disabled', true);
        }

        this.updateSelectAllBtn();
    },

    updateSelectAllBtn: function()
    {
        if(this.$allCheckboxes.filter(':checked').length < this.$allCheckboxes.length)
        {
            this.$selectAllBtn.text(Craft.t('Select All'));
        }
        else
        {
            this.$selectAllBtn.text(Craft.t('Deselect All'));
        }
    }
});

var userPermissions = $('.user-permissions');

$.each(userPermissions, function() {
    new Craft.UserPermissions(this);
});

})(jQuery);
