(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.QuickPostWidget = Garnish.Base.extend({
    params: null,
    initFields: null,
    formHtml: null,
    $widget: null,
    $form: null,
    $saveBtn: null,
    $errorList: null,
    loading: false,

    init: function (widgetId, params, initFields, formHtml) {
      this.params = params;
      this.initFields = initFields;
      this.formHtml = formHtml;
      this.$widget = $('#widget' + widgetId);

      this.initForm(this.$widget.find('form:first'));
    },

    initForm: function ($form) {
      this.$form = $form;
      this.$saveBtn = this.$form.find('button[type=submit]');

      this.initFields();

      var $menuBtn = this.$form.find('> .buttons > .btngroup > .menubtn'),
        $saveAndContinueEditingBtn = $menuBtn
          .data('menubtn')
          .menu.$container.find('> ul > li > a');

      $menuBtn.menubtn();

      this.addListener(this.$form, 'submit', 'handleFormSubmit');
      this.addListener(
        $saveAndContinueEditingBtn,
        'click',
        'saveAndContinueEditing'
      );
    },

    handleFormSubmit: function (event) {
      event.preventDefault();

      this.save(this.onSave.bind(this));
    },

    saveAndContinueEditing: function () {
      this.save(this.gotoEntry.bind(this));
    },

    save: function (callback) {
      if (this.loading) {
        return;
      }

      this.loading = true;
      this.$saveBtn.addClass('loading');

      var formData = Garnish.getPostData(this.$form),
        data = $.extend({enabled: 1}, formData, this.params);

      Craft.sendActionRequest('POST', 'entries/save-entry', {data})
        .then((response) => {
          if (this.$errorList) {
            this.$errorList.children().remove();
          }

          Craft.cp.displayNotice(Craft.t('app', 'Entry saved.'));
          callback(response.data);
        })
        .catch(({response}) => {
          if (this.$errorList) {
            this.$errorList.children().remove();
          }

          Craft.cp.displayError(Craft.t('app', 'Couldn’t save entry.'));

          if (response.data.errors) {
            if (!this.$errorList) {
              this.$errorList = $('<ul class="errors"/>').insertAfter(
                this.$form
              );
            }

            for (var attribute in response.data.errors) {
              if (!response.data.errors.hasOwnProperty(attribute)) {
                continue;
              }

              for (var i = 0; i < response.data.errors[attribute].length; i++) {
                var error = response.data.errors[attribute][i];
                $('<li>' + error + '</li>').appendTo(this.$errorList);
              }
            }
          }
        })
        .finally(() => {
          this.loading = false;
          this.$saveBtn.removeClass('loading');
        });
    },

    onSave: function (response) {
      // Reset the widget
      var $newForm = $(this.formHtml);
      this.$form.replaceWith($newForm);
      Craft.initUiElements($newForm);
      this.initForm($newForm);

      // Are there any Recent Entries widgets to notify?
      if (typeof Craft.RecentEntriesWidget !== 'undefined') {
        for (var i = 0; i < Craft.RecentEntriesWidget.instances.length; i++) {
          var widget = Craft.RecentEntriesWidget.instances[i];
          if (
            !widget.params.sectionId ||
            widget.params.sectionId == this.params.sectionId
          ) {
            widget.addEntry({
              url: response.cpEditUrl,
              title: response.title,
              dateCreated: response.dateCreated,
              username: response.authorUsername,
            });
          }
        }
      }
    },

    gotoEntry: function (response) {
      // Redirect to the entry’s edit URL
      Craft.redirectTo(response.cpEditUrl);
    },
  });
})(jQuery);
