(function ($) {
  /** global: Craft */
  /** global: Garnish */
  var FieldsAdmin = Garnish.Base.extend({
    $groups: null,
    $selectedGroup: null,

    init: function () {
      this.$groups = $('#groups');
      this.$selectedGroup = this.$groups.find('a.sel:first');
      this.addListener($('#newgroupbtn'), 'activate', 'addNewGroup');

      var $groupSettingsBtn = $('#groupsettingsbtn');

      if ($groupSettingsBtn.length) {
        var menuBtn = $groupSettingsBtn.data('menubtn');

        menuBtn.settings.onOptionSelect = (elem) => {
          var action = $(elem).data('action');

          switch (action) {
            case 'rename': {
              this.renameSelectedGroup();
              break;
            }
            case 'delete': {
              this.deleteSelectedGroup();
              break;
            }
          }
        };
      }
    },

    addNewGroup: function () {
      var name = this.promptForGroupName('');

      if (name) {
        var data = {
          name: name,
        };

        Craft.sendActionRequest('POST', 'fields/save-group', {data})
          .then((response) => {
            location.href = Craft.getUrl(
              'settings/fields/' + response.data.group.id
            );
          })
          .catch(({response}) => {
            if (response.data.errors) {
              var errors = this.flattenErrors(response.data.errors);
              alert(
                Craft.t('app', 'Could not create the group:') +
                  '\n\n' +
                  errors.join('\n')
              );
            } else {
              Craft.cp.displayError();
            }
          });
      }
    },

    renameSelectedGroup: function () {
      var oldName = this.$selectedGroup.text(),
        newName = this.promptForGroupName(oldName);

      if (newName && newName !== oldName) {
        var data = {
          id: this.$selectedGroup.data('id'),
          name: newName,
        };

        Craft.sendActionRequest('POST', 'fields/save-group', {data})
          .then((response) => {
            this.$selectedGroup.text(response.data.group.name);
            Craft.cp.displayNotice(Craft.t('app', 'Group renamed.'));
          })
          .catch(({response}) => {
            if (response.data.errors) {
              var errors = this.flattenErrors(response.data.errors);
              alert(
                Craft.t('app', 'Could not rename the group:') +
                  '\n\n' +
                  errors.join('\n')
              );
            } else {
              Craft.cp.displayError();
            }
          });
      }
    },

    promptForGroupName: function (oldName) {
      return prompt(
        Craft.t('app', 'What do you want to name the group?'),
        oldName
      );
    },

    deleteSelectedGroup: function () {
      if (
        confirm(
          Craft.t(
            'app',
            'Are you sure you want to delete this group and all its fields?'
          )
        )
      ) {
        var data = {
          id: this.$selectedGroup.data('id'),
        };

        Craft.sendActionRequest('POST', 'fields/delete-group', {data})
          .then((response) => {
            location.href = Craft.getUrl('settings/fields');
          })
          .catch(({response}) => {
            Craft.cp.displayError();
          });
      }
    },

    flattenErrors: function (responseErrors) {
      var errors = [];

      for (var attribute in responseErrors) {
        if (!responseErrors.hasOwnProperty(attribute)) {
          continue;
        }

        errors = errors.concat(responseErrors[attribute]);
      }

      return errors;
    },
  });

  Garnish.$doc.ready(function () {
    Craft.FieldsAdmin = new FieldsAdmin();
  });
})(jQuery);
