(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.EntryDraftEditor = Garnish.Base.extend(
        {
            $revisionBtn: null,
            $editBtn: null,
            $nameInput: null,
            $saveBtn: null,
            $spinner: null,

            draftId: null,
            draftName: null,
            draftNotes: null,
            hud: null,
            loading: false,

            init: function(draftId, draftName, draftNotes) {
                this.draftId = draftId;
                this.draftName = draftName;
                this.draftNotes = draftNotes;

                this.$revisionBtn = $('#revision-btn');
                this.$editBtn = $('#editdraft-btn');

                this.addListener(this.$editBtn, 'click', 'showHud');
            },

            showHud: function() {
                if (!this.hud) {
                    var $hudBody = $('<div/>');

                    var $field, $inputContainer;

                    // Add the Name field
                    $field = $('<div class="field"><div class="heading"><label for="draft-name">' + Craft.t('app', 'Draft Name') + '</label></div></div>').appendTo($hudBody);
                    $inputContainer = $('<div class="input"/>').appendTo($field);
                    this.$nameInput = $('<input type="text" class="text fullwidth" id="draft-name"/>').appendTo($inputContainer).val(this.draftName);

                    // Add the Notes field
                    $field = $('<div class="field"><div class="heading"><label for="draft-notes">' + Craft.t('app', 'Notes') + '</label></div></div>').appendTo($hudBody);
                    $inputContainer = $('<div class="input"/>').appendTo($field);
                    this.$notesInput = $('<textarea class="text fullwidth" id="draft-notes" rows="2"/>').appendTo($inputContainer).val(this.draftNotes);

                    // Add the button
                    var $footer = $('<div class="hud-footer"/>').appendTo($hudBody),
                        $buttonsContainer = $('<div class="buttons right"/>').appendTo($footer);
                    this.$saveBtn = $('<input type="submit" class="btn submit disabled" value="' + Craft.t('app', 'Save') + '"/>').appendTo($buttonsContainer);
                    this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

                    this.hud = new Garnish.HUD(this.$editBtn, $hudBody, {
                        onSubmit: $.proxy(this, 'save')
                    });

                    new Garnish.NiceText(this.$notesInput);

                    this.addListener(this.$notesInput, 'keydown', 'onNotesKeydown');

                    this.addListener(this.$nameInput, 'textchange', 'checkValues');
                    this.addListener(this.$notesInput, 'textchange', 'checkValues');

                    this.hud.on('show', $.proxy(this, 'onHudShow'));
                    this.hud.on('hide', $.proxy(this, 'onHudHide'));
                    this.hud.on('escape', $.proxy(this, 'onHudEscape'));

                    this.onHudShow();
                }
                else {
                    this.hud.show();
                }

                if (!Garnish.isMobileBrowser(true)) {
                    this.$nameInput.trigger('focus');
                }
            },

            onHudShow: function() {
                this.$editBtn.addClass('active');
            },

            onHudHide: function() {
                this.$editBtn.removeClass('active');
            },

            onHudEscape: function() {
                this.$nameInput.val(this.draftName);
            },

            onNotesKeydown: function(ev) {
                if (ev.keyCode === Garnish.RETURN_KEY) {
                    ev.preventDefault();
                    this.hud.submit();
                }
            },

            hasAnythingChanged: function() {
                return (this.$nameInput.val() !== this.draftName || this.$notesInput.val() !== this.draftNotes);
            },

            checkValues: function() {
                if (this.$nameInput.val() && this.hasAnythingChanged()) {
                    this.$saveBtn.removeClass('disabled');
                    return true;
                }
                else {
                    this.$saveBtn.addClass('disabled');
                    return false;
                }
            },

            save: function() {
                if (this.loading) {
                    return;
                }

                if (!this.checkValues()) {
                    this.shakeHud();
                    return;
                }

                this.loading = true;
                this.$saveBtn.addClass('active');
                this.$spinner.removeClass('hidden');

                var data = {
                    draftId: this.draftId,
                    name: this.$nameInput.val(),
                    notes: this.$notesInput.val()
                };

                Craft.postActionRequest('entry-revisions/update-draft-meta', data, $.proxy(function(response, textStatus) {
                    this.loading = false;
                    this.$saveBtn.removeClass('active');
                    this.$spinner.addClass('hidden');

                    if (textStatus === 'success') {
                        if (response.success) {
                            this.$revisionBtn.text(data.name);
                            this.$revisionBtn.data('menubtn').menu.$options.filter('.sel').text(data.name);
                            this.draftName = data.name;
                            this.draftNotes = data.notes;
                            this.checkValues();
                            this.hud.hide();
                        }
                        else {
                            this.shakeHud();
                        }
                    }
                }, this));
            },

            shakeHud: function() {
                Garnish.shake(this.hud.$hud);
            }

        });
})(jQuery);
