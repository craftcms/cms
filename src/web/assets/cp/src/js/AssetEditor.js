/** global: Craft */
/** global: Garnish */
/**
 * Asset index class
 */
Craft.AssetEditor = Craft.BaseElementEditor.extend(
    {
        reloadIndex: false,

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
                onSave: function () {
                    this.reloadIndex = true;
                    this.reloadForm();
                }.bind(this),
                allowDegreeFractions: Craft.isImagick
            });
        },

        onHideHud: function () {
            if (this.reloadIndex && this.settings.elementIndex) {
                this.settings.elementIndex.updateElements();
            }

            this.base();
        }
    });

// Register it!
Craft.registerElementEditorClass('craft\\elements\\Asset', Craft.AssetEditor);
