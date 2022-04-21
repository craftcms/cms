(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.SitesAdmin = Garnish.Base.extend({
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
          var $elem = $(elem);

          if ($elem.hasClass('disabled')) {
            return;
          }

          switch ($elem.data('action')) {
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
      this.promptForGroupName('')
        .then((name) => {
          if (!name) {
            return;
          }

          let data = {
            name: name,
          };

          Craft.postActionRequest(
            'sites/save-group',
            data,
            (response, textStatus) => {
              if (textStatus === 'success') {
                if (response.success) {
                  location.href = Craft.getUrl('settings/sites', {
                    groupId: response.group.id,
                  });
                } else if (response.errors) {
                  var errors = this.flattenErrors(response.errors);
                  alert(
                    Craft.t('app', 'Could not create the group:') +
                      '\n\n' +
                      errors.join('\n')
                  );
                } else {
                  Craft.cp.displayError();
                }
              }
            }
          );
        })
        .catch(() => {});
    },

    renameSelectedGroup: function () {
      this.promptForGroupName(this.$selectedGroup.data('raw-name'))
        .then((newName) => {
          var data = {
            id: this.$selectedGroup.data('id'),
            name: newName,
          };

          Craft.postActionRequest(
            'sites/save-group',
            data,
            (response, textStatus) => {
              if (textStatus === 'success') {
                if (response.success) {
                  this.$selectedGroup.text(response.group.name);
                  this.$selectedGroup.data('raw-name', newName);
                  Craft.cp.displayNotice(Craft.t('app', 'Group renamed.'));
                } else if (response.errors) {
                  var errors = this.flattenErrors(response.errors);
                  alert(
                    Craft.t('app', 'Could not rename the group:') +
                      '\n\n' +
                      errors.join('\n')
                  );
                } else {
                  Craft.cp.displayError();
                }
              }
            }
          );
        })
        .catch(() => {});
    },

    promptForGroupName: function (oldName) {
      return new Promise((resolve, reject) => {
        Craft.sendActionRequest('POST', 'sites/rename-group-field', {
          data: {name: oldName},
        }).then((response) => {
          let $form = $('<form/>', {class: 'modal prompt'}).appendTo(
            Garnish.$bod
          );
          let $body = $('<div/>', {class: 'body'})
            .append(response.data.html)
            .appendTo($form);
          let $buttons = $('<div/>', {class: 'buttons right'}).appendTo($body);
          let $cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
          }).appendTo($buttons);
          let $saveBtn = $('<button/>', {
            type: 'submit',
            class: 'btn submit',
            text: Craft.t('app', 'Save'),
          }).appendTo($buttons);

          Craft.appendFootHtml(response.data.js);

          let success = false;
          let modal = new Garnish.Modal($form, {
            onShow: () => {
              setTimeout(() => {
                Craft.setFocusWithin($body);
              }, 100);
            },
            onHide: () => {
              if (!success) {
                reject();
              }
            },
          });

          $form.on('submit', (ev) => {
            ev.preventDefault();
            let newName = $('.text', $body).val();
            if (newName && newName !== oldName) {
              resolve(newName);
              success = true;
            }
            modal.hide();
          });

          $cancelBtn.on('click', () => {
            modal.hide();
          });
        });
      });
    },

    deleteSelectedGroup: function () {
      if (
        confirm(Craft.t('app', 'Are you sure you want to delete this group?'))
      ) {
        var data = {
          id: this.$selectedGroup.data('id'),
        };

        Craft.postActionRequest(
          'sites/delete-group',
          data,
          (response, textStatus) => {
            if (textStatus === 'success') {
              if (response.success) {
                location.href = Craft.getUrl('settings/sites');
              } else {
                Craft.cp.displayError();
              }
            }
          }
        );
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

  Craft.SiteAdminTable = Craft.AdminTable.extend({
    confirmDeleteModal: null,

    $rowToDelete: null,
    $deleteActionRadios: null,
    $deleteSubmitBtn: null,
    $deleteSpinner: null,

    _deleting: false,

    confirmDeleteItem: function ($row) {
      if (this.confirmDeleteModal) {
        this.confirmDeleteModal.destroy();
        delete this.confirmDeleteModal;
      }

      this._createConfirmDeleteModal($row);

      // Auto-focus the first radio
      if (!Garnish.isMobileBrowser(true)) {
        setTimeout(() => {
          this.$deleteActionRadios.first().trigger('focus');
        }, 100);
      }

      return false;
    },

    validateDeleteInputs: function () {
      var validates =
        this.$deleteActionRadios.eq(0).prop('checked') ||
        this.$deleteActionRadios.eq(1).prop('checked');

      if (validates) {
        this.$deleteSubmitBtn.removeClass('disabled');
      } else {
        this.$deleteSubmitBtn.addClass('disabled');
      }

      return validates;
    },

    submitDeleteLocale: function (ev) {
      ev.preventDefault();

      if (this._deleting || !this.validateDeleteInputs()) {
        return;
      }

      this.$deleteSubmitBtn.addClass('active');
      this.$deleteSpinner.removeClass('hidden');
      this.disable();
      this._deleting = true;

      var data = {
        id: this.getItemId(this.$rowToDelete),
      };

      // Are we transferring content?
      if (this.$deleteActionRadios.eq(0).prop('checked')) {
        data.transferContentTo = this.$transferSelect.val();
      }

      Craft.postActionRequest(
        this.settings.deleteAction,
        data,
        (response, textStatus) => {
          if (textStatus === 'success') {
            this._deleting = false;
            this.enable();
            this.confirmDeleteModal.hide();
            this.handleDeleteItemResponse(response, this.$rowToDelete);
          }
        }
      );
    },

    _createConfirmDeleteModal: function ($row) {
      this.$rowToDelete = $row;

      let id = this.getItemId($row);
      let name = this.getItemName($row);

      let $form = $(
        '<form id="confirmdeletemodal" class="modal fitted" method="post" accept-charset="UTF-8">' +
          Craft.getCsrfInput() +
          '<input type="hidden" name="action" value="localization/deleteLocale"/>' +
          '<input type="hidden" name="id" value="' +
          id +
          '"/>' +
          '</form>'
      ).appendTo(Garnish.$bod);
      let $body = $(
        '<div class="body">' +
          '<p>' +
          Craft.t(
            'app',
            'What do you want to do with any content that is only available in {language}?',
            {language: name}
          ) +
          '</p>' +
          '<div class="options">' +
          '<label><input type="radio" name="contentAction" value="transfer"/> ' +
          Craft.t('app', 'Transfer it to:') +
          '</label> ' +
          '<div id="transferselect" class="select">' +
          '<select/>' +
          '</div>' +
          '</div>' +
          '<div>' +
          '<label><input type="radio" name="contentAction" value="delete"/> ' +
          Craft.t('app', 'Delete it') +
          '</label>' +
          '</div>' +
          '</div>'
      ).appendTo($form);
      let $buttons = $('<div class="buttons right"/>').appendTo($body);
      let $cancelBtn = $('<button/>', {
        type: 'button',
        class: 'btn',
        text: Craft.t('app', 'Cancel'),
      }).appendTo($buttons);

      this.$deleteActionRadios = $body.find('input[type=radio]');
      this.$transferSelect = $('#transferselect').find('> select');
      this.$deleteSubmitBtn = $('<button/>', {
        type: 'submit',
        class: 'btn submit disabled',
        text: Craft.t('app', 'Delete {site}', {site: name}),
      }).appendTo($buttons);
      this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo(
        $buttons
      );

      for (var i = 0; i < Craft.sites.length; i++) {
        if (Craft.sites[i].id != id) {
          this.$transferSelect.append(
            '<option value="' +
              Craft.sites[i].id +
              '">' +
              Craft.escapeHtml(Craft.sites[i].name) +
              '</option>'
          );
        }
      }

      this.confirmDeleteModal = new Garnish.Modal($form);

      this.addListener($cancelBtn, 'click', function () {
        this.confirmDeleteModal.hide();
      });

      this.addListener(
        this.$deleteActionRadios,
        'change',
        'validateDeleteInputs'
      );
      this.addListener($form, 'submit', 'submitDeleteLocale');
    },
  });
})(jQuery);
