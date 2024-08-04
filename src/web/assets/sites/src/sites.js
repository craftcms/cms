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

          Craft.sendActionRequest('POST', 'sites/save-group', {data})
            .then((response) => {
              location.href = Craft.getUrl('settings/sites', {
                groupId: response.data.group.id,
              });
            })
            .catch((e) => {
              if (e?.response?.data?.errors) {
                Craft.cp.displayError(
                  Craft.t('app', 'Could not create the group:') +
                    '\n\n' +
                    e.response.data.errors.join('\n')
                );
              } else {
                Craft.cp.displayError();
              }
            });
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

          Craft.sendActionRequest('POST', 'sites/save-group', {data})
            .then((response) => {
              this.$selectedGroup.text(response.data.group.name);
              this.$selectedGroup.data('raw-name', newName);
              Craft.cp.displaySuccess(Craft.t('app', 'Group renamed.'));
            })
            .catch((e) => {
              if (e?.response?.data?.errors) {
                Craft.cp.displayError(
                  Craft.t('app', 'Could not rename the group:') +
                    '\n\n' +
                    e.response.data.errors.join('\n')
                );
              } else {
                Craft.cp.displayError();
              }
            });
        })
        .catch(() => {});
    },

    promptForGroupName: function (oldName) {
      return new Promise((resolve, reject) => {
        Craft.sendActionRequest('POST', 'sites/rename-group-field', {
          data: {name: oldName},
        }).then(async (response) => {
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

          await Craft.appendBodyHtml(response.data.js);

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

        Craft.sendActionRequest('POST', 'sites/delete-group', {data})
          .then(() => {
            location.href = Craft.getUrl('settings/sites');
          })
          .catch(() => {
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

  Craft.SiteAdminTable = Craft.AdminTable.extend({
    confirmDeleteModal: null,

    $rowToDelete: null,
    $deleteActionRadios: null,
    $deleteSubmitBtn: null,

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
          this.$deleteActionRadios.first().focus();
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

    submitDeleteSite: function (ev) {
      ev.preventDefault();

      if (this._deleting || !this.validateDeleteInputs()) {
        return;
      }

      this.$deleteSubmitBtn.addClass('loading');
      this.disable();
      this._deleting = true;

      var data = {
        id: this.getItemId(this.$rowToDelete),
      };

      // Are we transferring content?
      if (this.$deleteActionRadios.eq(0).prop('checked')) {
        data.transferContentTo = this.$transferSelect.val();
      }

      this.$deleteSubmitBtn.removeClass('loading');

      Craft.sendActionRequest('POST', this.settings.deleteAction, {data}).then(
        (response) => {
          this._deleting = false;
          this.enable();
          this.confirmDeleteModal.hide();
          this.handleDeleteItemSuccess(response.data, this.$rowToDelete);
        }
      );
    },

    _createConfirmDeleteModal: function ($row) {
      this.$rowToDelete = $row;

      let id = this.getItemId($row);
      let name = this.getItemName($row);

      let $form = $(
        '<form id="confirmdeletemodal" class="modal fitted" method="post" accept-charset="UTF-8"/>'
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
      this.$deleteSubmitBtn = Craft.ui
        .createSubmitButton({
          class: 'disabled',
          label: Craft.t('app', 'Delete {site}', {site: name}),
          spinner: true,
        })
        .appendTo($buttons);

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
      this.addListener($form, 'submit', 'submitDeleteSite');
    },
  });
})(jQuery);
