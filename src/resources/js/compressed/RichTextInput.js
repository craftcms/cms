!function(a){/**
	 * Rich Text input class
	 */
Craft.RichTextInput=Garnish.Base.extend({id:null,entrySources:null,categorySources:null,assetSources:null,elementLocale:null,redactorConfig:null,$textarea:null,redactor:null,init:function(b,c,d,e,f,g,h,i){
// Redactor I config setting normalization
if(this.id=b,this.entrySources=c,this.categorySources=d,this.assetSources=e,this.elementLocale=f,this.redactorConfig=h,this.redactorConfig.lang||(this.redactorConfig.lang=i),this.redactorConfig.direction||(this.redactorConfig.direction=g),this.redactorConfig.imageUpload=!0,this.redactorConfig.fileUpload=!0,
// Prevent a JS error when calling core.destroy() when opts.plugins == false
typeof this.redactorConfig.plugins!=typeof[]&&(this.redactorConfig.plugins=[]),this.redactorConfig.buttons){var j;
// buttons.html => plugins.source
(j=a.inArray("html",this.redactorConfig.buttons))!==-1&&(this.redactorConfig.buttons.splice(j,1),this.redactorConfig.plugins.unshift("source")),
// buttons.formatting => buttons.format
(j=a.inArray("formatting",this.redactorConfig.buttons))!==-1&&this.redactorConfig.buttons.splice(j,1,"format");for(var k,l=["unorderedlist","orderedlist","undent","indent"],m=0;m<l.length;m++)(j=a.inArray(l[m],this.redactorConfig.buttons))!==-1&&(this.redactorConfig.buttons.splice(j,1),("undefined"==typeof k||j<k)&&(k=j));"undefined"!=typeof k&&this.redactorConfig.buttons.splice(k,0,"lists")}var n={init:Craft.RichTextInput.handleRedactorInit};if(typeof this.redactorConfig.callbacks==typeof[])
// Merge them together
for(var m in n)"undefined"!=typeof this.redactorConfig.callbacks[m]&&(this.redactorConfig.callbacks[m]=this.mergeCallbacks(n[m],this.redactorConfig.callbacks[m]));else this.redactorConfig.callbacks=n;
// Initialize Redactor
this.$textarea=a("#"+this.id),this.initRedactor(),"undefined"!=typeof Craft.livePreview&&(
// There's a UI glitch if Redactor is in Code view when Live Preview is shown/hidden
Craft.livePreview.on("beforeEnter beforeExit",a.proxy(function(){this.redactor.core.destroy()},this)),Craft.livePreview.on("enter slideOut",a.proxy(function(){this.initRedactor()},this)))},mergeCallbacks:function(a,b){return function(){a.apply(this,arguments),b.apply(this,arguments)}},initRedactor:function(){Craft.RichTextInput.currentInstance=this,this.$textarea.redactor(this.redactorConfig),delete Craft.RichTextInput.currentInstance},onInitRedactor:function(b){this.redactor=b,
// Only customize the toolbar if there is one,
// otherwise we get a JS error due to redactor.$toolbar being undefined
this.redactor.opts.toolbar&&this.customizeToolbar(),this.leaveFullscreetOnSaveShortcut(),this.redactor.core.editor().on("focus",a.proxy(this,"onEditorFocus")).on("blur",a.proxy(this,"onEditorBlur")),this.redactor.opts.toolbarFixed&&!Craft.RichTextInput.scrollPageOnReady&&(Garnish.$doc.on("ready",function(){Garnish.$doc.trigger("scroll")}),Craft.RichTextInput.scrollPageOnReady=!0)},customizeToolbar:function(){
// Override the Image and File buttons?
if(this.assetSources.length){var b=this.replaceRedactorButton("image",this.redactor.lang.get("image")),c=this.replaceRedactorButton("file",this.redactor.lang.get("file"));b&&this.redactor.button.addCallback(b,a.proxy(this,"onImageButtonClick")),c&&this.redactor.button.addCallback(c,a.proxy(this,"onFileButtonClick"))}else
// Image and File buttons aren't supported
this.redactor.button.remove("image"),this.redactor.button.remove("file");
// Override the Link button?
if(this.entrySources.length||this.categorySources.length){var d=this.replaceRedactorButton("link",this.redactor.lang.get("link"));if(d){var e={};this.entrySources.length&&(e.link_entry={title:Craft.t("Link to an entry"),func:a.proxy(this,"onLinkToEntryButtonClick")}),this.categorySources.length&&(e.link_category={title:Craft.t("Link to a category"),func:a.proxy(this,"onLinkToCategoryButtonClick")}),
// Add the default Link options
a.extend(e,{link:{title:this.redactor.lang.get("link-insert"),func:"link.show",observe:{element:"a",in:{title:this.redactor.lang.get("link-edit")},out:{title:this.redactor.lang.get("link-insert")}}},unlink:{title:this.redactor.lang.get("unlink"),func:"link.unlink",observe:{element:"a",out:{attr:{class:"redactor-dropdown-link-inactive","aria-disabled":!0}}}}}),this.redactor.button.addDropdown(d,e)}}},onImageButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.assetSelectionModal?this.assetSelectionModal=Craft.createElementSelectorModal("Asset",{storageKey:"RichTextFieldType.ChooseImage",multiSelect:!0,criteria:{locale:this.elementLocale,kind:"image"},onSelect:a.proxy(function(b,c){if(b.length){this.redactor.selection.restore();for(var d=0;d<b.length;d++){var e=b[d],f=e.url+"#asset:"+e.id;c&&(f+=":"+c),this.redactor.insert.node(a('<img src="'+f+'" />')[0]),this.redactor.code.sync()}this.redactor.observe.images()}},this),closeOtherModals:!1,canSelectImageTransforms:!0}):this.assetSelectionModal.show()},onFileButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.assetLinkSelectionModal?this.assetLinkSelectionModal=Craft.createElementSelectorModal("Asset",{storageKey:"RichTextFieldType.LinkToAsset",criteria:{locale:this.elementLocale},onSelect:a.proxy(function(b){if(b.length){this.redactor.selection.restore();var c=b[0],d=c.url+"#asset:"+c.id,e=this.redactor.selection.text(),f=e.length>0?e:c.label;this.redactor.insert.node(a('<a href="'+d+'">'+f+"</a>")[0]),this.redactor.code.sync()}},this),closeOtherModals:!1,canSelectImageTransforms:!0}):this.assetLinkSelectionModal.show()},onLinkToEntryButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.entrySelectionModal?this.entrySelectionModal=Craft.createElementSelectorModal("Entry",{storageKey:"RichTextFieldType.LinkToEntry",sources:this.entrySources,criteria:{locale:this.elementLocale},onSelect:a.proxy(function(b){if(b.length){this.redactor.selection.restore();var c=b[0],d=c.url+"#entry:"+c.id,e=this.redactor.selection.text(),f=e.length>0?e:c.label;this.redactor.insert.node(a('<a href="'+d+'">'+f+"</a>")[0]),this.redactor.code.sync()}},this),closeOtherModals:!1}):this.entrySelectionModal.show()},onLinkToCategoryButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.categorySelectionModal?this.categorySelectionModal=Craft.createElementSelectorModal("Category",{storageKey:"RichTextFieldType.LinkToCategory",sources:this.categorySources,criteria:{locale:this.elementLocale},onSelect:a.proxy(function(b){if(b.length){this.redactor.selection.restore();var c=b[0],d=c.url+"#category:"+c.id,e=this.redactor.selection.text(),f=e.length>0?e:c.label;this.redactor.insert.node(a('<a href="'+d+'">'+f+"</a>")[0]),this.redactor.code.sync()}},this),closeOtherModals:!1}):this.categorySelectionModal.show()},onEditorFocus:function(){this.redactor.core.box().addClass("focus")},onEditorBlur:function(){this.redactor.core.box().removeClass("focus")},leaveFullscreetOnSaveShortcut:function(){"undefined"!=typeof this.redactor.fullscreen&&"function"==typeof this.redactor.fullscreen.disable&&Craft.cp.on("beforeSaveShortcut",a.proxy(function(){this.redactor.fullscreen.isOpen&&this.redactor.fullscreen.disable()},this))},replaceRedactorButton:function(a,b){
// Ignore if the button isn't in use
if(this.redactor.button.get(a).length){
// Create a placeholder button
var c=a+"_placeholder";this.redactor.button.addAfter(a,c),
// Remove the original
this.redactor.button.remove(a);
// Add the new one
var d=this.redactor.button.addAfter(c,a,b);
// Set the dropdown
//this.redactor.button.addDropdown($btn, dropdown);
// Remove the placeholder
return this.redactor.button.remove(c),d}}},{handleRedactorInit:function(){
// `this` is the current Redactor instance.
// `Craft.RichTextInput.currentInstance` is the current RichTextInput instance
Craft.RichTextInput.currentInstance.onInitRedactor(this)}})}(jQuery);
//# sourceMappingURL=RichTextInput.js.map