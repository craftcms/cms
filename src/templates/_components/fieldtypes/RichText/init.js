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
            }
        }
    }
};

config.buttonsCustom.link = {
    title: Craft.t('Link'),
    dropdown: {
        link_entry:
        {
            title: Craft.t('Link an entry'),
            callback: function () {
                var editor = this;
                editor.selectionSave();

                var elementSelectModal = new Craft.ElementSelectorModal('Entry', {
                    sources: {{sections|raw}},
                    onSelect: function (elements) {
                        if (elements.length)
                        {
                            editor.selectionRestore();
                            var element = elements[0];
                            editor.insertNode($('<a href="' + element.$element.attr('data-url') + '">' + element.label + '</a>')[0]);

                            editor.sync();
                        }
                    }
                });
                elementSelectModal.show();
            }
        },
        link:
        {
            title: Craft.t('Insert link'),
            callback: function () { this.linkShow();}
        },
        unlink:
        {
            title: Craft.t('Remove link'),
            callback: function () { this.unlink();}
        }
    }
}

$(targetSelector).redactor(config);