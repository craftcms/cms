/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetEditor = Craft.BaseElementEditor.extend({
    reloadIndex: false,

    updateForm: function(response, refreshInitialData) {
        this.base(response, refreshInitialData);

        if (this.$element.data('id')) {
            var $imageEditorTrigger = this.$fieldsContainer.find('> .meta > .preview-thumb-container.editable');

            if ($imageEditorTrigger.length) {
                this.addListener($imageEditorTrigger, 'click', 'showImageEditor');
            }
        }
    },

    showImageEditor: function() {
        new Craft.AssetImageEditor(this.$element.data('id'), {
            onSave: function() {
                this.reloadIndex = true;
                this.reloadForm();
            }.bind(this),
        });
    },

    onHideHud: function() {
        if (this.reloadIndex && this.settings.elementIndex) {
            this.settings.elementIndex.updateElements();
        } else if (this.reloadIndex && this.settings.input) {
            this.settings.input.refreshThumbnail(this.$element.data('id'));
        }

        this.base();
    }
});

// Register it!
Craft.registerElementEditorClass('craft\\elements\\Asset', Craft.AssetEditor);
