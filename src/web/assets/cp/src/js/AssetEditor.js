/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetEditor = Craft.BaseElementEditor.extend(
    {
        updateForm: function(response) {
            this.base(response);

            if (this.$element.data('id')) {
                var $imageEditorTrigger = this.$fieldsContainer.find('> .meta > .image-preview-container.editable');

                if ($imageEditorTrigger.length) {
                    this.addListener($imageEditorTrigger, 'click', 'showImageEditor');
                }
            }

        },

        showImageEditor: function()
        {
            new Craft.AssetImageEditor(this.$element.data('id'), {
                onSave: $.proxy(this, 'reloadForm'),
                allowDegreeFractions: Craft.isImagick
            });
        }

    });

// Register it!
Craft.registerElementEditorClass('craft\\elements\\Asset', Craft.AssetEditor);
