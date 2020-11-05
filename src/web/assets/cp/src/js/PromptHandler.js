/** global: Craft */
/** global: Garnish */
/**
 * File Manager.
 */
Craft.PromptHandler = Garnish.Base.extend({
    modal: null,
    $modalContainerDiv: null,
    $prompt: null,
    $promptApplyToRemainingContainer: null,
    $promptApplyToRemainingCheckbox: null,
    $promptApplyToRemainingLabel: null,
    $pomptChoices: null,


    _prompts: [],
    _promptBatchCallback: $.noop,
    _promptBatchReturnData: [],
    _promptBatchNum: 0,

    resetPrompts: function() {
        this._prompts = [];
        this._promptBatchCallback = $.noop;
        this._promptBatchReturnData = [];
        this._promptBatchNum = 0;
    },

    addPrompt: function(prompt) {
        this._prompts.push(prompt);
    },

    getPromptCount: function() {
        return this._prompts.length;
    },

    showBatchPrompts: function(callback) {
        this._promptBatchCallback = callback;
        this._promptBatchReturnData = [];
        this._promptBatchNum = 0;

        this._showNextPromptInBatch();
    },

    _showNextPromptInBatch: function() {
        var prompt = this._prompts[this._promptBatchNum].prompt,
            remainingInBatch = this._prompts.length - (this._promptBatchNum + 1);

        this._showPrompt(prompt.message, prompt.choices, $.proxy(this, '_handleBatchPromptSelection'), remainingInBatch);
    },

    /**
     * Handles a prompt choice selection.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
    _handleBatchPromptSelection: function(choice, applyToRemaining) {
        var prompt = this._prompts[this._promptBatchNum],
            remainingInBatch = this._prompts.length - (this._promptBatchNum + 1);

        // Record this choice
        var choiceData = $.extend(prompt, {choice: choice});
        this._promptBatchReturnData.push(choiceData);

        // Are there any remaining items in the batch?
        if (remainingInBatch) {
            // Get ready to deal with the next prompt
            this._promptBatchNum++;

            // Apply the same choice to the remaining items?
            if (applyToRemaining) {
                this._handleBatchPromptSelection(choice, true);
            }
            else {
                // Show the next prompt
                this._showNextPromptInBatch();
            }
        }
        else {
            // All done! Call the callback
            if (typeof this._promptBatchCallback === 'function') {
                this._promptBatchCallback(this._promptBatchReturnData);
            }
        }
    },

    /**
     * Show the user prompt with a given message and choices, plus an optional "Apply to remaining" checkbox.
     *
     * @param {string} message
     * @param {object} choices
     * @param {function} callback
     * @param {number} itemsToGo
     */
    _showPrompt: function(message, choices, callback, itemsToGo) {
        this._promptCallback = callback;

        if (this.modal === null) {
            this.modal = new Garnish.Modal({closeOtherModals: false});
        }

        if (this.$modalContainerDiv === null) {
            this.$modalContainerDiv = $('<div class="modal fitted prompt-modal"></div>').addClass().appendTo(Garnish.$bod);
        }

        this.$prompt = $('<div class="body"></div>').appendTo(this.$modalContainerDiv.empty());

        this.$promptMessage = $('<p class="prompt-msg"/>').appendTo(this.$prompt);

        this.$promptChoices = $('<div class="options"></div>').appendTo(this.$prompt);

        this.$promptApplyToRemainingContainer = $('<label class="assets-applytoremaining"/>').appendTo(this.$prompt).hide();
        this.$promptApplyToRemainingCheckbox = $('<input type="checkbox"/>').appendTo(this.$promptApplyToRemainingContainer);
        this.$promptApplyToRemainingLabel = $('<span/>').appendTo(this.$promptApplyToRemainingContainer);

        this.$promptButtons = $('<div class="buttons right"/>').appendTo(this.$prompt);

        this.modal.setContainer(this.$modalContainerDiv);

        this.$promptMessage.html(message);

        let $cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
        }).appendTo(this.$promptButtons);
        let $submitBtn = $('<button/>', {
            type: 'submit',
            class: 'btn submit disabled',
            text: Craft.t('app', 'OK'),
        }).appendTo(this.$promptButtons);

        for (var i = 0; i < choices.length; i++) {
            var $radioButtonHtml = $('<div><label><input type="radio" name="promptAction" value="' + choices[i].value + '"/> ' + choices[i].title + '</label></div>').appendTo(this.$promptChoices),
                $radioButton = $radioButtonHtml.find('input');

            this.addListener($radioButton, 'click', function() {
                $submitBtn.removeClass('disabled');
            });
        }

        this.addListener($submitBtn, 'activate', function(ev) {
            var choice = $(ev.currentTarget).parents('.modal').find('input[name=promptAction]:checked').val(),
                applyToRemaining = this.$promptApplyToRemainingCheckbox.prop('checked');

            this._selectPromptChoice(choice, applyToRemaining);
        });

        this.addListener($cancelBtn, 'activate', function() {
            var choice = 'cancel',
                applyToRemaining = this.$promptApplyToRemainingCheckbox.prop('checked');

            this._selectPromptChoice(choice, applyToRemaining);
        });

        if (itemsToGo) {
            this.$promptApplyToRemainingContainer.show();
            this.$promptApplyToRemainingLabel.html(' ' + Craft.t('app', 'Apply this to the {number} remaining conflicts?', {number: itemsToGo}));
        }

        this.modal.show();
        this.modal.removeListener(Garnish.Modal.$shade, 'click');
        this.addListener(Garnish.Modal.$shade, 'click', '_cancelPrompt');
    },

    /**
     * Handles when a user selects one of the prompt choices.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
    _selectPromptChoice: function(choice, applyToRemaining) {
        this.$prompt.fadeOut('fast', $.proxy(function() {
            this.modal.hide();
            this._promptCallback(choice, applyToRemaining);
        }, this));
    },

    /**
     * Cancels the prompt.
     */
    _cancelPrompt: function() {
        this._selectPromptChoice('cancel', true);
    }
});
