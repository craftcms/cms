var config = {{config|raw}};
var targetSelector = '.redactor-{{handle}}';
if (typeof config.buttonsCustom == "undefined")
{
  config.buttonsCustom = {};
}



config.buttonsCustom.image = {
    title: Craft.t('Insert image'),
    dropdown:
    {
        from_web: {
            title: Craft.t('Insert URL'),
            callback: function () { this.imageShow();}
        },
        from_assets: {
            title: Craft.t('Choose image'),
            callback: function () {

                var editor = this;
                editor.selectionSave();

                var elementSelectModal = new Craft.ElementSelectorModal('Asset', {
                    criteria: { kind: 'image' },
                    onSelect: function (elements) {
                        if (elements.length)
                        {
                            editor.selectionRestore();

                            var element = elements[0].$element;
                            editor.insertNode($('<img src="' + element.attr('data-url') + '" />')[0]);

                            editor.sync();
                        }
                    }
                });
                elementSelectModal.show();
                var html = 'ss';
            }
        }
    }
};

$(targetSelector).redactor(config);