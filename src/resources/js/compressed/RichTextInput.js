!function(a){/**
	 * Rich Text input class
	 */
Craft.RichTextInput=Garnish.Base.extend({id:null,linkOptions:null,volumes:null,elementSiteId:null,redactorConfig:null,$textarea:null,redactor:null,linkOptionModals:null,init:function(b){
// Redactor I config setting normalization
if(this.id=b.id,this.linkOptions=b.linkOptions,this.volumes=b.volumes,this.transforms=b.transforms,this.elementSiteId=b.elementSiteId,this.redactorConfig=b.redactorConfig,this.linkOptionModals=[],this.redactorConfig.lang||(this.redactorConfig.lang=b.redactorLang),this.redactorConfig.direction||(this.redactorConfig.direction=b.direction||Craft.orientation),this.redactorConfig.imageUpload=!0,this.redactorConfig.fileUpload=!0,this.redactorConfig.dragImageUpload=!1,this.redactorConfig.dragFileUpload=!1,
// Prevent a JS error when calling core.destroy() when opts.plugins == false
typeof this.redactorConfig.plugins!=typeof[]&&(this.redactorConfig.plugins=[]),this.redactorConfig.buttons){var c;
// buttons.html => plugins.source
(c=a.inArray("html",this.redactorConfig.buttons))!==-1&&(this.redactorConfig.buttons.splice(c,1),this.redactorConfig.plugins.unshift("source")),
// buttons.formatting => buttons.format
(c=a.inArray("formatting",this.redactorConfig.buttons))!==-1&&this.redactorConfig.buttons.splice(c,1,"format");for(var d,e=["unorderedlist","orderedlist","undent","indent"],f=0;f<e.length;f++)(c=a.inArray(e[f],this.redactorConfig.buttons))!==-1&&(this.redactorConfig.buttons.splice(c,1),("undefined"==typeof d||c<d)&&(d=c));"undefined"!=typeof d&&this.redactorConfig.buttons.splice(d,0,"lists")}var g={init:Craft.RichTextInput.handleRedactorInit};if(typeof this.redactorConfig.callbacks==typeof[])
// Merge them together
for(var f in g)"undefined"!=typeof this.redactorConfig.callbacks[f]&&(this.redactorConfig.callbacks[f]=this.mergeCallbacks(g[f],this.redactorConfig.callbacks[f]));else this.redactorConfig.callbacks=g;
// Initialize Redactor
this.$textarea=a("#"+this.id),this.initRedactor(),"undefined"!=typeof Craft.livePreview&&(
// There's a UI glitch if Redactor is in Code view when Live Preview is shown/hidden
Craft.livePreview.on("beforeEnter beforeExit",a.proxy(function(){this.redactor.core.destroy()},this)),Craft.livePreview.on("enter slideOut",a.proxy(function(){this.initRedactor()},this)))},mergeCallbacks:function(a,b){return function(){a.apply(this,arguments),b.apply(this,arguments)}},initRedactor:function(){Craft.RichTextInput.currentInstance=this,this.$textarea.redactor(this.redactorConfig),delete Craft.RichTextInput.currentInstance},onInitRedactor:function(b){this.redactor=b,
// Only customize the toolbar if there is one,
// otherwise we get a JS error due to redactor.$toolbar being undefined
this.redactor.opts.toolbar&&this.customizeToolbar(),this.leaveFullscreetOnSaveShortcut(),this.redactor.core.editor().on("focus",a.proxy(this,"onEditorFocus")).on("blur",a.proxy(this,"onEditorBlur")),this.redactor.opts.toolbarFixed&&!Craft.RichTextInput.scrollPageOnReady&&(Garnish.$doc.on("ready",function(){Garnish.$doc.trigger("scroll")}),Craft.RichTextInput.scrollPageOnReady=!0)},customizeToolbar:function(){
// Override the Image and File buttons?
if(this.volumes.length){var b=this.replaceRedactorButton("image",this.redactor.lang.get("image")),c=this.replaceRedactorButton("file",this.redactor.lang.get("file"));b&&this.redactor.button.addCallback(b,a.proxy(this,"onImageButtonClick")),c&&this.redactor.button.addCallback(c,a.proxy(this,"onFileButtonClick"))}else
// Image and File buttons aren't supported
this.redactor.button.remove("image"),this.redactor.button.remove("file");
// Override the Link button?
if(this.linkOptions.length){var d=this.replaceRedactorButton("link",this.redactor.lang.get("link"));if(d){for(var e={},f=0;f<this.linkOptions.length;f++)e["link_option"+f]={title:this.linkOptions[f].optionTitle,func:a.proxy(this,"onLinkOptionClick",f)};
// Add the default Link options
a.extend(e,{link:{title:this.redactor.lang.get("link-insert"),func:"link.show",observe:{element:"a",in:{title:this.redactor.lang.get("link-edit")},out:{title:this.redactor.lang.get("link-insert")}}},unlink:{title:this.redactor.lang.get("unlink"),func:"link.unlink",observe:{element:"a",out:{attr:{class:"redactor-dropdown-link-inactive","aria-disabled":!0}}}}}),this.redactor.button.addDropdown(d,e)}}},onImageButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.assetSelectionModal?this.assetSelectionModal=Craft.createElementSelectorModal("craft\\app\\elements\\Asset",{storageKey:"RichTextFieldType.ChooseImage",multiSelect:!0,sources:this.volumes,criteria:{siteId:this.elementSiteId,kind:"image"},onSelect:a.proxy(function(b,c){if(b.length){this.redactor.selection.restore();for(var d=0;d<b.length;d++){var e=b[d],f=e.url+"#asset:"+e.id;c&&(f+=":"+c),this.redactor.insert.node(a('<img src="'+f+'" />')[0]),this.redactor.code.sync()}this.redactor.observe.images()}},this),closeOtherModals:!1,transforms:this.transforms}):this.assetSelectionModal.show()},onFileButtonClick:function(){this.redactor.selection.save(),"undefined"==typeof this.assetLinkSelectionModal?this.assetLinkSelectionModal=Craft.createElementSelectorModal("craft\\app\\elements\\Asset",{storageKey:"RichTextFieldType.LinkToAsset",sources:this.volumes,criteria:{siteId:this.elementSiteId},onSelect:a.proxy(function(b){if(b.length){this.redactor.selection.restore();var c=b[0],d=c.url+"#asset:"+c.id,e=this.redactor.selection.text(),f=e.length>0?e:c.label;this.redactor.insert.node(a('<a href="'+d+'">'+f+"</a>")[0]),this.redactor.code.sync()}},this),closeOtherModals:!1,transforms:this.transforms}):this.assetLinkSelectionModal.show()},onLinkOptionClick:function(b){if(this.redactor.selection.save(),"undefined"==typeof this.linkOptionModals[b]){var c=this.linkOptions[b];this.linkOptionModals[b]=Craft.createElementSelectorModal(c.elementType,{storageKey:c.storageKey||"RichTextFieldType.LinkTo"+c.elementType,sources:c.sources,criteria:a.extend({siteId:this.elementSiteId},c.criteria),onSelect:a.proxy(function(b){if(b.length){this.redactor.selection.restore();var d=b[0],e=c.elementType.replace(/^\w|_\w/g,function(a){return a.toLowerCase()}),f=d.url+"#"+e+":"+d.id,g=this.redactor.selection.text(),h=g.length>0?g:d.label;this.redactor.insert.node(a('<a href="'+f+'">'+h+"</a>")[0]),this.redactor.code.sync()}},this),closeOtherModals:!1})}else this.linkOptionModals[b].show()},onEditorFocus:function(){this.redactor.core.box().addClass("focus")},onEditorBlur:function(){this.redactor.core.box().removeClass("focus")},leaveFullscreetOnSaveShortcut:function(){"undefined"!=typeof this.redactor.fullscreen&&"function"==typeof this.redactor.fullscreen.disable&&Craft.cp.on("beforeSaveShortcut",a.proxy(function(){this.redactor.fullscreen.isOpen&&this.redactor.fullscreen.disable()},this))},replaceRedactorButton:function(a,b){
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