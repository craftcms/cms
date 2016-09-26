/*! Craft 3.0.0 - 2016-09-26 */
!function(a){
// Set all the standard Craft.* stuff
a.extend(Craft,{navHeight:48,/**
	 * Map of high-ASCII codes to their low-ASCII characters.
	 *
	 * @var object
	 */
asciiCharMap:{a:["à","á","ả","ã","ạ","ă","ắ","ằ","ẳ","ẵ","ặ","â","ấ","ầ","ẩ","ẫ","ậ","ä","ā","ą","å","α","ά","ἀ","ἁ","ἂ","ἃ","ἄ","ἅ","ἆ","ἇ","ᾀ","ᾁ","ᾂ","ᾃ","ᾄ","ᾅ","ᾆ","ᾇ","ὰ","ά","ᾰ","ᾱ","ᾲ","ᾳ","ᾴ","ᾶ","ᾷ","а","أ"],b:["б","β","Ъ","Ь","ب"],c:["ç","ć","č","ĉ","ċ"],d:["ď","ð","đ","ƌ","ȡ","ɖ","ɗ","ᵭ","ᶁ","ᶑ","д","δ","د","ض"],e:["é","è","ẻ","ẽ","ẹ","ê","ế","ề","ể","ễ","ệ","ë","ē","ę","ě","ĕ","ė","ε","έ","ἐ","ἑ","ἒ","ἓ","ἔ","ἕ","ὲ","έ","е","ё","э","є","ə"],f:["ф","φ","ف"],g:["ĝ","ğ","ġ","ģ","г","ґ","γ","ج"],h:["ĥ","ħ","η","ή","ح","ه"],i:["í","ì","ỉ","ĩ","ị","î","ï","ī","ĭ","į","ı","ι","ί","ϊ","ΐ","ἰ","ἱ","ἲ","ἳ","ἴ","ἵ","ἶ","ἷ","ὶ","ί","ῐ","ῑ","ῒ","ΐ","ῖ","ῗ","і","ї","и"],j:["ĵ","ј","Ј"],k:["ķ","ĸ","к","κ","Ķ","ق","ك"],l:["ł","ľ","ĺ","ļ","ŀ","л","λ","ل"],m:["м","μ","م"],n:["ñ","ń","ň","ņ","ŉ","ŋ","ν","н","ن"],o:["ó","ò","ỏ","õ","ọ","ô","ố","ồ","ổ","ỗ","ộ","ơ","ớ","ờ","ở","ỡ","ợ","ø","ō","ő","ŏ","ο","ὀ","ὁ","ὂ","ὃ","ὄ","ὅ","ὸ","ό","ö","о","و","θ"],p:["п","π"],r:["ŕ","ř","ŗ","р","ρ","ر"],s:["ś","š","ş","с","σ","ș","ς","س","ص"],t:["ť","ţ","т","τ","ț","ت","ط"],u:["ú","ù","ủ","ũ","ụ","ư","ứ","ừ","ử","ữ","ự","ü","û","ū","ů","ű","ŭ","ų","µ","у"],v:["в"],w:["ŵ","ω","ώ"],x:["χ"],y:["ý","ỳ","ỷ","ỹ","ỵ","ÿ","ŷ","й","ы","υ","ϋ","ύ","ΰ","ي"],z:["ź","ž","ż","з","ζ","ز"],aa:["ع"],ae:["æ"],ch:["ч"],dj:["ђ","đ"],dz:["џ"],gh:["غ"],kh:["х","خ"],lj:["љ"],nj:["њ"],oe:["œ"],ps:["ψ"],sh:["ш"],shch:["щ"],ss:["ß"],th:["þ","ث","ذ","ظ"],ts:["ц"],ya:["я"],yu:["ю"],zh:["ж"],"(c)":["©"],A:["Á","À","Ả","Ã","Ạ","Ă","Ắ","Ằ","Ẳ","Ẵ","Ặ","Â","Ấ","Ầ","Ẩ","Ẫ","Ậ","Ä","Å","Ā","Ą","Α","Ά","Ἀ","Ἁ","Ἂ","Ἃ","Ἄ","Ἅ","Ἆ","Ἇ","ᾈ","ᾉ","ᾊ","ᾋ","ᾌ","ᾍ","ᾎ","ᾏ","Ᾰ","Ᾱ","Ὰ","Ά","ᾼ","А"],B:["Б","Β"],C:["Ć","Č","Ĉ","Ċ"],D:["Ď","Ð","Đ","Ɖ","Ɗ","Ƌ","ᴅ","ᴆ","Д","Δ"],E:["É","È","Ẻ","Ẽ","Ẹ","Ê","Ế","Ề","Ể","Ễ","Ệ","Ë","Ē","Ę","Ě","Ĕ","Ė","Ε","Έ","Ἐ","Ἑ","Ἒ","Ἓ","Ἔ","Ἕ","Έ","Ὲ","Е","Ё","Э","Є","Ə"],F:["Ф","Φ"],G:["Ğ","Ġ","Ģ","Г","Ґ","Γ"],H:["Η","Ή"],I:["Í","Ì","Ỉ","Ĩ","Ị","Î","Ï","Ī","Ĭ","Į","İ","Ι","Ί","Ϊ","Ἰ","Ἱ","Ἳ","Ἴ","Ἵ","Ἶ","Ἷ","Ῐ","Ῑ","Ὶ","Ί","И","І","Ї"],K:["К","Κ"],L:["Ĺ","Ł","Л","Λ","Ļ"],M:["М","Μ"],N:["Ń","Ñ","Ň","Ņ","Ŋ","Н","Ν"],O:["Ó","Ò","Ỏ","Õ","Ọ","Ô","Ố","Ồ","Ổ","Ỗ","Ộ","Ơ","Ớ","Ờ","Ở","Ỡ","Ợ","Ö","Ø","Ō","Ő","Ŏ","Ο","Ό","Ὀ","Ὁ","Ὂ","Ὃ","Ὄ","Ὅ","Ὸ","Ό","О","Θ","Ө"],P:["П","Π"],R:["Ř","Ŕ","Р","Ρ"],S:["Ş","Ŝ","Ș","Š","Ś","С","Σ"],T:["Ť","Ţ","Ŧ","Ț","Т","Τ"],U:["Ú","Ù","Ủ","Ũ","Ụ","Ư","Ứ","Ừ","Ử","Ữ","Ự","Û","Ü","Ū","Ů","Ű","Ŭ","Ų","У"],V:["В"],W:["Ω","Ώ"],X:["Χ"],Y:["Ý","Ỳ","Ỷ","Ỹ","Ỵ","Ÿ","Ῠ","Ῡ","Ὺ","Ύ","Ы","Й","Υ","Ϋ"],Z:["Ź","Ž","Ż","З","Ζ"],AE:["Æ"],CH:["Ч"],DJ:["Ђ"],DZ:["Џ"],KH:["Х"],LJ:["Љ"],NJ:["Њ"],PS:["Ψ"],SH:["Ш"],SHCH:["Щ"],SS:["ẞ"],TH:["Þ"],TS:["Ц"],YA:["Я"],YU:["Ю"],ZH:["Ж"]," ":["Â ","â","â","â","â","â","â","â","â","â","â","â","â¯","â","ã"]},/**
	 * Get a translated message.
	 *
	 * @param {string} category
	 * @param {string} message
	 * @param {object} params
	 * @return string
	 */
t:function(a,b,c){if("undefined"!=typeof Craft.translations[a]&&"undefined"!=typeof Craft.translations[a][b]&&(b=Craft.translations[a][b]),c)for(var d in c)c.hasOwnProperty(d)&&(b=b.replace("{"+d+"}",c[d]));return b},formatDate:function(b){return"object"!=typeof b&&(b=new Date(b)),a.datepicker.formatDate(Craft.datepickerOptions.dateFormat,b)},/**
	 * Escapes some HTML.
	 *
	 * @param {string} str
	 * @return string
	 */
escapeHtml:function(b){return a("<div/>").text(b).html()},/**
	 * Returns the text in a string that might contain HTML tags.
	 *
	 * @param {string} str
	 * @return string
	 */
getText:function(b){return a("<div/>").html(b).text()},/**
	 * Encodes a URI copmonent. Mirrors PHP's rawurlencode().
	 *
	 * @param {string} str
	 * @return string
	 * @see http://stackoverflow.com/questions/1734250/what-is-the-equivalent-of-javascripts-encodeuricomponent-in-php
	 */
encodeUriComponent:function(a){a=encodeURIComponent(a);var b={"!":"%21","*":"%2A","'":"%27","(":"%28",")":"%29"};for(var c in b){var d=new RegExp("\\"+c,"g");a=a.replace(d,b[c])}return a},/**
	 * Formats an ID out of an input name.
	 *
	 * @param {string} inputName
	 * @return string
	 */
formatInputId:function(a){return this.rtrim(a.replace(/[\[\]\\]+/g,"-"),"-")},/**
	 * @return string
	 * @param path
	 * @param params
	 */
getUrl:function(b,c,d){
// Return path if it appears to be an absolute URL.
if("string"!=typeof b&&(b=""),b.search("://")!=-1||"//"==b.substr(0,2))return b;b=Craft.trim(b,"/");var e="";
// Normalize the params
if(a.isPlainObject(c)){var f=[];for(var g in c)if(c.hasOwnProperty(g)){var h=c[g];"#"==g?e=h:null!==h&&""!==h&&f.push(g+"="+h)}c=f}c=Garnish.isArray(c)?c.join("&"):Craft.trim(c,"&?");
// Were there already any query string params in the path?
var i=b.indexOf("?");i!=-1&&(c=b.substr(i+1)+(c?"&"+c:""),b=b.substr(0,i));
// Put it all together
var j;if(d){if(j=d,b){
// Does baseUrl already contain a path?
var k=j.match(/[&\?]p=[^&]+/);k&&(j=j.replace(k[0],k[0]+"/"+b),b="")}}else j=Craft.baseUrl;
// Does the base URL already have a query string?
var i=j.indexOf("?");if("-1"!=i&&(c=j.substr(i+1)+(c?"&"+c:""),j=j.substr(0,i)),!Craft.omitScriptNameInUrls&&b)if(Craft.usePathInfo)
// Make sure that the script name is in the URL
j.search(Craft.scriptName)==-1&&(j=Craft.rtrim(j,"/")+"/"+Craft.scriptName);else{
// Move the path into the query string params
// Is the p= param already set?
if(c&&"p="==c.substr(0,2)){var l,m=c.indexOf("&");m!=-1?(l=c.substring(2,m),c=c.substr(m+1)):(l=c.substr(2),c=null),
// Just in case
l=Craft.rtrim(l),b=l+(b?"/"+b:"")}
// Now move the path into the params
c="p="+b+(c?"&"+c:""),b=null}return b&&(j=Craft.rtrim(j,"/")+"/"+b),c&&(j+="?"+c),e&&(j+="#"+e),j},/**
	 * @return string
	 * @param path
	 * @param params
	 */
getCpUrl:function(a,b){return this.getUrl(a,b,Craft.baseCpUrl)},/**
	 * @return string
	 * @param path
	 * @param params
	 */
getSiteUrl:function(a,b){return this.getUrl(a,b,Craft.baseSiteUrl)},/**
	 * Returns a resource URL.
	 *
	 * @param {string} path
	 * @param {object|string|undefined} params
	 * @return string
	 */
getResourceUrl:function(a,b){return Craft.getUrl(a,b,Craft.resourceUrl)},/**
	 * Returns an action URL.
	 *
	 * @param {string} path
	 * @param {object|string|undefined} params
	 * @return string
	 */
getActionUrl:function(a,b){return Craft.getUrl(a,b,Craft.actionUrl)},/**
	 * Redirects the window to a given URL.
	 *
	 * @param {string} url
	 */
redirectTo:function(a){document.location.href=this.getUrl(a)},/**
	 * Returns a hidden CSRF token input, if CSRF protection is enabled.
	 *
	 * @return string
	 */
getCsrfInput:function(){return Craft.csrfTokenName?'<input type="hidden" name="'+Craft.csrfTokenName+'" value="'+Craft.csrfTokenValue+'"/>':""},/**
	 * Posts an action request to the server.
	 *
	 * @param {string} action
	 * @param {object|undefined} data
	 * @param {function|undefined} callback
	 * @param {object|undefined} options
	 * @return jqXHR
	 */
postActionRequest:function(b,c,d,e){
// Make 'data' optional
"function"==typeof c&&(e=d,d=c,c={}),Craft.csrfTokenValue&&Craft.csrfTokenName&&("string"==typeof c?(c&&(c+="&"),c+=Craft.csrfTokenName+"="+Craft.csrfTokenValue):(c="object"!=typeof c?{}:a.extend({},c),c[Craft.csrfTokenName]=Craft.csrfTokenValue));var f=a.ajax(a.extend({url:Craft.getActionUrl(b),type:"POST",dataType:"json",data:c,success:d,error:function(a,b,c){d&&d(null,b,a)},complete:function(a,b){"success"!=b&&("undefined"!=typeof Craft.cp?Craft.cp.displayError():alert(Craft.t("app","An unknown error occurred.")))}},e));
// Call the 'send' callback
return e&&"function"==typeof e.send&&e.send(f),f},_waitingOnAjax:!1,_ajaxQueue:[],/**
	 * Queues up an action request to be posted to the server.
	 */
queueActionRequest:function(a,b,c,d){
// Make 'data' optional
"function"==typeof b&&(d=c,c=b,b=void 0),Craft._ajaxQueue.push([a,b,c,d]),Craft._waitingOnAjax||Craft._postNextActionRequestInQueue()},_postNextActionRequestInQueue:function(){Craft._waitingOnAjax=!0;var a=Craft._ajaxQueue.shift();Craft.postActionRequest(a[0],a[1],function(b,c,d){a[2]&&"function"==typeof a[2]&&a[2](b,c,d),Craft._ajaxQueue.length?Craft._postNextActionRequestInQueue():Craft._waitingOnAjax=!1},a[3])},/**
	 * Converts a comma-delimited string into an array.
	 *
	 * @param {string} str
	 * @return array
	 */
stringToArray:function(b){if("string"!=typeof b)return b;for(var c=b.split(","),d=0;d<c.length;d++)c[d]=a.trim(c[d]);return c},/**
	 * Expands an array of POST array-style strings into an actual array.
	 *
	 * @param {object} arr
	 * @return array
	 */
expandPostArray:function(a){var b={};for(var c in a)if(a.hasOwnProperty(c)){var d,e=a[c],f=c.match(/^(\w+)(\[.*)?/);if(f[2]){
// Get all of the nested keys
d=f[2].match(/\[[^\[\]]*\]/g);
// Chop off the brackets
for(var g=0;g<d.length;g++)d[g]=d[g].substring(1,d[g].length-1)}else d=[];d.unshift(f[1]);for(var h=b,g=0;g<d.length;g++)g<d.length-1?("object"!=typeof h[d[g]]&&(
// Figure out what this will be by looking at the next key
d[g+1]&&parseInt(d[g+1])!=d[g+1]?h[d[g]]={}:h[d[g]]=[]),h=h[d[g]]):(
// Last one. Set the value
d[g]||(d[g]=h.length),h[d[g]]=e)}return b},/**
	 * Compares two variables and returns whether they are equal in value.
	 * Recursively compares array and object values.
	 *
	 * @param obj1
	 * @param obj2
	 * @return boolean
	 */
compare:function(a,b){
// Compare the types
if(typeof a!=typeof b)return!1;if("object"==typeof a){
// Compare the lengths
if(a.length!=b.length)return!1;
// Is one of them an array but the other is not?
if(a instanceof Array!=b instanceof Array)return!1;
// If they're actual objects (not arrays), compare the keys
if(!(a instanceof Array||Craft.compare(Craft.getObjectKeys(a).sort(),Craft.getObjectKeys(b).sort())))return!1;
// Compare each value
for(var c in a)if(a.hasOwnProperty(c)&&!Craft.compare(a[c],b[c]))return!1;
// All clear
return!0}return a===b},/**
	 * Returns an array of an object's keys.
	 *
	 * @param {object} obj
	 * @return string
	 */
getObjectKeys:function(a){var b=[];for(var c in a)a.hasOwnProperty(c)&&b.push(c);return b},/**
	 * Takes an array or string of chars, and places a backslash before each one, returning the combined string.
	 *
	 * Userd by ltrim() and rtrim()
	 *
	 * @param {string|object} chars
	 * @return string
	 */
escapeChars:function(a){Garnish.isArray(a)||(a=a.split());for(var b="",c=0;c<a.length;c++)b+="\\"+a[c];return b},/**
	 * Trim characters off of the beginning of a string.
	 *
	 * @param {string} str
	 * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
ltrim:function(a,b){if(!a)return a;void 0===b&&(b=" \t\n\r\0\v");var c=new RegExp("^["+Craft.escapeChars(b)+"]+");return a.replace(c,"")},/**
	 * Trim characters off of the end of a string.
	 *
	 * @param {string} str
	 * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
rtrim:function(a,b){if(!a)return a;void 0===b&&(b=" \t\n\r\0\v");var c=new RegExp("["+Craft.escapeChars(b)+"]+$");return a.replace(c,"")},/**
	 * Trim characters off of the beginning and end of a string.
	 *
	 * @param {string} str
	 * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
trim:function(a,b){return a=Craft.ltrim(a,b),a=Craft.rtrim(a,b)},/**
	 * Filters an array.
	 *
	 * @param {object} arr
	 * @param {function} callback A user-defined callback function. If null, we'll just remove any elements that equate to false.
	 * @return array
	 */
filterArray:function(a,b){for(var c=[],d=0;d<a.length;d++){var e;e="function"==typeof b?b(a[d],d):a[d],e&&c.push(a[d])}return c},/**
	 * Returns whether an element is in an array (unlike jQuery.inArray(), which returns the element's index, or -1).
	 *
	 * @param elem
	 * @param arr
	 * @return boolean
	 */
inArray:function(b,c){return a.inArray(b,c)!=-1},/**
	 * Removes an element from an array.
	 *
	 * @param elem
	 * @param {object} arr
	 * @return boolean Whether the element could be found or not.
	 */
removeFromArray:function(b,c){var d=a.inArray(b,c);return d!=-1&&(c.splice(d,1),!0)},/**
	 * Returns the last element in an array.
	 *
	 * @param {object}
	 * @return mixed
	 */
getLast:function(a){return a.length?a[a.length-1]:null},/**
	 * Makes the first character of a string uppercase.
	 *
	 * @param {string} str
	 * @return string
	 */
uppercaseFirst:function(a){return a.charAt(0).toUpperCase()+a.slice(1)},/**
	 * Makes the first character of a string lowercase.
	 *
	 * @param {string} str
	 * @return string
	 */
lowercaseFirst:function(a){return a.charAt(0).toLowerCase()+a.slice(1)},/**
	 * Converts a number of seconds into a human-facing time duration.
	 */
secondsToHumanTimeDuration:function(a,b){"undefined"==typeof b&&(b=!0);var c=604800,d=86400,e=3600,f=60,g=Math.floor(a/c);a%=c;var h=Math.floor(a/d);a%=d;var i=Math.floor(a/e);a%=e;var j;b?(j=Math.floor(a/f),a%=f):(j=Math.round(a/f),a=0);var k=[];return g&&k.push(g+" "+(1==g?Craft.t("app","week"):Craft.t("app","weeks"))),h&&k.push(h+" "+(1==h?Craft.t("app","day"):Craft.t("app","days"))),i&&k.push(i+" "+(1==i?Craft.t("app","hour"):Craft.t("app","hours"))),!j&&(b||g||h||i)||k.push(j+" "+(1==j?Craft.t("app","minute"):Craft.t("app","minutes"))),!a&&(!b||g||h||i||j)||k.push(a+" "+(1==a?Craft.t("app","second"):Craft.t("app","seconds"))),k.join(", ")},/**
	 * Converts extended ASCII characters to ASCII.
	 *
	 * @param {string} str
	 * @return string
	 */
asciiString:function(a){for(var b="",c=0;c<a.length;c++){var d=a.charCodeAt(c),e=a.charAt(c);if(d>=32&&d<128)b+=e;else for(var f in Craft.asciiCharMap)if(Craft.asciiCharMap.hasOwnProperty(f))for(var g=0;g<Craft.asciiCharMap[f].length;g++)Craft.asciiCharMap[f][g]==e&&(b+=f)}return b},/**
	 * Prevents the outline when an element is focused by the mouse.
	 *
	 * @param elem Either an actual element or a jQuery collection.
	 */
preventOutlineOnMouseFocus:function(b){var c=a(b),d=".preventOutlineOnMouseFocus";c.on("mousedown"+d,function(){c.addClass("no-outline"),c.focus()}).on("keydown"+d+" blur"+d,function(a){a.keyCode!=Garnish.SHIFT_KEY&&a.keyCode!=Garnish.CTRL_KEY&&a.keyCode!=Garnish.CMD_KEY&&c.removeClass("no-outline")})},/**
	 * Creates a validation error list.
	 *
	 * @param {object} errors
	 * @return jQuery
	 */
createErrorList:function(b){for(var c=a(document.createElement("ul")).addClass("errors"),d=0;d<b.length;d++){var e=a(document.createElement("li"));e.appendTo(c),e.html(b[d])}return c},appendHeadHtml:function(b){if(b){
// Prune out any link tags that are already included
var c=a("link[href]");if(c.length){for(var d=[],e=0;e<c.length;e++){var f=c.eq(e).attr("href");d.push(f.replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&"))}var g=new RegExp('<link\\s[^>]*href="(?:'+d.join("|")+')".*?></script>',"g");b=b.replace(g,"")}a("head").append(b)}},appendFootHtml:function(b){if(b){
// Prune out any script tags that are already included
var c=a("script[src]");if(c.length){for(var d=[],e=0;e<c.length;e++){var f=c.eq(e).attr("src");d.push(f.replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&"))}var g=new RegExp('<script\\s[^>]*src="(?:'+d.join("|")+')".*?></script>',"g");b=b.replace(g,"")}Garnish.$bod.append(b)}},/**
	 * Initializes any common UI elements in a given container.
	 *
	 * @param {object} $container
	 */
initUiElements:function(b){a(".grid",b).grid(),a(".pane",b).pane(),a(".info",b).infoicon(),a(".checkbox-select",b).checkboxselect(),a(".fieldtoggle",b).fieldtoggle(),a(".lightswitch",b).lightswitch(),a(".nicetext",b).nicetext(),a(".pill",b).pill(),a(".formsubmit",b).formsubmit(),a(".menubtn",b).menubtn()},_elementIndexClasses:{},_elementSelectorModalClasses:{},/**
	 * Registers an element index class for a given element type.
	 *
	 * @param {string} elementType
	 * @param {function} func
	 */
registerElementIndexClass:function(a,b){if("undefined"!=typeof this._elementIndexClasses[a])throw"An element index class has already been registered for the element type “"+a+"”.";this._elementIndexClasses[a]=b},/**
	 * Registers an element selector modal class for a given element type.
	 *
	 * @param {string} elementType
	 * @param {function} func
	 */
registerElementSelectorModalClass:function(a,b){if("undefined"!=typeof this._elementSelectorModalClasses[a])throw"An element selector modal class has already been registered for the element type “"+a+"”.";this._elementSelectorModalClasses[a]=b},/**
	 * Creates a new element index for a given element type.
	 *
	 * @param {string} elementType
	 * @param $container
	 * @param {object} settings
	 * @return BaseElementIndex
	 */
createElementIndex:function(a,b,c){var d;return new(d="undefined"!=typeof this._elementIndexClasses[a]?this._elementIndexClasses[a]:Craft.BaseElementIndex)(a,b,c)},/**
	 * Creates a new element selector modal for a given element type.
	 *
	 * @param {string} elementType
	 * @param {object} settings
	 */
createElementSelectorModal:function(a,b){var c;return new(c="undefined"!=typeof this._elementSelectorModalClasses[a]?this._elementSelectorModalClasses[a]:Craft.BaseElementSelectorModal)(a,b)},/**
	 * Retrieves a value from localStorage if it exists.
	 *
	 * @param {string} key
	 * @param defaultValue
	 */
getLocalStorage:function(a,b){return a="Craft-"+Craft.systemUid+"."+a,"undefined"!=typeof localStorage&&"undefined"!=typeof localStorage[a]?JSON.parse(localStorage[a]):b},/**
	 * Saves a value to localStorage.
	 *
	 * @param {string} key
	 * @param value
	 */
setLocalStorage:function(a,b){if("undefined"!=typeof localStorage){a="Craft-"+Craft.systemUid+"."+a;
// localStorage might be filled all the way up.
// Especially likely if this is a private window in Safari 8+, where localStorage technically exists,
// but has a max size of 0 bytes.
try{localStorage[a]=JSON.stringify(b)}catch(a){}}},/**
	 * Returns element information from it's HTML.
	 *
	 * @param element
	 * @returns object
	 */
getElementInfo:function(b){var c=a(b);return c.hasClass("element")||(c=c.find(".element:first")),{id:c.data("id"),siteId:c.data("site-id"),label:c.data("label"),status:c.data("status"),url:c.data("url"),hasThumb:c.hasClass("hasthumb"),$element:c}},/**
	 * Changes an element to the requested size.
	 *
	 * @param element
	 * @param size
	 */
setElementSize:function(b,c){var d=a(b);if("small"!=c&&"large"!=c&&(c="small"),!d.hasClass(c)){var e="small"==c?"large":"small";if(d.addClass(c).removeClass(e),d.hasClass("hasthumb")){var f=d.find("> .elementthumb > img"),g="small"==c?"30":"100",h=a("<img/>",{sizes:g+"px",srcset:f.attr("srcset")||f.attr("data-pfsrcset")});f.replaceWith(h),picturefill({elements:[h[0]]})}}},/**
	 * Shows an element editor HUD.
	 *
	 * @param {object} $element
	 * @param {object} settings
	 */
showElementEditor:function(a,b){if(Garnish.hasAttr(a,"data-editable")&&!a.hasClass("disabled")&&!a.hasClass("loading"))return new Craft.ElementEditor(a,b)}}),
// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------
a.extend(a.fn,{animateLeft:function(a,b,c,d){return"ltr"==Craft.orientation?this.velocity({left:a},b,c,d):this.velocity({right:a},b,c,d)},animateRight:function(a,b,c,d){return"ltr"==Craft.orientation?this.velocity({right:a},b,c,d):this.velocity({left:a},b,c,d)},/**
	 * Disables elements by adding a .disabled class and preventing them from receiving focus.
	 */
disable:function(){return this.each(function(){var b=a(this);b.addClass("disabled"),b.data("activatable")&&b.removeAttr("tabindex")})},/**
	 * Enables elements by removing their .disabled class and allowing them to receive focus.
	 */
enable:function(){return this.each(function(){var b=a(this);b.removeClass("disabled"),b.data("activatable")&&b.attr("tabindex","0")})},/**
	 * Sets the element as the container of a grid.
	 */
grid:function(){return this.each(function(){var b=a(this),c={};b.data("item-selector")&&(c.itemSelector=b.data("item-selector")),b.data("cols")&&(c.cols=parseInt(b.data("cols"))),b.data("max-cols")&&(c.maxCols=parseInt(b.data("max-cols"))),b.data("min-col-width")&&(c.minColWidth=parseInt(b.data("min-col-width"))),b.data("mode")&&(c.mode=b.data("mode")),b.data("fill-mode")&&(c.fillMode=b.data("fill-mode")),b.data("col-class")&&(c.colClass=b.data("col-class")),b.data("snap-to-grid")&&(c.snapToGrid=!!b.data("snap-to-grid")),new Craft.Grid(this,c)})},infoicon:function(){return this.each(function(){new Craft.InfoIcon(this)})},pane:function(){return this.each(function(){a.data(this,"pane")||new Craft.Pane(this)})},/**
	 * Sets the element as a container for a checkbox select.
	 */
checkboxselect:function(){return this.each(function(){a.data(this,"checkboxselect")||new Garnish.CheckboxSelect(this)})},/**
	 * Sets the element as a field toggle trigger.
	 */
fieldtoggle:function(){return this.each(function(){a.data(this,"fieldtoggle")||new Craft.FieldToggle(this)})},lightswitch:function(b,c,d){
// param mapping
// param mapping
return"settings"==b?("string"==typeof c?(b={},b[c]=d):b=c,this.each(function(){var c=a.data(this,"lightswitch");c&&c.setSettings(b)})):(a.isPlainObject(b)||(b={}),this.each(function(){var c=a.extend({},b);Garnish.hasAttr(this,"data-value")&&(c.value=a(this).attr("data-value")),a.data(this,"lightswitch")||new Craft.LightSwitch(this,c)}))},nicetext:function(){return this.each(function(){a.data(this,"nicetext")||new Garnish.NiceText(this)})},pill:function(){return this.each(function(){a.data(this,"pill")||new Garnish.Pill(this)})},formsubmit:function(){
// Secondary form submit buttons
this.on("click",function(b){var c=a(b.currentTarget);if(!c.attr("data-confirm")||confirm(c.attr("data-confirm"))){var d;
// Is this a menu item?
d=c.data("menu")?c.data("menu").$anchor.closest("form"):c.closest("form"),c.attr("data-action")&&a('<input type="hidden" name="action"/>').val(c.attr("data-action")).appendTo(d),c.attr("data-redirect")&&a('<input type="hidden" name="redirect"/>').val(c.attr("data-redirect")).appendTo(d),c.attr("data-param")&&a('<input type="hidden"/>').attr({name:c.attr("data-param"),value:c.attr("data-value")}).appendTo(d),d.submit()}})},menubtn:function(){return this.each(function(){var b=a(this);if(!b.data("menubtn")&&b.next().hasClass("menu")){var c={};b.data("menu-anchor")&&(c.menuAnchor=b.data("menu-anchor")),new Garnish.MenuBtn(b,c)}})}}),Garnish.$doc.ready(function(){Craft.initUiElements()}),/**
 * Element index class
 */
Craft.BaseElementIndex=Garnish.Base.extend({
// Properties
// =========================================================================
initialized:!1,elementType:null,instanceState:null,sourceStates:null,sourceStatesStorageKey:null,searchTimeout:null,sourceSelect:null,$container:null,$main:null,$mainSpinner:null,isIndexBusy:!1,$sidebar:null,showingSidebar:null,sourceKey:null,sourceViewModes:null,$source:null,$customizeSourcesBtn:null,customizeSourcesModal:null,$toolbar:null,$toolbarTableRow:null,toolbarOffset:null,$search:null,searching:!1,searchText:null,$clearSearchBtn:null,$statusMenuBtn:null,statusMenu:null,status:null,$siteMenuBtn:null,siteMenu:null,siteId:null,$sortMenuBtn:null,sortMenu:null,$sortAttributesList:null,$sortDirectionsList:null,$scoreSortAttribute:null,$structureSortAttribute:null,$elements:null,$viewModeBtnTd:null,$viewModeBtnContainer:null,viewModeBtns:null,viewMode:null,view:null,_autoSelectElements:null,actions:null,actionsHeadHtml:null,actionsFootHtml:null,$selectAllContainer:null,$selectAllCheckbox:null,showingActionTriggers:!1,_$triggers:null,
// Public methods
// =========================================================================
/**
	 * Constructor
	 */
init:function(b,c,d){this.elementType=b,this.$container=c,this.setSettings(d,Craft.BaseElementIndex.defaults),
// Set the state objects
// ---------------------------------------------------------------------
this.instanceState={selectedSource:null},this.sourceStates={},
// Instance states (selected source) are stored by a custom storage key defined in the settings
this.settings.storageKey&&a.extend(this.instanceState,Craft.getLocalStorage(this.settings.storageKey),{}),
// Source states (view mode, etc.) are stored by the element type and context
this.sourceStatesStorageKey="BaseElementIndex."+this.elementType+"."+this.settings.context,a.extend(this.sourceStates,Craft.getLocalStorage(this.sourceStatesStorageKey,{})),
// Find the DOM elements
// ---------------------------------------------------------------------
this.$main=this.$container.find(".main"),this.$toolbar=this.$container.find(".toolbar:first"),this.$toolbarTableRow=this.$toolbar.children("table").children("tbody").children("tr"),this.$statusMenuBtn=this.$toolbarTableRow.find(".statusmenubtn:first"),this.$siteMenuBtn=this.$toolbarTableRow.find(".sitemenubtn:first"),this.$sortMenuBtn=this.$toolbarTableRow.find(".sortmenubtn:first"),this.$search=this.$toolbarTableRow.find(".search:first input:first"),this.$clearSearchBtn=this.$toolbarTableRow.find(".search:first > .clear"),this.$mainSpinner=this.$toolbar.find(".spinner:first"),this.$sidebar=this.$container.find(".sidebar:first"),this.$customizeSourcesBtn=this.$sidebar.children(".customize-sources"),this.$elements=this.$container.find(".elements:first"),this.$viewModeBtnTd=this.$toolbarTableRow.find(".viewbtns:first"),this.$viewModeBtnContainer=a('<div class="btngroup fullwidth"/>').appendTo(this.$viewModeBtnTd),
// Keep the toolbar at the top of the window
"index"!=this.settings.context||Garnish.isMobileBrowser(!0)||this.addListener(Garnish.$win,"resize,scroll","updateFixedToolbar");
// Initialize the sources
// ---------------------------------------------------------------------
var e=this._getSourcesInList(this.$sidebar.children("nav").children("ul"));
// No source, no party.
if(0!=e.length){
// Initialize the site menu
// ---------------------------------------------------------------------
// Is there a site menu?
if(
// The source selector
this.sourceSelect=new Garnish.Select(this.$sidebar.find("nav"),{multi:!1,allowEmpty:!1,vertical:!0,onSelectionChange:a.proxy(this,"_handleSourceSelectionChange")}),this._initSources(e),
// Customize button
this.$customizeSourcesBtn.length&&this.addListener(this.$customizeSourcesBtn,"click","createCustomizeSourcesModal"),
// Initialize the status menu
// ---------------------------------------------------------------------
this.$statusMenuBtn.length&&(this.statusMenu=this.$statusMenuBtn.menubtn().data("menubtn").menu,this.statusMenu.on("optionselect",a.proxy(this,"_handleStatusChange"))),this.$siteMenuBtn.length){this.siteMenu=this.$siteMenuBtn.menubtn().data("menubtn").menu;
// Figure out the initial site
var f=this.siteMenu.$options.filter(".sel:first");if(f.length||(f=this.siteMenu.$options.first()),f.length?this.siteId=f.data("site-id"):
// No site options -- they must not have any site permissions
this.settings.criteria={id:"0"},this.siteMenu.on("optionselect",a.proxy(this,"_handleSiteChange")),this.site){
// Do we have a different site stored in localStorage?
var g=Craft.getLocalStorage("BaseElementIndex.siteId");if(g&&g!=this.siteId){
// Is that one available here?
var h=this.siteMenu.$options.filter('[data-site-id="'+g+'"]:first');h.length&&
// Todo: switch this to siteMenu.selectOption($storedSiteOption) once Menu is updated to support that
h.trigger("click")}}}else this.settings.criteria&&this.settings.criteria.siteId&&(this.siteId=this.settings.criteria.siteId);
// Initialize the search input
// ---------------------------------------------------------------------
// Automatically update the elements after new search text has been sitting for a 1/2 second
this.addListener(this.$search,"textchange",a.proxy(function(){!this.searching&&this.$search.val()?this.startSearching():this.searching&&!this.$search.val()&&this.stopSearching(),this.searchTimeout&&clearTimeout(this.searchTimeout),this.searchTimeout=setTimeout(a.proxy(this,"updateElementsIfSearchTextChanged"),500)},this)),
// Update the elements when the Return key is pressed
this.addListener(this.$search,"keypress",a.proxy(function(a){a.keyCode==Garnish.RETURN_KEY&&(a.preventDefault(),this.searchTimeout&&clearTimeout(this.searchTimeout),this.updateElementsIfSearchTextChanged())},this)),
// Clear the search when the X button is clicked
this.addListener(this.$clearSearchBtn,"click",a.proxy(function(){this.$search.val(""),this.searchTimeout&&clearTimeout(this.searchTimeout),Garnish.isMobileBrowser(!0)||this.$search.focus(),this.stopSearching(),this.updateElementsIfSearchTextChanged()},this)),
// Auto-focus the Search box
Garnish.isMobileBrowser(!0)||this.$search.focus(),
// Initialize the sort menu
// ---------------------------------------------------------------------
// Is there a sort menu?
this.$sortMenuBtn.length&&(this.sortMenu=this.$sortMenuBtn.menubtn().data("menubtn").menu,this.$sortAttributesList=this.sortMenu.$container.children(".sort-attributes"),this.$sortDirectionsList=this.sortMenu.$container.children(".sort-directions"),this.sortMenu.on("optionselect",a.proxy(this,"_handleSortChange"))),
// Let everyone know that the UI is initialized
// ---------------------------------------------------------------------
this.initialized=!0,this.afterInit();
// Select the initial source
// ---------------------------------------------------------------------
var i,j=this.getDefaultSourceKey();if(j&&(i=this.getSourceByKey(j))){
// Expand any parent sources
var k=i.parentsUntil(".sidebar","li");k.not(":first").addClass("expanded")}j&&i||(
// Select the first source by default
i=this.$sources.first()),i.length&&this.selectSource(i),
// Load the first batch of elements!
// ---------------------------------------------------------------------
this.updateElements()}},afterInit:function(){this.onAfterInit()},get $sources(){if(this.sourceSelect)return this.sourceSelect.$items},updateFixedToolbar:function(){(this.toolbarOffset||(this.toolbarOffset=this.$toolbar.offset().top,this.toolbarOffset))&&(this.updateFixedToolbar._scrollTop=Garnish.$win.scrollTop(),Garnish.$win.width()>992&&this.updateFixedToolbar._scrollTop>this.toolbarOffset-7?(this.$toolbar.hasClass("fixed")||(this.$elements.css("padding-top",this.$toolbar.outerHeight()+24),this.$toolbar.addClass("fixed")),this.$toolbar.css("width",this.$main.width())):this.$toolbar.hasClass("fixed")&&(this.$toolbar.removeClass("fixed"),this.$toolbar.css("width",""),this.$elements.css("padding-top","")))},initSource:function(a){this.sourceSelect.addItems(a),this.initSourceToggle(a)},initSourceToggle:function(a){var b=this._getSourceToggle(a);b.length&&this.addListener(b,"click","_handleSourceToggleClick")},deinitSource:function(a){this.sourceSelect.removeItems(a),this.deinitSourceToggle(a)},deinitSourceToggle:function(a){var b=this._getSourceToggle(a);b.length&&this.removeListener(b,"click")},getDefaultSourceKey:function(){return this.instanceState.selectedSource},startSearching:function(){
// Show the clear button and add/select the Score sort option
this.$clearSearchBtn.removeClass("hidden"),this.$scoreSortAttribute||(this.$scoreSortAttribute=a('<li><a data-attr="score">'+Craft.t("app","Score")+"</a></li>"),this.sortMenu.addOptions(this.$scoreSortAttribute.children())),this.$scoreSortAttribute.prependTo(this.$sortAttributesList),this.setSortAttribute("score"),this.getSortAttributeOption("structure").addClass("disabled"),this.searching=!0},stopSearching:function(){
// Hide the clear button and Score sort option
this.$clearSearchBtn.addClass("hidden"),this.$scoreSortAttribute.detach(),this.getSortAttributeOption("structure").removeClass("disabled"),this.setStoredSortOptionsForSource(),this.searching=!1},setInstanceState:function(b,c){"object"==typeof b?a.extend(this.instanceState,b):this.instanceState[b]=c,
// Store it in localStorage too?
this.settings.storageKey&&Craft.setLocalStorage(this.settings.storageKey,this.instanceState)},getSourceState:function(a,b,c){
// Set it now so any modifications to it by whoever's calling this will be stored.
return"undefined"==typeof this.sourceStates[a]&&(this.sourceStates[a]={}),"undefined"==typeof b?this.sourceStates[a]:"undefined"!=typeof this.sourceStates[a][b]?this.sourceStates[a][b]:"undefined"!=typeof c?c:null},getSelectedSourceState:function(a,b){return this.getSourceState(this.instanceState.selectedSource,a,b)},setSelecetedSourceState:function(b,c){var d=this.getSelectedSourceState();"object"==typeof b?a.extend(d,b):d[b]=c,this.sourceStates[this.instanceState.selectedSource]=d,
// Store it in localStorage too
Craft.setLocalStorage(this.sourceStatesStorageKey,this.sourceStates)},storeSortAttributeAndDirection:function(){var a=this.getSelectedSortAttribute();"score"!=a&&this.setSelecetedSourceState({order:a,sort:this.getSelectedSortDirection()})},/**
	 * Returns the data that should be passed to the elementIndex/getElements controller action
	 * when loading elements.
	 */
getViewParams:function(){var b=a.extend({status:this.status,siteId:this.siteId,search:this.searchText,limit:this.settings.batchSize},this.settings.criteria),c={context:this.settings.context,elementType:this.elementType,source:this.instanceState.selectedSource,criteria:b,disabledElementIds:this.settings.disabledElementIds,viewState:this.getSelectedSourceState()};
// Possible that the order/sort isn't entirely accurate if we're sorting by Score
return c.viewState.order=this.getSelectedSortAttribute(),c.viewState.sort=this.getSelectedSortDirection(),"structure"==this.getSelectedSortAttribute()&&(c.collapsedElementIds=this.instanceState.collapsedElementIds),c},updateElements:function(){
// Ignore if we're not fully initialized yet
if(this.initialized){this.setIndexBusy();var b=this.getViewParams();Craft.postActionRequest("element-indexes/get-elements",b,a.proxy(function(a,c){this.setIndexAvailable(),"success"==c?this._updateView(b,a):Craft.cp.displayError(Craft.t("app","An unknown error occurred."))},this))}},updateElementsIfSearchTextChanged:function(){this.searchText!==(this.searchText=this.searching?this.$search.val():null)&&this.updateElements()},showActionTriggers:function(){
// Ignore if they're already shown
this.showingActionTriggers||(
// Hard-code the min toolbar height in case it was taller than the actions toolbar
// (prevents the elements from jumping if this ends up being a double-click)
this.$toolbar.css("min-height",this.$toolbar.height()),
// Hide any toolbar inputs
this.$toolbarTableRow.children().not(this.$selectAllContainer).addClass("hidden"),this._$triggers?this._$triggers.insertAfter(this.$selectAllContainer):this._createTriggers(),this.showingActionTriggers=!0)},submitAction:function(b,c){
// Make sure something's selected
var d=this.view.getSelectedElementIds(),e=d.length;this.view.getEnabledElements.length;if(0!=e){for(var f,g=0;g<this.actions.length;g++)if(this.actions[g].type==b){f=this.actions[g];break}if(f&&(!f.confirm||confirm(f.confirm))){
// Get ready to submit
var h=this.getViewParams(),i=a.extend(h,c,{elementAction:b,elementIds:d});
// Do it
this.setIndexBusy(),this._autoSelectElements=d,Craft.postActionRequest("element-indexes/perform-action",i,a.proxy(function(a,b){this.setIndexAvailable(),"success"==b&&(a.success?(this._updateView(h,a),a.message&&Craft.cp.displayNotice(a.message),
// There may be a new background task that needs to be run
Craft.cp.runPendingTasks()):Craft.cp.displayError(a.message))},this))}}},hideActionTriggers:function(){
// Ignore if there aren't any
this.showingActionTriggers&&(this._$triggers.detach(),this.$toolbarTableRow.children().not(this.$selectAllContainer).removeClass("hidden"),
// Unset the min toolbar height
this.$toolbar.css("min-height",""),this.showingActionTriggers=!1)},updateActionTriggers:function(){
// Do we have an action UI to update?
if(this.actions){var a=this.view.getSelectedElements().length;0!=a?(a==this.view.getEnabledElements().length?(this.$selectAllCheckbox.removeClass("indeterminate"),this.$selectAllCheckbox.addClass("checked"),this.$selectAllBtn.attr("aria-checked","true")):(this.$selectAllCheckbox.addClass("indeterminate"),this.$selectAllCheckbox.removeClass("checked"),this.$selectAllBtn.attr("aria-checked","mixed")),this.showActionTriggers()):(this.$selectAllCheckbox.removeClass("indeterminate checked"),this.$selectAllBtn.attr("aria-checked","false"),this.hideActionTriggers())}},getSelectedElements:function(){return this.view?this.view.getSelectedElements():a()},getSelectedElementIds:function(){return this.view?this.view.getSelectedElementIds():[]},getSortAttributeOption:function(a){return this.$sortAttributesList.find('a[data-attr="'+a+'"]:first')},getSelectedSortAttribute:function(){return this.$sortAttributesList.find("a.sel:first").data("attr")},setSortAttribute:function(a){
// Find the option (and make sure it actually exists)
var b=this.getSortAttributeOption(a);if(b.length){this.$sortAttributesList.find("a.sel").removeClass("sel"),b.addClass("sel");var c=b.text();this.$sortMenuBtn.attr("title",Craft.t("app","Sort by {attribute}",{attribute:c})),this.$sortMenuBtn.text(c),this.setSortDirection("asc"),"score"==a||"structure"==a?this.$sortDirectionsList.find("a").addClass("disabled"):this.$sortDirectionsList.find("a").removeClass("disabled")}},getSortDirectionOption:function(a){return this.$sortDirectionsList.find("a[data-dir="+a+"]:first")},getSelectedSortDirection:function(){return this.$sortDirectionsList.find("a.sel:first").data("dir")},getSelectedViewMode:function(){return this.getSelectedSourceState("mode")},setSortDirection:function(a){"desc"!=a&&(a="asc"),this.$sortMenuBtn.attr("data-icon",a),this.$sortDirectionsList.find("a.sel").removeClass("sel"),this.getSortDirectionOption(a).addClass("sel")},getSourceByKey:function(a){if(this.$sources){var b=this.$sources.filter('[data-key="'+a+'"]:first');if(b.length)return b}},selectSource:function(b){if(!b||!b.length)return!1;if(this.$source&&this.$source[0]&&this.$source[0]==b[0])return!1;
// Create the buttons if there's more than one mode available to this source
if(this.$source=b,this.sourceKey=b.data("key"),this.setInstanceState("selectedSource",this.sourceKey),b[0]!=this.sourceSelect.$selectedItems[0]&&this.sourceSelect.selectItem(b),Craft.cp.updateSidebarMenuLabel(),this.searching&&(
// Clear the search value without causing it to update elements
this.searchText=null,this.$search.val(""),this.stopSearching()),
// Sort menu
// ----------------------------------------------------------------------
// Does this source have a structure?
Garnish.hasAttr(this.$source,"data-has-structure")?(this.$structureSortAttribute||(this.$structureSortAttribute=a('<li><a data-attr="structure">'+Craft.t("app","Structure")+"</a></li>"),this.sortMenu.addOptions(this.$structureSortAttribute.children())),this.$structureSortAttribute.prependTo(this.$sortAttributesList)):this.$structureSortAttribute&&this.$structureSortAttribute.removeClass("sel").detach(),this.setStoredSortOptionsForSource(),
// View mode buttons
// ----------------------------------------------------------------------
// Clear out any previous view mode data
this.$viewModeBtnContainer.empty(),this.viewModeBtns={},this.viewMode=null,
// Get the new list of view modes
this.sourceViewModes=this.getViewModesForSource(),this.sourceViewModes.length>1){this.$viewModeBtnTd.removeClass("hidden");for(var c=0;c<this.sourceViewModes.length;c++){var d=this.sourceViewModes[c],e=a('<div data-view="'+d.mode+'" role="button" class="btn'+("undefined"!=typeof d.className?" "+d.className:"")+'" title="'+d.title+'"'+("undefined"!=typeof d.icon?' data-icon="'+d.icon+'"':"")+"/>").appendTo(this.$viewModeBtnContainer);this.viewModeBtns[d.mode]=e,this.addListener(e,"click",{mode:d.mode},function(a){this.selectViewMode(a.data.mode),this.updateElements()})}}else this.$viewModeBtnTd.addClass("hidden");
// Figure out which mode we should start with
var d=this.getSelectedViewMode();
// Try to keep using the current view mode
return d&&this.doesSourceHaveViewMode(d)||(d=this.viewMode&&this.doesSourceHaveViewMode(this.viewMode)?this.viewMode:this.sourceViewModes[0].mode),this.selectViewMode(d),this.onSelectSource(),!0},selectSourceByKey:function(a){var b=this.getSourceByKey(a);return!!b&&this.selectSource(b)},setStoredSortOptionsForSource:function(){
// Default to whatever's first
this.setSortAttribute(),this.setSortDirection("asc");var a=this.getSelectedSourceState("order"),b=this.getSelectedSourceState("sort");a||(
// Get the default
a=this.getDefaultSort(),Garnish.isArray(a)&&(b=a[1],a=a[0])),"asc"!=b&&"desc"!=b&&(b="asc"),this.setSortAttribute(a),this.setSortDirection(b)},getDefaultSort:function(){
// Does the source specify what to do?
// Does the source specify what to do?
return this.$source&&Garnish.hasAttr(this.$source,"data-default-sort")?this.$source.attr("data-default-sort").split(":"):[this.$sortAttributesList.find("a:first").data("attr"),"asc"]},getViewModesForSource:function(){var a=[{mode:"table",title:Craft.t("app","Display in a table"),icon:"list"}];return this.$source&&Garnish.hasAttr(this.$source,"data-has-thumbs")&&a.push({mode:"thumbs",title:Craft.t("app","Display as thumbnails"),icon:"grid"}),a},doesSourceHaveViewMode:function(a){for(var b=0;b<this.sourceViewModes.length;b++)if(this.sourceViewModes[b].mode==a)return!0;return!1},selectViewMode:function(a,b){
// Make sure that the current source supports it
b||this.doesSourceHaveViewMode(a)||(a=this.sourceViewModes[0].mode),
// Has anything changed?
a!=this.viewMode&&(
// Deselect the previous view mode
this.viewMode&&"undefined"!=typeof this.viewModeBtns[this.viewMode]&&this.viewModeBtns[this.viewMode].removeClass("active"),this.viewMode=a,this.setSelecetedSourceState("mode",this.viewMode),"undefined"!=typeof this.viewModeBtns[this.viewMode]&&this.viewModeBtns[this.viewMode].addClass("active"))},createView:function(a,b){var c=this.getViewClass(a);return new c(this,this.$elements,b)},getViewClass:function(a){switch(a){case"table":return Craft.TableElementIndexView;case"thumbs":return Craft.ThumbsElementIndexView;default:throw'View mode "'+a+'" not supported.'}},rememberDisabledElementId:function(b){var c=a.inArray(b,this.settings.disabledElementIds);c==-1&&this.settings.disabledElementIds.push(b)},forgetDisabledElementId:function(b){var c=a.inArray(b,this.settings.disabledElementIds);c!=-1&&this.settings.disabledElementIds.splice(c,1)},enableElements:function(b){b.removeClass("disabled").parents(".disabled").removeClass("disabled");for(var c=0;c<b.length;c++){var d=a(b[c]).data("id");this.forgetDisabledElementId(d)}this.onEnableElements(b)},disableElements:function(b){b.removeClass("sel").addClass("disabled");for(var c=0;c<b.length;c++){var d=a(b[c]).data("id");this.rememberDisabledElementId(d)}this.onDisableElements(b)},getElementById:function(a){return this.view.getElementById(a)},enableElementsById:function(b){b=a.makeArray(b);for(var c=0;c<b.length;c++){var d=b[c],e=this.getElementById(d);e&&e.length?this.enableElements(e):this.forgetDisabledElementId(d)}},disableElementsById:function(b){b=a.makeArray(b);for(var c=0;c<b.length;c++){var d=b[c],e=this.getElementById(d);e&&e.length?this.disableElements(e):this.rememberDisabledElementId(d)}},selectElementAfterUpdate:function(a){null===this._autoSelectElements&&(this._autoSelectElements=[]),this._autoSelectElements.push(a)},addButton:function(a){this.getButtonContainer().append(a)},isShowingSidebar:function(){return null===this.showingSidebar&&(this.showingSidebar=this.$sidebar.length&&!this.$sidebar.hasClass("hidden")),this.showingSidebar},getButtonContainer:function(){
// Is there a predesignated place where buttons should go?
if(this.settings.buttonContainer)return a(this.settings.buttonContainer);
// Add it to the page header
var b=a("#extra-headers").find("> .buttons:first");if(!b.length){var c=a("#extra-headers");c.length||(c=a('<div id="extra-headers"/>').appendTo(a("#page-header"))),b=a('<div class="buttons right"/>').appendTo(c)}return b},setIndexBusy:function(){this.$mainSpinner.removeClass("hidden"),this.isIndexBusy=!0},setIndexAvailable:function(){this.$mainSpinner.addClass("hidden"),this.isIndexBusy=!1},createCustomizeSourcesModal:function(){
// Recreate it each time
var a=new Craft.CustomizeSourcesModal(this,{onHide:function(){a.destroy()}});return a},disable:function(){this.sourceSelect&&this.sourceSelect.disable(),this.view&&this.view.disable(),this.base()},enable:function(){this.sourceSelect&&this.sourceSelect.enable(),this.view&&this.view.enable(),this.base()},
// Events
// =========================================================================
onAfterInit:function(){this.settings.onAfterInit(),this.trigger("afterInit")},onSelectSource:function(){this.settings.onSelectSource(this.sourceKey),this.trigger("selectSource",{sourceKey:this.sourceKey})},onUpdateElements:function(){this.settings.onUpdateElements(),this.trigger("updateElements")},onSelectionChange:function(){this.settings.onSelectionChange(),this.trigger("selectionChange")},onEnableElements:function(a){this.settings.onEnableElements(a),this.trigger("enableElements",{elements:a})},onDisableElements:function(a){this.settings.onDisableElements(a),this.trigger("disableElements",{elements:a})},
// Private methods
// =========================================================================
// UI state handlers
// -------------------------------------------------------------------------
_handleSourceSelectionChange:function(){
// If the selected source was just removed (maybe because its parent was collapsed),
// there won't be a selected source
// If the selected source was just removed (maybe because its parent was collapsed),
// there won't be a selected source
return this.sourceSelect.totalSelected?void(this.selectSource(this.sourceSelect.$selectedItems)&&this.updateElements()):void this.sourceSelect.selectItem(this.$sources.first())},_handleActionTriggerSubmit:function(b){b.preventDefault();var c=a(b.currentTarget);
// Make sure Craft.ElementActionTrigger isn't overriding this
if(!c.hasClass("disabled")&&!c.data("custom-handler")){var d=c.data("action"),e=Garnish.getPostData(c);this.submitAction(d,e)}},_handleMenuActionTriggerSubmit:function(b){var c=a(b.option);
// Make sure Craft.ElementActionTrigger isn't overriding this
if(!c.hasClass("disabled")&&!c.data("custom-handler")){var d=c.data("action");this.submitAction(d)}},_handleStatusChange:function(b){this.statusMenu.$options.removeClass("sel");var c=a(b.selectedOption).addClass("sel");this.$statusMenuBtn.html(c.html()),this.status=c.data("status"),this.updateElements()},_handleSiteChange:function(b){this.siteMenu.$options.removeClass("sel");var c=a(b.selectedOption).addClass("sel");this.$siteMenuBtn.html(c.html()),this.siteId=c.data("site-id"),this.initialized&&(
// Remember this site for later
Craft.setLocalStorage("BaseElementIndex.siteId",this.siteId),
// Update the elements
this.updateElements())},_handleSortChange:function(b){var c=a(b.selectedOption);c.hasClass("disabled")||c.hasClass("sel")||(
// Is this an attribute or a direction?
c.parent().parent().is(this.$sortAttributesList)?this.setSortAttribute(c.data("attr")):this.setSortDirection(c.data("dir")),this.storeSortAttributeAndDirection(),this.updateElements())},_handleSelectionChange:function(){this.updateActionTriggers(),this.onSelectionChange()},_handleSourceToggleClick:function(b){this._toggleSource(a(b.currentTarget).prev("a")),b.stopPropagation()},
// Source managemnet
// -------------------------------------------------------------------------
_getSourcesInList:function(a){return a.children("li").children("a")},_getChildSources:function(a){var b=a.siblings("ul");return this._getSourcesInList(b)},_getSourceToggle:function(a){return a.siblings(".toggle")},_initSources:function(b){for(var c=0;c<b.length;c++)this.initSource(a(b[c]))},_deinitSources:function(b){for(var c=0;c<b.length;c++)this.deinitSource(a(b[c]))},_toggleSource:function(a){a.parent("li").hasClass("expanded")?this._collapseSource(a):this._expandSource(a)},_expandSource:function(a){a.parent("li").addClass("expanded");var b=this._getChildSources(a);this._initSources(b)},_collapseSource:function(a){a.parent("li").removeClass("expanded");var b=this._getChildSources(a);this._deinitSources(b)},
// View
// -------------------------------------------------------------------------
_updateView:function(b,c){
// Cleanup
// -------------------------------------------------------------
// Kill the old view class
this.view&&(this.view.destroy(),delete this.view),
// Get rid of the old action triggers regardless of whether the new batch has actions or not
this.actions&&(this.hideActionTriggers(),this.actions=this.actionsHeadHtml=this.actionsFootHtml=this._$triggers=null),this.$selectAllContainer&&
// Git rid of the old select all button
this.$selectAllContainer.detach(),
// Batch actions setup
// -------------------------------------------------------------
"index"==this.settings.context&&c.actions&&c.actions.length&&(this.actions=c.actions,this.actionsHeadHtml=c.actionsHeadHtml,this.actionsFootHtml=c.actionsFootHtml,
// First time?
this.$selectAllContainer?(
// Reset the select all button
this.$selectAllCheckbox.removeClass("indeterminate checked"),this.$selectAllBtn.attr("aria-checked","false")):(
// Create the select all button
this.$selectAllContainer=a('<td class="selectallcontainer thin"/>'),this.$selectAllBtn=a('<div class="btn"/>').appendTo(this.$selectAllContainer),this.$selectAllCheckbox=a('<div class="checkbox"/>').appendTo(this.$selectAllBtn),this.$selectAllBtn.attr({role:"checkbox",tabindex:"0","aria-checked":"false"}),this.addListener(this.$selectAllBtn,"click",function(){0==this.view.getSelectedElements().length?this.view.selectAllElements():this.view.deselectAllElements()}),this.addListener(this.$selectAllBtn,"keydown",function(b){b.keyCode==Garnish.SPACE_KEY&&(b.preventDefault(),a(b.currentTarget).trigger("click"))})),
// Place the select all button at the beginning of the toolbar
this.$selectAllContainer.prependTo(this.$toolbarTableRow)),
// Update the view with the new container + elements HTML
// -------------------------------------------------------------
this.$elements.html(c.html),Craft.appendHeadHtml(c.headHtml),Craft.appendFootHtml(c.footHtml),picturefill();
// Create the view
// -------------------------------------------------------------
// Should we make the view selectable?
var d=this.actions||this.settings.selectable;
// Auto-select elements
// -------------------------------------------------------------
if(this.view=this.createView(this.getSelectedViewMode(),{context:this.settings.context,batchSize:this.settings.batchSize,params:b,selectable:d,multiSelect:this.actions||this.settings.multiSelect,checkboxMode:"index"==this.settings.context&&this.actions,onSelectionChange:a.proxy(this,"_handleSelectionChange")}),this._autoSelectElements){if(d)for(var e=0;e<this._autoSelectElements.length;e++)this.view.selectElementById(this._autoSelectElements[e]);this._autoSelectElements=null}
// Trigger the event
// -------------------------------------------------------------
this.onUpdateElements()},_createTriggers:function(){for(var b=[],c=[],d=[],e=0;e<this.actions.length;e++){var f=this.actions[e];if(f.trigger){var g=a('<form id="'+Craft.formatInputId(f.type)+'-actiontrigger"/>').data("action",f.type).append(f.trigger);this.addListener(g,"submit","_handleActionTriggerSubmit"),b.push(g)}else f.destructive?d.push(f):c.push(f)}var h;if(c.length||d.length){var i=a("<form/>");h=a('<div class="btn menubtn" data-icon="settings" title="'+Craft.t("app","Actions")+'"/>').appendTo(i);var j=a('<ul class="menu"/>').appendTo(i),k=this._createMenuTriggerList(c),l=this._createMenuTriggerList(d);k&&k.appendTo(j),k&&l&&a("<hr/>").appendTo(j),l&&l.appendTo(j),b.push(i)}
// Add a filler TD
b.push(""),this._$triggers=a();for(var e=0;e<b.length;e++){var m=a('<td class="'+(e<b.length-1?"thin":"")+'"/>').append(b[e]);this._$triggers=this._$triggers.add(m)}this._$triggers.insertAfter(this.$selectAllContainer),Craft.appendHeadHtml(this.actionsHeadHtml),Craft.appendFootHtml(this.actionsFootHtml),Craft.initUiElements(this._$triggers),h&&h.data("menubtn").on("optionSelect",a.proxy(this,"_handleMenuActionTriggerSubmit"))},_createMenuTriggerList:function(b){if(b&&b.length){for(var c=a("<ul/>"),d=0;d<b.length;d++){var e=b[d].type;a("<li/>").append(a("<a/>",{id:Craft.formatInputId(e)+"-actiontrigger","data-action":e,text:b[d].name})).appendTo(c)}return c}}},
// Static Properties
// =============================================================================
{defaults:{context:"index",storageKey:null,criteria:null,batchSize:50,disabledElementIds:[],selectable:!1,multiSelect:!1,buttonContainer:null,onAfterInit:a.noop,onSelectSource:a.noop,onUpdateElements:a.noop,onSelectionChange:a.noop,onEnableElements:a.noop,onDisableElements:a.noop}}),/**
* Base Element Index View
*/
Craft.BaseElementIndexView=Garnish.Base.extend({$container:null,$loadingMoreSpinner:null,$elementContainer:null,$scroller:null,elementIndex:null,elementSelect:null,loadingMore:!1,_totalVisible:null,_morePending:null,_handleEnableElements:null,_handleDisableElements:null,init:function(b,c,d){this.elementIndex=b,this.$container=a(c),this.setSettings(d,Craft.BaseElementIndexView.defaults),
// Create a "loading-more" spinner
this.$loadingMoreSpinner=a('<div class="centeralign hidden"><div class="spinner loadingmore"></div></div>').insertAfter(this.$container),
// Get the actual elements container and its child elements
this.$elementContainer=this.getElementContainer();var e=this.$elementContainer.children();this.setTotalVisible(e.length),this.setMorePending(this.settings.batchSize&&e.length==this.settings.batchSize),this.settings.selectable&&(this.elementSelect=new Garnish.Select(this.$elementContainer,e.filter(":not(.disabled)"),{multi:this.settings.multiSelect,vertical:this.isVerticalList(),handle:"index"==this.settings.context?".checkbox, .element:first":null,filter:":not(a):not(.toggle)",checkboxMode:this.settings.checkboxMode,onSelectionChange:a.proxy(this,"onSelectionChange")}),this._handleEnableElements=a.proxy(function(a){this.elementSelect.addItems(a.elements)},this),this._handleDisableElements=a.proxy(function(a){this.elementSelect.removeItems(a.elements)},this),this.elementIndex.on("enableElements",this._handleEnableElements),this.elementIndex.on("disableElements",this._handleDisableElements)),
// Enable inline element editing if this is an index page
"index"==this.settings.context&&(this._handleElementEditing=a.proxy(function(b){var c=a(b.target);if("A"!=c.prop("nodeName")){var d;if(c.hasClass("element"))d=c;else if(d=c.closest(".element"),!d.length)return;Garnish.hasAttr(d,"data-editable")&&this.createElementEditor(d)}},this),this.addListener(this.$elementContainer,"dblclick",this._handleElementEditing),a.isTouchCapable()&&this.addListener(this.$elementContainer,"taphold",this._handleElementEditing)),
// Give sub-classes a chance to do post-initialization stuff here
this.afterInit(),
// Set up lazy-loading
this.settings.batchSize&&("index"==this.settings.context?this.$scroller=Garnish.$win:this.$scroller=this.elementIndex.$main,this.$scroller.scrollTop(0),this.addListener(this.$scroller,"scroll","maybeLoadMore"),this.maybeLoadMore())},getElementContainer:function(){throw"Classes that extend Craft.BaseElementIndexView must supply a getElementContainer() method."},afterInit:function(){},getAllElements:function(){return this.$elementContainer.children()},getEnabledElements:function(){return this.$elementContainer.children(":not(.disabled)")},getElementById:function(a){var b=this.$elementContainer.children('[data-id="'+a+'"]:first');return b.length?b:null},getSelectedElements:function(){if(!this.elementSelect)throw"This view is not selectable.";return this.elementSelect.$selectedItems},getSelectedElementIds:function(){var a=this.getSelectedElements(),b=[];if(a)for(var c=0;c<a.length;c++)b.push(a.eq(c).data("id"));return b},selectElement:function(a){if(!this.elementSelect)throw"This view is not selectable.";return this.elementSelect.selectItem(a,!0),!0},selectElementById:function(a){if(!this.elementSelect)throw"This view is not selectable.";var b=this.getElementById(a);return!!b&&(this.elementSelect.selectItem(b,!0),!0)},selectAllElements:function(){this.elementSelect.selectAll()},deselectAllElements:function(){this.elementSelect.deselectAll()},isVerticalList:function(){return!1},getTotalVisible:function(){return this._totalVisible},setTotalVisible:function(a){this._totalVisible=a},getMorePending:function(){return this._morePending},setMorePending:function(a){this._morePending=a},/**
     * Checks if the user has reached the bottom of the scroll area, and if so, loads the next batch of elemets.
     */
maybeLoadMore:function(){this.canLoadMore()&&this.loadMore()},/**
     * Returns whether the user has reached the bottom of the scroll area.
     */
canLoadMore:function(){if(!this.getMorePending()||!this.settings.batchSize)return!1;
// Check if the user has reached the bottom of the scroll area
if(this.$scroller[0]==Garnish.$win[0]){var a=Garnish.$win.innerHeight(),b=Garnish.$win.scrollTop(),c=this.$container.offset().top,d=this.$container.height();return a+b>=c+d}var e=this.$scroller.prop("scrollHeight"),f=this.$scroller.scrollTop(),d=this.$scroller.outerHeight();return e-f<=d+15},/**
     * Loads the next batch of elements.
     */
loadMore:function(){if(this.getMorePending()&&!this.loadingMore&&this.settings.batchSize){this.loadingMore=!0,this.$loadingMoreSpinner.removeClass("hidden"),this.removeListener(this.$scroller,"scroll");var b=this.getLoadMoreParams();Craft.postActionRequest("element-indexes/get-more-elements",b,a.proxy(function(b,c){if(this.loadingMore=!1,this.$loadingMoreSpinner.addClass("hidden"),"success"==c){var d=a(b.html);this.appendElements(d),Craft.appendHeadHtml(b.headHtml),Craft.appendFootHtml(b.footHtml),this.elementSelect&&(this.elementSelect.addItems(d.filter(":not(.disabled)")),this.elementIndex.updateActionTriggers()),this.setTotalVisible(this.getTotalVisible()+d.length),this.setMorePending(d.length==this.settings.batchSize),
// Is there room to load more right now?
this.addListener(this.$scroller,"scroll","maybeLoadMore"),this.maybeLoadMore()}},this))}},getLoadMoreParams:function(){
// Use the same params that were passed when initializing this view
var b=a.extend(!0,{},this.settings.params);return b.criteria.offset=this.getTotalVisible(),b},appendElements:function(a){a.appendTo(this.$elementContainer),this.onAppendElements(a)},onAppendElements:function(a){this.settings.onAppendElements(a),this.trigger("appendElements",{newElements:a})},onSelectionChange:function(){this.settings.onSelectionChange(),this.trigger("selectionChange")},createElementEditor:function(a){new Craft.ElementEditor(a)},disable:function(){this.elementSelect&&this.elementSelect.disable()},enable:function(){this.elementSelect&&this.elementSelect.enable()},destroy:function(){
// Remove the "loading-more" spinner, since we added that outside of the view container
this.$loadingMoreSpinner.remove(),
// Delete the element select
this.elementSelect&&(this.elementIndex.off("enableElements",this._handleEnableElements),this.elementIndex.off("disableElements",this._handleDisableElements),this.elementSelect.destroy(),delete this.elementSelect),this.base()}},{defaults:{context:"index",batchSize:null,params:null,selectable:!1,multiSelect:!1,checkboxMode:!1,onAppendElements:a.noop,onSelectionChange:a.noop}}),/**
 * Element Select input
 */
Craft.BaseElementSelectInput=Garnish.Base.extend({elementSelect:null,elementSort:null,modal:null,elementEditor:null,$container:null,$elementsContainer:null,$elements:null,$addElementBtn:null,_initialized:!1,init:function(b){
// Normalize the settings and set them
// ---------------------------------------------------------------------
// Are they still passing in a bunch of arguments?
if(!a.isPlainObject(b)){for(var c={},d=["id","name","elementType","sources","criteria","sourceElementId","limit","modalStorageKey","fieldId"],e=0;e<d.length&&"undefined"!=typeof arguments[e];e++)c[d[e]]=arguments[e];b=c}this.setSettings(b,Craft.BaseElementSelectInput.defaults),
// Apply the storage key prefix
this.settings.modalStorageKey&&(this.modalStorageKey="BaseElementSelectInput."+this.settings.modalStorageKey),
// No reason for this to be sortable if we're only allowing 1 selection
1==this.settings.limit&&(this.settings.sortable=!1),this.$container=this.getContainer(),
// Store a reference to this class
this.$container.data("elementSelect",this),this.$elementsContainer=this.getElementsContainer(),this.$addElementBtn=this.getAddElementsBtn(),this.$addElementBtn&&1==this.settings.limit&&this.$addElementBtn.css("position","absolute").css("top",0).css(Craft.left,0),this.initElementSelect(),this.initElementSort(),this.resetElements(),this.$addElementBtn&&this.addListener(this.$addElementBtn,"activate","showModal"),this._initialized=!0},get totalSelected(){return this.$elements.length},getContainer:function(){return a("#"+this.settings.id)},getElementsContainer:function(){return this.$container.children(".elements")},getElements:function(){return this.$elementsContainer.children()},getAddElementsBtn:function(){return this.$container.children(".btn.add")},initElementSelect:function(){this.settings.selectable&&(this.elementSelect=new Garnish.Select({multi:this.settings.sortable,filter:":not(.delete)"}))},initElementSort:function(){this.settings.sortable&&(this.elementSort=new Garnish.DragSort({container:this.$elementsContainer,filter:this.settings.selectable?a.proxy(function(){
// Only return all the selected items if the target item is selected
// Only return all the selected items if the target item is selected
return this.elementSort.$targetItem.hasClass("sel")?this.elementSelect.getSelectedItems():this.elementSort.$targetItem},this):null,ignoreHandleSelector:".delete",axis:this.getElementSortAxis(),collapseDraggees:!0,magnetStrength:4,helperLagBase:1.5,onSortChange:this.settings.selectable?a.proxy(function(){this.elementSelect.resetItemOrder()},this):null}))},getElementSortAxis:function(){return"list"==this.settings.viewMode?"y":null},canAddMoreElements:function(){return!this.settings.limit||this.$elements.length<this.settings.limit},updateAddElementsBtn:function(){this.canAddMoreElements()?this.enableAddElementsBtn():this.disableAddElementsBtn()},disableAddElementsBtn:function(){this.$addElementBtn&&!this.$addElementBtn.hasClass("disabled")&&(this.$addElementBtn.addClass("disabled"),1==this.settings.limit&&(this._initialized?this.$addElementBtn.velocity("fadeOut",Craft.BaseElementSelectInput.ADD_FX_DURATION):this.$addElementBtn.hide()))},enableAddElementsBtn:function(){this.$addElementBtn&&this.$addElementBtn.hasClass("disabled")&&(this.$addElementBtn.removeClass("disabled"),1==this.settings.limit&&(this._initialized?this.$addElementBtn.velocity("fadeIn",Craft.BaseElementSelectInput.REMOVE_FX_DURATION):this.$addElementBtn.show()))},resetElements:function(){this.$elements=a(),this.addElements(this.getElements())},addElements:function(b){this.settings.selectable&&this.elementSelect.addItems(b),this.settings.sortable&&this.elementSort.addItems(b),this.settings.editable&&(this._handleShowElementEditor=a.proxy(function(b){this.elementEditor=Craft.showElementEditor(a(b.currentTarget),this.settings.editorSettings)},this),this.addListener(b,"dblclick",this._handleShowElementEditor),a.isTouchCapable()&&this.addListener(b,"taphold",this._handleShowElementEditor)),b.find(".delete").on("click",a.proxy(function(b){this.removeElement(a(b.currentTarget).closest(".element"))},this)),this.$elements=this.$elements.add(b),this.updateAddElementsBtn()},removeElements:function(a){if(this.settings.selectable&&this.elementSelect.removeItems(a),this.modal){for(var b=[],c=0;c<a.length;c++){var d=a.eq(c).data("id");d&&b.push(d)}b.length&&this.modal.elementIndex.enableElementsById(b)}
// Disable the hidden input in case the form is submitted before this element gets removed from the DOM
a.children("input").prop("disabled",!0),this.$elements=this.$elements.not(a),this.updateAddElementsBtn(),this.onRemoveElements()},removeElement:function(a){this.removeElements(a),this.animateElementAway(a,function(){a.remove()})},animateElementAway:function(a,b){a.css("z-index",0);var c={opacity:-1};c["margin-"+Craft.left]=-(a.outerWidth()+parseInt(a.css("margin-"+Craft.right))),"list"!=this.settings.viewMode&&0!=this.$elements.length||(c["margin-bottom"]=-(a.outerHeight()+parseInt(a.css("margin-bottom")))),a.velocity(c,Craft.BaseElementSelectInput.REMOVE_FX_DURATION,b)},showModal:function(){
// Make sure we haven't reached the limit
this.canAddMoreElements()&&(this.modal?this.modal.show():this.modal=this.createModal())},createModal:function(){return Craft.createElementSelectorModal(this.settings.elementType,this.getModalSettings())},getModalSettings:function(){return a.extend({closeOtherModals:!1,storageKey:this.modalStorageKey,sources:this.settings.sources,criteria:this.settings.criteria,multiSelect:1!=this.settings.limit,disabledElementIds:this.getDisabledElementIds(),onSelect:a.proxy(this,"onModalSelect")},this.settings.modalSettings)},getSelectedElementIds:function(){for(var a=[],b=0;b<this.$elements.length;b++)a.push(this.$elements.eq(b).data("id"));return a},getDisabledElementIds:function(){var a=this.getSelectedElementIds();return this.settings.sourceElementId&&a.push(this.settings.sourceElementId),a},onModalSelect:function(a){if(this.settings.limit){
// Cut off any excess elements
var b=this.settings.limit-this.$elements.length;a.length>b&&(a=a.slice(0,b))}this.selectElements(a),this.updateDisabledElementsInModal()},selectElements:function(a){for(var b=0;b<a.length;b++){var c=a[b],d=this.createNewElement(c);this.appendElement(d),this.addElements(d),this.animateElementIntoPlace(c.$element,d)}this.onSelectElements(a)},createNewElement:function(a){var b=a.$element.clone();
// Make a couple tweaks
return Craft.setElementSize(b,"large"==this.settings.viewMode?"large":"small"),b.addClass("removable"),b.prepend('<input type="hidden" name="'+this.settings.name+'[]" value="'+a.id+'"><a class="delete icon" title="'+Craft.t("app","Remove")+'"></a>'),b},appendElement:function(a){a.appendTo(this.$elementsContainer)},animateElementIntoPlace:function(a,b){var c=a.offset(),d=b.offset(),e=b.clone().appendTo(Garnish.$bod);b.css("visibility","hidden"),e.css({position:"absolute",zIndex:1e4,top:c.top,left:c.left});var f={top:d.top,left:d.left};e.velocity(f,Craft.BaseElementSelectInput.ADD_FX_DURATION,function(){e.remove(),b.css("visibility","visible")})},updateDisabledElementsInModal:function(){this.modal.elementIndex&&this.modal.elementIndex.disableElementsById(this.getDisabledElementIds())},getElementById:function(a){for(var b=0;b<this.$elements.length;b++){var c=this.$elements.eq(b);if(c.data("id")==a)return c}},onSelectElements:function(a){this.trigger("selectElements",{elements:a}),this.settings.onSelectElements(a)},onRemoveElements:function(){this.trigger("removeElements"),this.settings.onRemoveElements()}},{ADD_FX_DURATION:200,REMOVE_FX_DURATION:200,defaults:{id:null,name:null,fieldId:null,elementType:null,sources:null,criteria:{},sourceElementId:null,viewMode:"list",limit:null,modalStorageKey:null,modalSettings:{},onSelectElements:a.noop,onRemoveElements:a.noop,sortable:!0,selectable:!0,editable:!0,editorSettings:{}}}),/**
 * Element selector modal class
 */
Craft.BaseElementSelectorModal=Garnish.Modal.extend({elementType:null,elementIndex:null,$body:null,$selectBtn:null,$sidebar:null,$sources:null,$sourceToggles:null,$main:null,$search:null,$elements:null,$tbody:null,$primaryButtons:null,$secondaryButtons:null,$cancelBtn:null,$footerSpinner:null,init:function(b,c){this.elementType=b,this.setSettings(c,Craft.BaseElementSelectorModal.defaults);
// Build the modal
var d=a('<div class="modal elementselectormodal"></div>').appendTo(Garnish.$bod),e=a('<div class="body"><div class="spinner big"></div></div>').appendTo(d),f=a('<div class="footer"/>').appendTo(d);this.base(d,this.settings),this.$footerSpinner=a('<div class="spinner hidden"/>').appendTo(f),this.$primaryButtons=a('<div class="buttons right"/>').appendTo(f),this.$secondaryButtons=a('<div class="buttons left secondary-buttons"/>').appendTo(f),this.$cancelBtn=a('<div class="btn">'+Craft.t("app","Cancel")+"</div>").appendTo(this.$primaryButtons),this.$selectBtn=a('<div class="btn disabled submit">'+Craft.t("app","Select")+"</div>").appendTo(this.$primaryButtons),this.$body=e,this.addListener(this.$cancelBtn,"activate","cancel"),this.addListener(this.$selectBtn,"activate","selectElements")},onFadeIn:function(){this.elementIndex?
// Auto-focus the Search box
Garnish.isMobileBrowser(!0)||this.elementIndex.$search.focus():this._createElementIndex(),this.base()},onSelectionChange:function(){this.updateSelectBtnState()},updateSelectBtnState:function(){this.$selectBtn&&(this.elementIndex.getSelectedElements().length?this.enableSelectBtn():this.disableSelectBtn())},enableSelectBtn:function(){this.$selectBtn.removeClass("disabled")},disableSelectBtn:function(){this.$selectBtn.addClass("disabled")},enableCancelBtn:function(){this.$cancelBtn.removeClass("disabled")},disableCancelBtn:function(){this.$cancelBtn.addClass("disabled")},showFooterSpinner:function(){this.$footerSpinner.removeClass("hidden")},hideFooterSpinner:function(){this.$footerSpinner.addClass("hidden")},cancel:function(){this.$cancelBtn.hasClass("disabled")||this.hide()},selectElements:function(){if(this.elementIndex&&this.elementIndex.getSelectedElements().length){
// TODO: This code shouldn't know about views' elementSelect objects
this.elementIndex.view.elementSelect.clearMouseUpTimeout();var a=this.elementIndex.getSelectedElements(),b=this.getElementInfo(a);this.onSelect(b),this.settings.disableElementsOnSelect&&this.elementIndex.disableElements(this.elementIndex.getSelectedElements()),this.settings.hideOnSelect&&this.hide()}},getElementInfo:function(b){for(var c=[],d=0;d<b.length;d++){var e=a(b[d]);c.push(Craft.getElementInfo(e))}return c},show:function(){this.updateSelectBtnState(),this.base()},onSelect:function(a){this.settings.onSelect(a)},disable:function(){this.elementIndex&&this.elementIndex.disable(),this.base()},enable:function(){this.elementIndex&&this.elementIndex.enable(),this.base()},_createElementIndex:function(){
// Get the modal body HTML based on the settings
var b={context:"modal",elementType:this.elementType,sources:this.settings.sources};Craft.postActionRequest("elements/get-modal-body",b,a.proxy(function(b,c){"success"==c&&(this.$body.html(b.html),this.$body.has(".sidebar:not(.hidden)").length&&this.$body.addClass("has-sidebar"),
// Initialize the element index
this.elementIndex=Craft.createElementIndex(this.elementType,this.$body,{context:"modal",storageKey:this.settings.storageKey,criteria:this.settings.criteria,disabledElementIds:this.settings.disabledElementIds,selectable:!0,multiSelect:this.settings.multiSelect,buttonContainer:this.$secondaryButtons,onSelectionChange:a.proxy(this,"onSelectionChange")}),
// Double-clicking or double-tapping should select the elements
this.addListener(this.elementIndex.$elements,"doubletap","selectElements"))},this))}},{defaults:{resizable:!0,storageKey:null,sources:null,criteria:null,multiSelect:!1,disabledElementIds:[],disableElementsOnSelect:!1,hideOnSelect:!0,onCancel:a.noop,onSelect:a.noop}}),/**
 * Input Generator
 */
Craft.BaseInputGenerator=Garnish.Base.extend({$source:null,$target:null,$form:null,settings:null,listening:null,timeout:null,init:function(b,c,d){this.$source=a(b),this.$target=a(c),this.$form=this.$source.closest("form"),this.setSettings(d),this.startListening()},setNewSource:function(b){var c=this.listening;this.stopListening(),this.$source=a(b),c&&this.startListening()},startListening:function(){this.listening||(this.listening=!0,this.addListener(this.$source,"textchange","onTextChange"),this.addListener(this.$form,"submit","onFormSubmit"),this.addListener(this.$target,"focus",function(){this.addListener(this.$target,"textchange","stopListening"),this.addListener(this.$target,"blur",function(){this.removeListener(this.$target,"textchange,blur")})}))},stopListening:function(){this.listening&&(this.listening=!1,this.removeAllListeners(this.$source),this.removeAllListeners(this.$target),this.removeAllListeners(this.$form))},onTextChange:function(){this.timeout&&clearTimeout(this.timeout),this.timeout=setTimeout(a.proxy(this,"updateTarget"),250)},onFormSubmit:function(){this.timeout&&clearTimeout(this.timeout),this.updateTarget()},updateTarget:function(){var a=this.$source.val(),b=this.generateTargetValue(a);this.$target.val(b),this.$target.trigger("textchange")},generateTargetValue:function(a){return a}}),/**
 * Admin table class
 */
Craft.AdminTable=Garnish.Base.extend({settings:null,totalItems:null,sorter:null,$noItems:null,$table:null,$tbody:null,$deleteBtns:null,init:function(b){this.setSettings(b,Craft.AdminTable.defaults),this.settings.allowDeleteAll||(this.settings.minItems=1),this.$noItems=a(this.settings.noItemsSelector),this.$table=a(this.settings.tableSelector),this.$tbody=this.$table.children("tbody"),this.totalItems=this.$tbody.children().length,this.settings.sortable&&(this.sorter=new Craft.DataTableSorter(this.$table,{onSortChange:a.proxy(this,"reorderItems")})),this.$deleteBtns=this.$table.find(".delete"),this.addListener(this.$deleteBtns,"click","handleDeleteBtnClick"),this.updateUI()},addRow:function(b){if(!(this.settings.maxItems&&this.totalItems>=this.settings.maxItems)){var c=a(b).appendTo(this.$tbody),d=c.find(".delete");this.settings.sortable&&this.sorter.addItems(c),this.$deleteBtns=this.$deleteBtns.add(d),this.addListener(d,"click","handleDeleteBtnClick"),this.totalItems++,this.updateUI()}},reorderItems:function(){if(!this.settings.sortable)return!1;for(var b=[],c=0;c<this.sorter.$items.length;c++){var d=a(this.sorter.$items[c]).attr(this.settings.idAttribute);b.push(d)}
// Send it to the server
var e={ids:JSON.stringify(b)};Craft.postActionRequest(this.settings.reorderAction,e,a.proxy(function(a,c){"success"==c&&(a.success?(this.onReorderItems(b),Craft.cp.displayNotice(Craft.t("app",this.settings.reorderSuccessMessage))):Craft.cp.displayError(Craft.t("app",this.settings.reorderFailMessage)))},this))},handleDeleteBtnClick:function(b){if(!(this.settings.minItems&&this.totalItems<=this.settings.minItems)){var c=a(b.target).closest("tr");this.confirmDeleteItem(c)&&this.deleteItem(c)}},confirmDeleteItem:function(a){var b=this.getItemName(a);return confirm(Craft.t("app",this.settings.confirmDeleteMessage,{name:b}))},deleteItem:function(b){var c={id:this.getItemId(b)};Craft.postActionRequest(this.settings.deleteAction,c,a.proxy(function(a,c){"success"==c&&this.handleDeleteItemResponse(a,b)},this))},handleDeleteItemResponse:function(a,b){var c=this.getItemId(b),d=this.getItemName(b);a.success?(this.sorter&&this.sorter.removeItems(b),b.remove(),this.totalItems--,this.updateUI(),this.onDeleteItem(c),Craft.cp.displayNotice(Craft.t("app",this.settings.deleteSuccessMessage,{name:d}))):Craft.cp.displayError(Craft.t("app",this.settings.deleteFailMessage,{name:d}))},onReorderItems:function(a){this.settings.onReorderItems(a)},onDeleteItem:function(a){this.settings.onDeleteItem(a)},getItemId:function(a){return a.attr(this.settings.idAttribute)},getItemName:function(a){return a.attr(this.settings.nameAttribute)},updateUI:function(){
// Disable the sort buttons if there's only one row
if(
// Show the "No Whatever Exists" message if there aren't any
0==this.totalItems?(this.$table.hide(),this.$noItems.removeClass("hidden")):(this.$table.show(),this.$noItems.addClass("hidden")),this.settings.sortable){var b=this.$table.find(".move");1==this.totalItems?b.addClass("disabled"):b.removeClass("disabled")}
// Disable the delete buttons if we've reached the minimum items
this.settings.minItems&&this.totalItems<=this.settings.minItems?this.$deleteBtns.addClass("disabled"):this.$deleteBtns.removeClass("disabled"),
// Hide the New Whatever button if we've reached the maximum items
this.settings.newItemBtnSelector&&(this.settings.maxItems&&this.totalItems>=this.settings.maxItems?a(this.settings.newItemBtnSelector).addClass("hidden"):a(this.settings.newItemBtnSelector).removeClass("hidden"))}},{defaults:{tableSelector:null,noItemsSelector:null,newItemBtnSelector:null,idAttribute:"data-id",nameAttribute:"data-name",sortable:!1,allowDeleteAll:!0,minItems:0,maxItems:null,reorderAction:null,deleteAction:null,reorderSuccessMessage:Craft.t("app","New order saved."),reorderFailMessage:Craft.t("app","Couldn’t save new order."),confirmDeleteMessage:Craft.t("app","Are you sure you want to delete “{name}”?"),deleteSuccessMessage:Craft.t("app","“{name}” deleted."),deleteFailMessage:Craft.t("app","Couldn’t delete “{name}”."),onReorderItems:a.noop,onDeleteItem:a.noop}}),/**
 * Asset image editor class
 */
// TODO: Sometimes the rotation messes up the zoom
// TODO: Rotating by 0.1 degree kills stuff for non-square images?
Craft.AssetImageEditor=Garnish.Modal.extend({assetId:0,imageUrl:"",
// Original parameters for reference
originalImageHeight:0,originalImageWidth:0,aspectRatio:0,
// The currently resized image dimensions
imageHeight:0,imageWidth:0,canvas:null,canvasContext:null,canvasImageHeight:0,canvasImageWidth:0,
// Image and frame rotation degrees
rotation:0,frameRotation:0,
// TODO: should this be limited to 50 (or some other arbitrary number)?
// Operation stack
doneOperations:[],undoneOperations:[],
// zoom ratio for the image
zoomRatio:1,
// Used when dragging the slider
previousSliderValue:0,
// Used to store values when releasing the slider
previousSavedSliderValue:0,paddingSize:24,imageLoaded:!1,animationInProgress:!1,animationFrames:20,drawGridLines:!1,$img:null,init:function(b){this.setSettings(Craft.AssetImageEditor.defaults),this.assetId=b,this.imageHeight=0,this.imageWidth=0,this.originalImageHeight=0,this.originalImageWidth=0,this.imageUrl="",this.aspectRatio=0,this.canvasImageHeight=0,this.canvasImageWidth=0,this.imageLoaded=!1,this.canvas=null,this.$img=null,this.rotation=0,this.animationInProgress=!1,this.doneOperations=[],this.undoneOperations=[],this.previousSliderValue=0,this.previousSavedSliderValue=0;
// Build the modal
var c=a('<div class="modal asset-editor"></div>').appendTo(Garnish.$bod),d=a('<div class="body"><div class="spinner big"></div></div>').appendTo(c),e=a('<div class="footer"/>').appendTo(c);this.base(c,this.settings),this.$buttons=a('<div class="buttons rightalign"/>').appendTo(e),this.$cancelBtn=a('<div class="btn">'+Craft.t("app","Cancel")+"</div>").appendTo(this.$buttons),this.$selectBtn=a('<div class="btn disabled submit">'+Craft.t("app","Replace Image")+"</div>").appendTo(this.$buttons),this.$selectBtn=a('<div class="btn disabled submit">'+Craft.t("app","Save as New Image")+"</div>").appendTo(this.$buttons),this.$body=d,this.addListener(this.$cancelBtn,"activate","cancel"),this.removeListener(this.$shade,"click"),Craft.postActionRequest("assets/image-editor",{assetId:this.assetId},a.proxy(this,"loadEditor"))},loadEditor:function(b){this.$body.html(b.html),this.canvas=this.$body.find("canvas")[0],this.canvasContext=this.canvas.getContext("2d"),this.imageHeight=this.originalImageHeight=b.imageData.height,this.imageWidth=this.originalImageWidth=b.imageData.width,this.imageUrl=b.imageData.url,this.aspectRatio=this.imageHeight/this.imageWidth,this.initImage(a.proxy(this,"updateSizeAndPosition")),this.addListeners()},updateSizeAndPosition:function(){this.imageLoaded?this.redrawEditor():this.base()},cancel:function(){this.hide(),this.destroy()},hide:function(){this.removeListeners(),this.base()},initImage:function(b){this.$img=a("<img />"),this.$img.attr("src",this.imageUrl).on("load",a.proxy(function(){this.imageLoaded=!0,b()},this))},redrawEditor:function(){var a=600,b=600;this.imageLoaded&&(a=this.originalImageHeight,b=this.originalImageWidth);var c=Garnish.$win.height()-4*this.paddingSize-this.$container.find(".footer").outerHeight(),d=Garnish.$win.width()-5*this.paddingSize-this.$container.find(".image-tools").outerWidth(),e=Math.max(parseInt(this.$container.find(".image-tools").css("min-height"),10),Math.min(c,d)),f=e+this.$container.find(".image-tools").outerWidth()+3*this.paddingSize,g=e+this.$container.find(".footer").outerHeight()+2*this.paddingSize;this.$container.width(f).height(g).find(".image-holder").width(e).height(e),this.canvasImageHeight=this.canvasImageWidth=e,this.$container.find(".image-tools").height(e+2*this.paddingSize),
// Re-center.
this.$container.css("left",Math.round((Garnish.$win.width()-f)/2)),this.$container.css("top",Math.round((Garnish.$win.height()-g)/2)),this.imageLoaded&&this.renderImage(!0)},renderImage:function(a,b){this.canvas.height=this.canvasImageHeight,this.canvas.width=this.canvasImageWidth;var c=this.originalImageHeight/this.canvasImageHeight,d=this.originalImageWidth/this.canvasImageWidth;
// Calculate the zoom ratio unless we're in the middle of an animation
// or we're forced to (when resetting the straighten slider)
if(
// Figure out the size
(d>1||c>1)&&(d>c?(this.imageWidth=this.canvasImageWidth,this.imageHeight=this.imageWidth*this.aspectRatio):(this.imageHeight=this.canvasImageHeight,this.imageWidth=this.imageHeight/this.aspectRatio)),
// Clear canvas
this.canvasContext.clearRect(0,0,this.canvasImageWidth,this.canvasImageHeight),!this.animationInProgress||b)
// For non-straightened images we know the zoom is going to be 1
if(this.rotation%90==0)this.zoomRatio=1;else{var e=this.calculateLargestProportionalRectangle(this.rotation,this.imageWidth,this.imageHeight);this.zoomRatio=Math.max(this.imageWidth/e.w,this.imageHeight/e.h)}
// Remember the current context
this.canvasContext.save(),
// Move (0,0) to center of canvas and rotate around it
this.canvasContext.translate(Math.round(this.canvasImageWidth/2),Math.round(this.canvasImageHeight/2)),this.canvasContext.rotate(this.rotation*Math.PI/180);var f=this.imageHeight*this.zoomRatio,g=this.imageWidth*this.zoomRatio;
// Draw the rotated image
this.canvasContext.drawImage(this.$img[0],0,0,this.originalImageWidth,this.originalImageHeight,-(g/2),-(f/2),g,f),this.canvasContext.restore(),a&&this.drawFrame(),this.drawGridLines&&this.drawGrid(),this.clipImage()},addListeners:function(){this.$container.find("a.rotate.clockwise").on("click",a.proxy(function(){this.animationInProgress||(this.addOperation({imageRotation:90}),this.rotate(90))},this)),this.$container.find("a.rotate.counter-clockwise").on("click",a.proxy(function(){this.animationInProgress||(this.addOperation({imageRotation:-90}),this.rotate(-90))},this));var b=this.$container.find(".straighten")[0];b.oninput=a.proxy(this,"straightenImage"),b.onchange=a.proxy(function(a){this.straightenImage(a,!0)},this),b.onmousedown=a.proxy(function(){this.showGridLines(),this.renderImage(!0)},this),b.onmouseup=a.proxy(function(){this.hideGridLines(),this.renderImage(!0)},this),a(".rotate.reset").on("click",a.proxy(function(){this.$container.find(".straighten").val(0),this.setStraightenOffset(0,!1,!0,!0)},this)),
// TODO: remove magic numbers and move them to Garnish Constants
this.addListener(Garnish.$doc,"keydown",a.proxy(function(a){
// CMD/CTRL + Y, CMD/CTRL + SHIFT + Z
if((a.metaKey||a.ctrlKey)&&(89==a.keyCode||90==a.keyCode&&a.shiftKey))return this.redo(),!1},this)),this.addListener(Garnish.$doc,"keydown",a.proxy(function(a){if((a.metaKey||a.ctrlKey)&&!a.shiftKey&&90==a.keyCode)return this.undo(),!1},this))},removeListeners:function(){this.removeListener(Garnish.$doc,"keydown")},addOperation:function(a){this.doneOperations.push(a),
// As soon as we do something, the stack of undone operations is gone.
this.undoneOperations=[]},undo:function(){if(!this.animationInProgress&&this.doneOperations.length>0){var a=this.doneOperations.pop();this.performOperation(a,!0),this.undoneOperations.push(a)}},redo:function(){if(!this.animationInProgress&&this.undoneOperations.length>0){var a=this.undoneOperations.pop();this.performOperation(a,!1),this.doneOperations.push(a)}},
// TODO: This is a horrible name for this function
performOperation:function(a,b){var c=b?-1:1;if("undefined"!=typeof a.imageRotation&&(this.rotation+=c*a.imageRotation,this.frameRotation+=c*a.imageRotation),"undefined"!=typeof a.straightenOffset){var d=c*a.straightenOffset;this.rotation+=d;var e=this.$container.find(".straighten"),f=parseFloat(e.val())+d;
// TODO: this is the part where we refactor the code a bit to be less confusing.
this.previousSavedSliderValue=f,this.previousSliderValue=f,e.val(f)}this.renderImage(!0)},rotate:function(b,c,d){var e=this.rotation+b;if(c)this.rotation=e,this.cleanUpRotationDegrees(),this.renderImage(!0);else{this.animationInProgress=!0;var f=Math.round(b/this.animationFrames*10)/10,g=0,h=function(){g++,this.rotation+=f,d||(this.frameRotation+=f),this.renderImage(!0,d),g<this.animationFrames?setTimeout(a.proxy(h,this),1):(
// Clean up the fractions and whatnot
this.rotation=e,this.cleanUpRotationDegrees(),this.renderImage(!0,d),this.animationInProgress=!1)};h.call(this)}},cleanUpRotationDegrees:function(){this.rotation=this._cleanUpDegrees(this.rotation),this.frameRotation=this._cleanUpDegrees(this.frameRotation)},/**
		 * Ensure a degree value is within [0..360] and has at most one decimal part.
		 */
_cleanUpDegrees:function(a){return a>360?a-=360:a<0&&(a+=360),a=Math.round(10*a)/10},
// Trigger operation - whether we're stopping to drag the slider and should trigger a state save
straightenImage:function(b,c){this.animationInProgress||this.setStraightenOffset(a(b.currentTarget).val(),!0,!1,c)},setStraightenOffset:function(a,b,c,d){var e=a-this.previousSliderValue;this.previousSliderValue=a,d&&(this.addOperation({straightenOffset:a-this.previousSavedSliderValue}),this.previousSavedSliderValue=a),this.rotate(e,b,c)},showGridLines:function(){this.drawGridLines=!0},hideGridLines:function(){this.drawGridLines=!1},/**
		 * Draw the frame around the image.
		 */
drawFrame:function(){
// Remember the current context
this.canvasContext.save(),this.prepareImageFrameRectangle(this.canvasContext),this.canvasContext.lineWidth=1,this.canvasContext.strokeStyle="rgba(0,0,0,0.6)",this.canvasContext.stroke(),
// Restore that context
this.canvasContext.restore()},prepareImageFrameRectangle:function(a){a.translate(Math.round(this.canvasImageWidth/2),Math.round(this.canvasImageHeight/2)),a.rotate(this.frameRotation*Math.PI/180),a.rect(-(this.imageWidth/2)+1,-(this.imageHeight/2)+1,this.imageWidth-2,this.imageHeight-2)},/**
		 * Draw the grid with guides for straightening.
		 */
drawGrid:function(){this.canvasContext.lineWidth=1,this.canvasContext.save(),
// Rotate along the frame
this.canvasContext.translate(Math.round(this.canvasImageWidth/2),Math.round(this.canvasImageHeight/2)),this.canvasContext.rotate(this.frameRotation*Math.PI/180);for(var a=(this.imageWidth-2)/8,b=(this.imageHeight-2)/8,c=0;c<9;c++){switch(c){case 0:case 8:case 4:this.canvasContext.strokeStyle="rgba(0,0,0,0.6)";break;case 2:case 6:this.canvasContext.strokeStyle="rgba(0,0,0,0.3)";break;default:this.canvasContext.strokeStyle="rgba(0,0,0,0.15)"}this.canvasContext.beginPath(),this.canvasContext.moveTo(-(this.imageWidth/2)+a*c+1,-(this.imageHeight/2)),this.canvasContext.lineTo(-(this.imageWidth/2)+a*c+1,this.imageHeight/2),this.canvasContext.closePath(),this.canvasContext.stroke()}for(c=0;c<9;c++){switch(c){case 0:case 8:case 4:this.canvasContext.strokeStyle="rgba(0,0,0,0.6)";break;case 2:case 6:this.canvasContext.strokeStyle="rgba(0,0,0,0.3)";break;default:this.canvasContext.strokeStyle="rgba(0,0,0,0.15)"}this.canvasContext.beginPath(),this.canvasContext.moveTo(-(this.imageWidth/2),-(this.imageHeight/2)+b*c+1),this.canvasContext.lineTo(this.imageWidth/2,-(this.imageHeight/2)+b*c+1),this.canvasContext.closePath(),this.canvasContext.stroke()}this.canvasContext.restore()},/**
		 * Add a new clipping canvas on top of the existing canvas.
		 */
clipImage:function(){var a=Garnish.$doc[0].createElement("canvas");a.width=this.canvas.width,a.height=this.canvas.height;var b=a.getContext("2d");b.fillStyle="white",b.fillRect(0,0,a.width,a.height),b.globalCompositeOperation="xor",this.prepareImageFrameRectangle(b),b.fill(),this.canvasContext.drawImage(a,0,0)},/**
		 * Calculate the largest possible rectangle within a rotated rectangle.
		 * Adapted from http://stackoverflow.com/a/18402507/2040791
		 */
calculateLargestProportionalRectangle:function(a,b,c){var d,e;b<=c?(d=b,e=c):(d=c,e=b),
// Angle normalization in range [-PI..PI)
a>180&&(a=180-a),a<0&&(a+=180);var f=a*(Math.PI/180);f>Math.PI/2&&(f=Math.PI-f);var g,h,i=d/(e*Math.sin(f)+d*Math.cos(f));return b<=c?(g=d*i,h=e*i):(g=e*i,h=d*i),{w:g,h:h}}},{defaults:{resizable:!1,shadeClass:"assetEditor"}}),/**
 * Asset index class
 */
Craft.AssetIndex=Craft.BaseElementIndex.extend({$includeSubfoldersContainer:null,$includeSubfoldersCheckbox:null,showingIncludeSubfoldersCheckbox:!1,$uploadButton:null,$uploadInput:null,$progressBar:null,$folders:null,uploader:null,promptHandler:null,progressBar:null,_uploadTotalFiles:0,_uploadFileProgress:{},_uploadedFileIds:[],_currentUploaderSettings:{},_fileDrag:null,_folderDrag:null,_expandDropTargetFolderTimeout:null,_tempExpandedFolders:[],_fileConflictTemplate:{message:"File “{file}” already exists at target location.",choices:[{value:"keepBoth",title:Craft.t("app","Keep both")},{value:"replace",title:Craft.t("app","Replace it")}]},_folderConflictTemplate:{message:"Folder “{folder}” already exists at target location",choices:[{value:"replace",title:Craft.t("app","Replace the folder (all existing files will be deleted)")},{value:"merge",title:Craft.t("app","Merge the folder (any conflicting files will be replaced)")}]},init:function(a,b,c){this.base(a,b,c),"index"==this.settings.context&&this._initIndexPageMode()},initSource:function(a){this.base(a),this._createFolderContextMenu(a),"index"==this.settings.context&&(this._folderDrag&&this._getSourceLevel(a)>1&&this._folderDrag.addItems(a.parent()),this._fileDrag&&this._fileDrag.updateDropTargets())},deinitSource:function(a){this.base(a);
// Does this source have a context menu?
var b=a.data("contextmenu");b&&b.destroy(),"index"==this.settings.context&&(this._folderDrag&&this._getSourceLevel(a)>1&&this._folderDrag.removeItems(a.parent()),this._fileDrag&&this._fileDrag.updateDropTargets())},_getSourceLevel:function(a){return a.parentsUntil("nav","ul").length},/**
	 * Initialize the index page-specific features
	 */
_initIndexPageMode:function(){
// Make the elements selectable
this.settings.selectable=!0,this.settings.multiSelect=!0;var b=a.proxy(this,"_onDragStart"),c=a.proxy(this,"_onDropTargetChange");
// File dragging
// ---------------------------------------------------------------------
this._fileDrag=new Garnish.DragDrop({activeDropTargetClass:"sel",helperOpacity:.75,filter:a.proxy(function(){return this.view.getSelectedElements()},this),helper:a.proxy(function(a){return this._getFileDragHelper(a)},this),dropTargets:a.proxy(function(){for(var b=[],c=0;c<this.$sources.length;c++)b.push(a(this.$sources[c]));return b},this),onDragStart:b,onDropTargetChange:c,onDragStop:a.proxy(this,"_onFileDragStop")}),
// Folder dragging
// ---------------------------------------------------------------------
this._folderDrag=new Garnish.DragDrop({activeDropTargetClass:"sel",helperOpacity:.75,filter:a.proxy(function(){for(var b=this.sourceSelect.getSelectedItems(),c=[],d=0;d<b.length;d++){var e=a(b[d]).parent();e.hasClass("sel")&&this._getSourceLevel(e)>1&&c.push(e[0])}return a(c)},this),helper:a.proxy(function(b){var c=a('<div class="sidebar" style="padding-top: 0; padding-bottom: 0;"/>'),d=a("<nav/>").appendTo(c),e=a("<ul/>").appendTo(d);
// Match the style
return b.appendTo(e).removeClass("expanded"),b.children("a").addClass("sel"),b.css({"padding-top":this._folderDrag.$draggee.css("padding-top"),"padding-right":this._folderDrag.$draggee.css("padding-right"),"padding-bottom":this._folderDrag.$draggee.css("padding-bottom"),"padding-left":this._folderDrag.$draggee.css("padding-left")}),c},this),dropTargets:a.proxy(function(){var b=[],c=[];this._folderDrag.$draggee.find("a[data-key]").each(function(){c.push(a(this).data("key"))});for(var d=0;d<this.$sources.length;d++){var e=a(this.$sources[d]);Craft.inArray(e.data("key"),c)||b.push(e)}return b},this),onDragStart:b,onDropTargetChange:c,onDragStop:a.proxy(this,"_onFolderDragStop")})},/**
	 * On file drag stop
	 */
_onFileDragStop:function(){if(this._fileDrag.$activeDropTarget&&this._fileDrag.$activeDropTarget[0]!=this.$source[0]){
// For each file, prepare array data.
for(var b=this.$source,c=this._getFolderIdFromSourceKey(this._fileDrag.$activeDropTarget.data("key")),d=[],e=0;e<this._fileDrag.$draggee.length;e++){var f=Craft.getElementInfo(this._fileDrag.$draggee[e]).id;d.push(f)}
// Are any files actually getting moved?
if(d.length){this.setIndexBusy(),this._positionProgressBar(),this.progressBar.resetProgressBar(),this.progressBar.setItemCount(d.length),this.progressBar.showProgressBar();
// For each file to move a separate request
var g=[];for(e=0;e<d.length;e++)g.push({fileId:d[e],folderId:c});
// Define the callback for when all file moves are complete
var h=a.proxy(function(e){this.promptHandler.resetPrompts();
// Loop trough all the responses
for(var f=0;f<e.length;f++){var i=e[f];
// Push prompt into prompt array
if(i.prompt){var j={message:this._fileConflictTemplate.message,choices:this._fileConflictTemplate.choices};j.message=Craft.t("app",j.message,{file:i.filename}),i.prompt=j,this.promptHandler.addPrompt(i)}i.error&&alert(i.error)}this.setIndexAvailable(),this.progressBar.hideProgressBar();var k=!1,l=function(){
// Select original source
this.sourceSelect.selectItem(b),
// Make sure we use the correct offset when fetching the next page
this._totalVisible-=this._fileDrag.$draggee.length;
// And remove the elements that have been moved away
for(var e=0;e<d.length;e++)a("[data-id="+d[e]+"]").remove();this.view.deselectAllElements(),this._collapseExtraExpandedFolders(c),k&&this.updateElements()};if(this.promptHandler.getPromptCount()){
// Define callback for completing all prompts
var m=a.proxy(function(a){
// Loop trough all returned data and prepare a new request array
for(var b=[],c=0;c<a.length;c++)if("cancel"!=a[c].choice)
// Find the matching request parameters for this file and modify them slightly
for(var d=0;d<g.length;d++)g[d].fileId==a[c].fileId&&(g[d].userResponse=a[c].choice,b.push(g[d]));else k=!0;
// Nothing to do, carry on
0==b.length?l.apply(this):(
// Start working
this.setIndexBusy(),this.progressBar.resetProgressBar(),this.progressBar.setItemCount(this.promptHandler.getPromptCount()),this.progressBar.showProgressBar(),
// Move conflicting files again with resolutions now
this._moveFile(b,0,h))},this);this._fileDrag.fadeOutHelpers(),this.promptHandler.showBatchPrompts(m)}else l.apply(this),this._fileDrag.fadeOutHelpers()},this);
// Skip returning dragees
// Initiate the file move with the built array, index of 0 and callback to use when done
return void this._moveFile(g,0,h)}}else
// Add the .sel class back on the selected source
this.$source.addClass("sel"),this._collapseExtraExpandedFolders();this._fileDrag.returnHelpersToDraggees()},/**
	 * On folder drag stop
	 */
_onFolderDragStop:function(){
// Only move if we have a valid target and we're not trying to move into our direct parent
if(this._folderDrag.$activeDropTarget&&0==this._folderDrag.$activeDropTarget.siblings("ul").children("li").filter(this._folderDrag.$draggee).length){var b=this._getFolderIdFromSourceKey(this._folderDrag.$activeDropTarget.data("key"));this._collapseExtraExpandedFolders(b);for(var c=[],d=0;d<this._folderDrag.$draggee.length;d++){var e=this._folderDrag.$draggee.eq(d).children("a"),f=this._getFolderIdFromSourceKey(e.data("key")),g=this._getSourceByFolderId(f);
// Make sure it's not already in the target folder
this._getFolderIdFromSourceKey(this._getParentSource(g).data("key"))!=b&&c.push(f)}if(c.length){c.sort(),c.reverse(),this.setIndexBusy(),this._positionProgressBar(),this.progressBar.resetProgressBar(),this.progressBar.setItemCount(c.length),this.progressBar.showProgressBar();for(var h=[],i=[],d=0;d<c.length;d++)i.push({folderId:c[d],parentId:b});
// Increment, so to avoid displaying folder files that are being moved
this.requestId++;/*
				 Here's the rundown:
				 1) Send all the folders being moved
				 2) Get results:
				   a) For all conflicting, receive prompts and resolve them to get:
				   b) For all valid move operations: by now server has created the needed folders
					  in target destination. Server returns an array of file move operations
				   c) server also returns a list of all the folder id changes
				   d) and the data-id of node to be removed, in case of conflict
				   e) and a list of folders to delete after the move
				 3) From data in 2) build a large file move operation array
				 4) Create a request loop based on this, so we can display progress bar
				 5) when done, delete all the folders and perform other maintenance
				 6) Champagne
				 */
// This will hold the final list of files to move
var j=[],k=[],l={},m=[],n=a.proxy(function(d){this.promptHandler.resetPrompts();
// Loop trough all the responses
for(var e=0;e<d.length;e++){var f=d[e];
// If succesful and have data, then update
if(f.success&&f.transferList&&f.changedIds){for(var g=0;g<f.transferList.length;g++)j.push(f.transferList[g]);k=c;for(var h in f.changedIds)f.changedIds.hasOwnProperty(h)&&(l[h]=f.changedIds[h]);m.push(f.removeFromTree)}
// Push prompt into prompt array
if(f.prompt){var p={message:this._folderConflictTemplate.message,choices:this._folderConflictTemplate.choices};p.message=Craft.t("app",p.message,{folder:f.foldername}),f.prompt=p,this.promptHandler.addPrompt(f)}f.error&&alert(f.error)}if(this.promptHandler.getPromptCount()){
// Define callback for completing all prompts
var q=a.proxy(function(b){this.promptHandler.resetPrompts();
// Loop trough all returned data and prepare a new request array
for(var c=[],d=0;d<b.length;d++)"cancel"!=b[d].choice&&(i[0].userResponse=b[d].choice,c.push(i[0]));
// Start working on them lists, baby
0==c.length?a.proxy(this,"_performActualFolderMove",j,k,l,m)():(
// Start working
this.setIndexBusy(),this.progressBar.resetProgressBar(),this.progressBar.setItemCount(this.promptHandler.getPromptCount()),this.progressBar.showProgressBar(),
// Move conflicting files again with resolutions now
o(c,0,n))},this);this.promptHandler.showBatchPrompts(q),this.setIndexAvailable(),this.progressBar.hideProgressBar()}else a.proxy(this,"_performActualFolderMove",j,k,l,m,b)()},this),o=a.proxy(function(b,c,d){0==c&&(h=[]),Craft.postActionRequest("assets/move-folder",b[c],a.proxy(function(a,e){c++,this.progressBar.incrementProcessedItemCount(1),this.progressBar.updateProgressBar(),"success"==e&&h.push(a),c>=b.length?d(h):o(b,c,d)},this))},this);
// Skip returning dragees until we get the Ajax response
// Initiate the folder move with the built array, index of 0 and callback to use when done
return void o(i,0,n)}}else
// Add the .sel class back on the selected source
this.$source.addClass("sel"),this._collapseExtraExpandedFolders();this._folderDrag.returnHelpersToDraggees()},/**
	 * Really move the folder. Like really. For real.
	 */
_performActualFolderMove:function(b,c,d,e,f){this.setIndexBusy(),this.progressBar.resetProgressBar(),this.progressBar.setItemCount(1),this.progressBar.showProgressBar();var g=a.proxy(function(b,c,d){
//Move the folders around in the tree
var e=a(),g=a(),h=0;
// Change the folder ids
for(var i in c)c.hasOwnProperty(i)&&(g=this._getSourceByFolderId(i),
// Change the id and select the containing element as the folder element.
g=g.attr("data-key","folder:"+c[i]).data("key","folder:"+c[i]).parent(),(0==e.length||e.parents().filter(g).length>0)&&(e=g,h=c[i]));if(0==e.length)return this.setIndexAvailable(),this.progressBar.hideProgressBar(),void this._folderDrag.returnHelpersToDraggees();var j=e.children("a"),k=e.siblings("ul, .toggle"),l=this._getParentSource(j),m=this._getSourceByFolderId(f);if("undefined"!=typeof d)for(var n=0;n<d.length;n++)m.parent().find('[data-key="folder:'+d[n]+'"]').parent().remove();this._prepareParentForChildren(m),this._appendSubfolder(m,e),j.after(k),this._cleanUpTree(l),this._cleanUpTree(m),this.$sidebar.find("ul>ul, ul>.toggle").remove();
// Delete the old folders
for(var n=0;n<b.length;n++)Craft.postActionRequest("assets/delete-folder",{folderId:b[n]});this.setIndexAvailable(),this.progressBar.hideProgressBar(),this._folderDrag.returnHelpersToDraggees(),this._selectSourceByFolderId(h)},this);b.length>0?this._moveFile(b,0,a.proxy(function(){g(c,d,e)},this)):g(c,d,e)},/**
	 * Get parent source for a source.
	 *
	 * @param $source
	 * @returns {*}
	 * @private
	 */
_getParentSource:function(a){if(this._getSourceLevel(a)>1)return a.parent().parent().siblings("a")},/**
	 * Move a file using data from a parameter array.
	 *
	 * @param parameterArray
	 * @param parameterIndex
	 * @param callback
	 * @private
	 */
_moveFile:function(b,c,d){0==c&&(this.responseArray=[]),Craft.postActionRequest("assets/move-asset",b[c],a.proxy(function(a,e){this.progressBar.incrementProcessedItemCount(1),this.progressBar.updateProgressBar(),"success"==e&&(this.responseArray.push(a),
// If assets were just merged we should get the referece tags updated right away
Craft.cp.runPendingTasks()),c++,c>=b.length?d(this.responseArray):this._moveFile(b,c,d)},this))},_selectSourceByFolderId:function(b){for(var c=this._getSourceByFolderId(b),d=c.parent().parents("li"),e=0;e<d.length;e++){var f=a(d[e]);f.hasClass("expanded")||f.children(".toggle").click()}this.sourceSelect.selectItem(c),this.$source=c,this.sourceKey=c.data("key"),this.setInstanceState("selectedSource",this.sourceKey),this.updateElements()},/**
	 * Initialize the uploader.
	 *
	 * @private
	 */
afterInit:function(){this.$uploadButton||(this.$uploadButton=a('<div class="btn submit" data-icon="upload" style="position: relative; overflow: hidden;" role="button">'+Craft.t("app","Upload files")+"</div>"),this.addButton(this.$uploadButton),this.$uploadInput=a('<input type="file" multiple="multiple" name="assets-upload" />').hide().insertBefore(this.$uploadButton)),this.promptHandler=new Craft.PromptHandler,this.progressBar=new Craft.ProgressBar(this.$main,!0);var b={url:Craft.getActionUrl("assets/save-asset"),fileInput:this.$uploadInput,dropZone:this.$main};b.events={fileuploadstart:a.proxy(this,"_onUploadStart"),fileuploadprogressall:a.proxy(this,"_onUploadProgress"),fileuploaddone:a.proxy(this,"_onUploadComplete")},"undefined"!=typeof this.settings.criteria.kind&&(b.allowedKinds=this.settings.criteria.kind),this._currentUploaderSettings=b,this.uploader=new Craft.Uploader(this.$uploadButton,b),this.$uploadButton.on("click",a.proxy(function(){this.$uploadButton.hasClass("disabled")||this.isIndexBusy||this.$uploadButton.parent().find("input[name=assets-upload]").click()},this)),this.base()},onSelectSource:function(){this.uploader.setParams({folderId:this._getFolderIdFromSourceKey(this.sourceKey)}),this.$source.attr("data-upload")?this.$uploadButton.removeClass("disabled"):this.$uploadButton.addClass("disabled"),this.base()},_getFolderIdFromSourceKey:function(a){return a.split(":")[1]},startSearching:function(){
// Does this source have subfolders?
if(this.$source.siblings("ul").length){if(null===this.$includeSubfoldersContainer){var b="includeSubfolders-"+Math.floor(1e9*Math.random());this.$includeSubfoldersContainer=a('<div style="margin-bottom: -23px; opacity: 0;"/>').insertAfter(this.$search);var c=a('<div style="padding-top: 5px;"/>').appendTo(this.$includeSubfoldersContainer);this.$includeSubfoldersCheckbox=a('<input type="checkbox" id="'+b+'" class="checkbox"/>').appendTo(c),a('<label class="light smalltext" for="'+b+'"/>').text(" "+Craft.t("app","Search in subfolders")).appendTo(c),this.addListener(this.$includeSubfoldersCheckbox,"change",function(){this.setSelecetedSourceState("includeSubfolders",this.$includeSubfoldersCheckbox.prop("checked")),this.updateElements()})}else this.$includeSubfoldersContainer.velocity("stop");var d=this.getSelectedSourceState("includeSubfolders",!1);this.$includeSubfoldersCheckbox.prop("checked",d),this.$includeSubfoldersContainer.velocity({marginBottom:0,opacity:1},"fast"),this.showingIncludeSubfoldersCheckbox=!0}this.base()},stopSearching:function(){this.showingIncludeSubfoldersCheckbox&&(this.$includeSubfoldersContainer.velocity("stop"),this.$includeSubfoldersContainer.velocity({marginBottom:-23,opacity:0},"fast"),this.showingIncludeSubfoldersCheckbox=!1),this.base()},getViewParams:function(){var a=this.base();return this.showingIncludeSubfoldersCheckbox&&this.$includeSubfoldersCheckbox.prop("checked")&&(a.criteria.includeSubfolders=!0),a},/**
	 * React on upload submit.
	 *
	 * @param {object} event
	 * @private
     */
_onUploadStart:function(a){this.setIndexBusy(),
// Initial values
this._positionProgressBar(),this.progressBar.resetProgressBar(),this.progressBar.showProgressBar(),this.promptHandler.resetPrompts()},/**
	 * Update uploaded byte count.
	 */
_onUploadProgress:function(a,b){var c=parseInt(b.loaded/b.total*100,10);this.progressBar.setProgressPercentage(c)},/**
	 * On Upload Complete.
	 */
_onUploadComplete:function(b,c){var d=c.result,e=c.files[0].name,f=!0;if(d.success||d.prompt){
// If there is a prompt, add it to the queue
if(
// Add the uploaded file to the selected ones, if appropriate
this._uploadedFileIds.push(d.fileId),d.prompt){var g={message:this._fileConflictTemplate.message,choices:this._fileConflictTemplate.choices};g.message=Craft.t("app",g.message,{file:d.filename}),d.prompt=g,this.promptHandler.addPrompt(d)}}else d.error?alert(Craft.t("app","Upload failed. The error message was: “{error}”",{error:d.error})):alert(Craft.t("app","Upload failed for {filename}.",{filename:e})),f=!1;
// For the last file, display prompts, if any. If not - just update the element view.
this.uploader.isLastUpload()&&(this.setIndexAvailable(),this.progressBar.hideProgressBar(),this.promptHandler.getPromptCount()?this.promptHandler.showBatchPrompts(a.proxy(this,"_uploadFollowup")):f&&this.updateElements())},/**
	 * Follow up to an upload that triggered at least one conflict resolution prompt.
	 *
	 * @param returnData
	 * @private
	 */
_uploadFollowup:function(b){this.setIndexBusy(),this.progressBar.resetProgressBar(),this.promptHandler.resetPrompts();var c=a.proxy(function(){this.setIndexAvailable(),this.progressBar.hideProgressBar(),this.updateElements()},this);this.progressBar.setItemCount(b.length);var d=a.proxy(function(b,c,e){var f={assetId:b[c].assetId,filename:b[c].filename,userResponse:b[c].choice};Craft.postActionRequest("assets/save-asset",f,a.proxy(function(a,f){"success"==f&&a.fileId&&this._uploadedFileIds.push(a.fileId),c++,this.progressBar.incrementProcessedItemCount(1),this.progressBar.updateProgressBar(),c==b.length?e():d(b,c,e)},this))},this);this.progressBar.showProgressBar(),d(b,0,c)},/**
	 * Perform actions after updating elements
	 * @private
	 */
onUpdateElements:function(){this._onUpdateElements(!1,this.view.getAllElements()),this.view.on("appendElements",a.proxy(function(a){this._onUpdateElements(!0,a.newElements)},this)),this.base()},_onUpdateElements:function(a,b){
// See if we have freshly uploaded files to add to selection
if("index"==this.settings.context&&(a||this._fileDrag.removeAllItems(),this._fileDrag.addItems(b)),this._uploadedFileIds.length){if(this.view.settings.selectable)for(var c=0;c<this._uploadedFileIds.length;c++)this.view.selectElementById(this._uploadedFileIds[c]);
// Reset the list.
this._uploadedFileIds=[]}this.base(a,b)},/**
	 * On Drag Start
	 */
_onDragStart:function(){this._tempExpandedFolders=[]},/**
	 * Get File Drag Helper
	 */
_getFileDragHelper:function(b){var c=this.getSelectedSourceState("mode");switch(c){case"table":var d=a('<div class="elements datatablesorthelper"/>').appendTo(Garnish.$bod),e=a('<div class="tableview"/>').appendTo(d),f=a('<table class="data"/>').appendTo(e),g=a("<tbody/>").appendTo(f);b.appendTo(g),
// Copy the column widths
this._$firstRowCells=this.view.$table.children("tbody").children("tr:first").children();for(var h=b.children(),i=0;i<h.length;i++){
// Hard-set the cell widths
var j=a(h[i]);
// Skip the checkbox cell
if(j.hasClass("checkbox-cell"))j.remove(),d.css("margin-"+Craft.left,19);else{var k=a(this._$firstRowCells[i]),l=k.width();k.width(l),j.width(l)}}return d;case"thumbs":var d=a('<div class="elements thumbviewhelper"/>').appendTo(Garnish.$bod),e=a('<ul class="thumbsview"/>').appendTo(d);return b.appendTo(e),d}return a()},/**
	 * On Drop Target Change
	 */
_onDropTargetChange:function(b){if(clearTimeout(this._expandDropTargetFolderTimeout),b){var c=this._getFolderIdFromSourceKey(b.data("key"));c?(this.dropTargetFolder=this._getSourceByFolderId(c),this._hasSubfolders(this.dropTargetFolder)&&!this._isExpanded(this.dropTargetFolder)&&(this._expandDropTargetFolderTimeout=setTimeout(a.proxy(this,"_expandFolder"),500))):this.dropTargetFolder=null}b&&b[0]!=this.$source[0]?
// Temporarily remove the .sel class on the active source
this.$source.removeClass("sel"):this.$source.addClass("sel")},/**
	 * Collapse Extra Expanded Folders
	 */
_collapseExtraExpandedFolders:function(a){clearTimeout(this._expandDropTargetFolderTimeout);
// If a source ID is passed in, exclude its parents
var b;a&&(b=this._getSourceByFolderId(a).parents("li").children("a"));for(var c=this._tempExpandedFolders.length-1;c>=0;c--){var d=this._tempExpandedFolders[c];
// Check the parent list, if a source id is passed in
a&&0!=b.filter('[data-key="'+d.data("key")+'"]').length||(this._collapseFolder(d),this._tempExpandedFolders.splice(c,1))}},_getSourceByFolderId:function(a){return this.$sources.filter('[data-key="folder:'+a+'"]')},_hasSubfolders:function(a){return a.siblings("ul").find("li").length},_isExpanded:function(a){return a.parent("li").hasClass("expanded")},_expandFolder:function(){
// Collapse any temp-expanded drop targets that aren't parents of this one
this._collapseExtraExpandedFolders(this._getFolderIdFromSourceKey(this.dropTargetFolder.data("key"))),this.dropTargetFolder.siblings(".toggle").click(),
// Keep a record of that
this._tempExpandedFolders.push(this.dropTargetFolder)},_collapseFolder:function(a){a.parent().hasClass("expanded")&&a.siblings(".toggle").click()},_createFolderContextMenu:function(b){var c=[{label:Craft.t("app","New subfolder"),onClick:a.proxy(this,"_createSubfolder",b)}];
// For all folders that are not top folders
"index"==this.settings.context&&this._getSourceLevel(b)>1&&(c.push({label:Craft.t("app","Rename folder"),onClick:a.proxy(this,"_renameFolder",b)}),c.push({label:Craft.t("app","Delete folder"),onClick:a.proxy(this,"_deleteFolder",b)})),new Garnish.ContextMenu(b,c,{menuClass:"menu"})},_createSubfolder:function(b){var c=prompt(Craft.t("app","Enter the name of the folder"));if(c){var d={parentId:this._getFolderIdFromSourceKey(b.data("key")),folderName:c};this.setIndexBusy(),Craft.postActionRequest("assets/create-folder",d,a.proxy(function(c,d){if(this.setIndexAvailable(),"success"==d&&c.success){this._prepareParentForChildren(b);var e=a('<li><a data-key="folder:'+c.folderId+'"'+(Garnish.hasAttr(b,"data-has-thumbs")?" data-has-thumbs":"")+' data-upload="'+b.attr("data-upload")+'">'+c.folderName+"</a></li>"),f=e.children("a:first");this._appendSubfolder(b,e),this.initSource(f)}"success"==d&&c.error&&alert(c.error)},this))}},_deleteFolder:function(b){if(confirm(Craft.t("app","Really delete folder “{folder}”?",{folder:a.trim(b.text())}))){var c={folderId:this._getFolderIdFromSourceKey(b.data("key"))};this.setIndexBusy(),Craft.postActionRequest("assets/delete-folder",c,a.proxy(function(a,c){if(this.setIndexAvailable(),"success"==c&&a.success){var d=this._getParentSource(b);
// Remove folder and any trace from its parent, if needed
this.deinitSource(b),b.parent().remove(),this._cleanUpTree(d)}"success"==c&&a.error&&alert(a.error)},this))}},/**
	 * Rename
	 */
_renameFolder:function(b){var c=a.trim(b.text()),d=prompt(Craft.t("app","Rename folder"),c);if(d&&d!=c){var e={folderId:this._getFolderIdFromSourceKey(b.data("key")),newName:d};this.setIndexBusy(),Craft.postActionRequest("assets/rename-folder",e,a.proxy(function(a,c){this.setIndexAvailable(),"success"==c&&a.success&&b.text(a.newName),"success"==c&&a.error&&alert(a.error)},this),"json")}},/**
	 * Prepare a source folder for children folder.
	 *
	 * @param $parentFolder
	 * @private
	 */
_prepareParentForChildren:function(a){this._hasSubfolders(a)||(a.parent().addClass("expanded").append('<div class="toggle"></div><ul></ul>'),this.initSourceToggle(a))},/**
	 * Appends a subfolder to the parent folder at the correct spot.
	 *
	 * @param $parentFolder
	 * @param $subfolder
	 * @private
	 */
_appendSubfolder:function(b,c){for(var d=b.siblings("ul"),e=d.children("li"),f=a.trim(c.children("a:first").text()),g=!1,h=0;h<e.length;h++){var i=a(e[h]);if(a.trim(i.children("a:first").text())>f){i.before(c),g=!0;break}}g||b.siblings("ul").append(c)},_cleanUpTree:function(a){null!==a&&0==a.siblings("ul").children("li").length&&(this.deinitSourceToggle(a),a.siblings("ul").remove(),a.siblings(".toggle").remove(),a.parent().removeClass("expanded"))},_positionProgressBar:function(){var b=a(),c=0;b="index"==this.settings.context?this.progressBar.$progressBar.closest("#content"):this.progressBar.$progressBar.closest(".main");var d=b.offset().top,e=Garnish.$doc.scrollTop(),f=e-d,g=Garnish.$win.height();c=b.height()>g?g/2-6+f:b.height()/2-6,this.progressBar.$progressBar.css({top:c})}}),
// Register it!
Craft.registerElementIndexClass("craft\\app\\elements\\Asset",Craft.AssetIndex),/**
 * Asset Select input
 */
Craft.AssetSelectInput=Craft.BaseElementSelectInput.extend({requestId:0,hud:null,uploader:null,progressBar:null,originalFilename:"",originalExtension:"",init:function(){arguments.length>0&&"object"==typeof arguments[0]&&(arguments[0].editorSettings={onShowHud:a.proxy(this.resetOriginalFilename,this),onCreateForm:a.proxy(this._renameHelper,this),validators:[a.proxy(this.validateElementForm,this)]}),this.base.apply(this,arguments),this._attachUploader()},/**
	 * Attach the uploader with drag event handler
	 */
_attachUploader:function(){this.progressBar=new Craft.ProgressBar(a('<div class="progress-shade"></div>').appendTo(this.$container));var b={url:Craft.getActionUrl("assets/express-upload"),dropZone:this.$container,formData:{fieldId:this.settings.fieldId,elementId:this.settings.sourceElementId}};
// If CSRF protection isn't enabled, these won't be defined.
"undefined"!=typeof Craft.csrfTokenName&&"undefined"!=typeof Craft.csrfTokenValue&&(
// Add the CSRF token
b.formData[Craft.csrfTokenName]=Craft.csrfTokenValue),"undefined"!=typeof this.settings.criteria.kind&&(b.allowedKinds=this.settings.criteria.kind),b.canAddMoreFiles=a.proxy(this,"canAddMoreFiles"),b.events={},b.events.fileuploadstart=a.proxy(this,"_onUploadStart"),b.events.fileuploadprogressall=a.proxy(this,"_onUploadProgress"),b.events.fileuploaddone=a.proxy(this,"_onUploadComplete"),this.uploader=new Craft.Uploader(this.$container,b)},/**
	 * Add the freshly uploaded file to the input field.
	 */
selectUploadedFile:function(a){
// Check if we're able to add new elements
if(this.canAddMoreElements()){var b=a.$element;
// Make a couple tweaks
b.addClass("removable"),b.prepend('<input type="hidden" name="'+this.settings.name+'[]" value="'+a.id+'"><a class="delete icon" title="'+Craft.t("app","Remove")+'"></a>'),b.appendTo(this.$elementsContainer);var c=-(b.outerWidth()+10);this.$addElementBtn.css("margin-"+Craft.left,c+"px");var d={};d["margin-"+Craft.left]=0,this.$addElementBtn.velocity(d,"fast"),this.addElements(b),delete this.modal}},/**
	 * On upload start.
	 */
_onUploadStart:function(a){this.progressBar.$progressBar.css({top:Math.round(this.$container.outerHeight()/2)-6}),this.$container.addClass("uploading"),this.progressBar.resetProgressBar(),this.progressBar.showProgressBar()},/**
	 * On upload progress.
	 */
_onUploadProgress:function(a,b){var c=parseInt(b.loaded/b.total*100,10);this.progressBar.setProgressPercentage(c)},/**
	 * On a file being uploaded.
	 */
_onUploadComplete:function(b,c){if(c.result.error)alert(c.result.error);else{var d=a(c.result.html);Craft.appendHeadHtml(c.result.headHtml),this.selectUploadedFile(Craft.getElementInfo(d))}
// Last file
this.uploader.isLastUpload()&&(this.progressBar.hideProgressBar(),this.$container.removeClass("uploading"))},/**
	 * We have to take into account files about to be added as well
	 */
canAddMoreFiles:function(a){return!this.settings.limit||this.$elements.length+a<this.settings.limit},/**
	 * Parse the passed filename into the base filename and extension.
	 *
	 * @param filename
	 * @returns {{extension: string, baseFileName: string}}
	 */
_parseFilename:function(a){var b=a.split("."),c="";b.length>1&&(c=b.pop());var d=b.join(".");return{extension:c,baseFileName:d}},/**
	 * A helper function or the filename field.
	 * @private
	 */
_renameHelper:function(b){a(".renameHelper",b).on("focus",a.proxy(function(a){var b=a.currentTarget,c=this._parseFilename(b.value);""==this.originalFilename&&""==this.originalExtension&&(this.originalFilename=c.baseFileName,this.originalExtension=c.extension);var d=0,e=c.baseFileName.length;if("undefined"!=typeof b.selectionStart)b.selectionStart=d,b.selectionEnd=e;else if(document.selection&&document.selection.createRange){
// IE branch
b.select();var f=document.selection.createRange();f.collapse(!0),f.moveEnd("character",e),f.moveStart("character",d),f.select()}},this))},resetOriginalFilename:function(){this.originalFilename="",this.originalExtension=""},validateElementForm:function(){var b=a(".renameHelper",this.elementEditor.hud.$hud.data("elementEditor").$form),c=this._parseFilename(b.val());
// Blank extension
// If filename changed as well, assume removal of extension a mistake
return c.extension==this.originalExtension||(""==c.extension?this.originalFilename!=c.baseFileName?(b.val(c.baseFileName+"."+this.originalExtension),!0):confirm(Craft.t("app","Are you sure you want to remove the extension “.{ext}”?",{ext:this.originalExtension})):confirm(Craft.t("app","Are you sure you want to change the extension from “.{oldExt}” to “.{newExt}”?",{oldExt:this.originalExtension,newExt:c.extension})))}}),/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal=Craft.BaseElementSelectorModal.extend({$selectTransformBtn:null,_selectedTransform:null,init:function(b,c){c=a.extend({},Craft.AssetSelectorModal.defaults,c),this.base(b,c),c.transforms.length&&this.createSelectTransformButton(c.transforms)},createSelectTransformButton:function(b){if(b&&b.length){var c=a('<div class="btngroup"/>').appendTo(this.$primaryButtons);this.$selectBtn.appendTo(c),this.$selectTransformBtn=a('<div class="btn menubtn disabled">'+Craft.t("app","Select transform")+"</div>").appendTo(c);for(var d=a('<div class="menu" data-align="right"></div>').insertAfter(this.$selectTransformBtn),e=a("<ul></ul>").appendTo(d),f=0;f<b.length;f++)a('<li><a data-transform="'+b[f].handle+'">'+b[f].name+"</a></li>").appendTo(e);var g=new Garnish.MenuBtn(this.$selectTransformBtn,{onOptionSelect:a.proxy(this,"onSelectTransform")});g.disable(),this.$selectTransformBtn.data("menuButton",g)}},onSelectionChange:function(b){var c=this.elementIndex.getSelectedElements(),d=!1;if(c.length&&this.settings.transforms.length){d=!0;for(var e=0;e<c.length&&a(".element.hasthumb:first",c[e]).length;e++);}var f=null;this.$selectTransformBtn&&(f=this.$selectTransformBtn.data("menuButton")),d?(f&&f.enable(),this.$selectTransformBtn.removeClass("disabled")):this.$selectTransformBtn&&(f&&f.disable(),this.$selectTransformBtn.addClass("disabled")),this.base()},onSelectTransform:function(b){var c=a(b).data("transform");this.selectImagesWithTransform(c)},selectImagesWithTransform:function(b){
// First we must get any missing transform URLs
"undefined"==typeof Craft.AssetSelectorModal.transformUrls[b]&&(Craft.AssetSelectorModal.transformUrls[b]={});for(var c=this.elementIndex.getSelectedElements(),d=[],e=0;e<c.length;e++){var f=a(c[e]),g=Craft.getElementInfo(f).id;"undefined"==typeof Craft.AssetSelectorModal.transformUrls[b][g]&&d.push(g)}d.length?(this.showFooterSpinner(),this.fetchMissingTransformUrls(d,b,a.proxy(function(){this.hideFooterSpinner(),this.selectImagesWithTransform(b)},this))):(this._selectedTransform=b,this.selectElements(),this._selectedTransform=null)},fetchMissingTransformUrls:function(b,c,d){var e=b.pop(),f={fileId:e,handle:c,returnUrl:!0};Craft.postActionRequest("assets/generate-transform",f,a.proxy(function(a,f){Craft.AssetSelectorModal.transformUrls[c][e]=!1,"success"==f&&a.url&&(Craft.AssetSelectorModal.transformUrls[c][e]=a.url),
// More to load?
b.length?this.fetchMissingTransformUrls(b,c,d):d()},this))},getElementInfo:function(a){var b=this.base(a);if(this._selectedTransform)for(var c=0;c<b.length;c++){var d=b[c].id;"undefined"!=typeof Craft.AssetSelectorModal.transformUrls[this._selectedTransform][d]&&Craft.AssetSelectorModal.transformUrls[this._selectedTransform][d]!==!1&&(b[c].url=Craft.AssetSelectorModal.transformUrls[this._selectedTransform][d])}return b},onSelect:function(a){this.settings.onSelect(a,this._selectedTransform)}},{defaults:{canSelectImageTransforms:!1,transforms:[]},transformUrls:{}}),
// Register it!
Craft.registerElementSelectorModalClass("craft\\app\\elements\\Asset",Craft.AssetSelectorModal),/**
 * AuthManager class
 */
Craft.AuthManager=Garnish.Base.extend({remainingSessionTime:null,checkRemainingSessionTimer:null,showLoginModalTimer:null,decrementLogoutWarningInterval:null,showingLogoutWarningModal:!1,showingLoginModal:!1,logoutWarningModal:null,loginModal:null,$logoutWarningPara:null,$passwordInput:null,$passwordSpinner:null,$loginBtn:null,$loginErrorPara:null,submitLoginIfLoggedOut:!1,/**
	 * Init
	 */
init:function(){this.updateRemainingSessionTime(Craft.remainingSessionTime)},/**
	 * Sets a timer for the next time to check the auth timeout.
	 */
setCheckRemainingSessionTimer:function(b){this.checkRemainingSessionTimer&&clearTimeout(this.checkRemainingSessionTimer),this.checkRemainingSessionTimer=setTimeout(a.proxy(this,"checkRemainingSessionTime"),1e3*b)},/**
	 * Pings the server to see how many seconds are left on the current user session, and handles the response.
	 */
checkRemainingSessionTime:function(b){a.ajax({url:Craft.getActionUrl("users/get-remaining-session-time",b?null:"dontExtendSession=1"),type:"GET",dataType:"json",complete:a.proxy(function(a,b){"success"==b?(this.updateRemainingSessionTime(a.responseJSON.timeout),this.submitLoginIfLoggedOut=!1,"undefined"!=typeof a.responseJSON.csrfTokenValue&&"undefined"!=typeof Craft.csrfTokenValue&&(Craft.csrfTokenValue=a.responseJSON.csrfTokenValue)):this.updateRemainingSessionTime(-1)},this)})},/**
	 * Updates our record of the auth timeout, and handles it.
	 */
updateRemainingSessionTime:function(b){this.remainingSessionTime=parseInt(b),
// Are we within the warning window?
this.remainingSessionTime!=-1&&this.remainingSessionTime<Craft.AuthManager.minSafeSessiotTime?(
// Is there still time to renew the session?
this.remainingSessionTime?(this.showingLogoutWarningModal||
// Show the warning modal
this.showLogoutWarningModal(),
// Will the session expire before the next checkup?
this.remainingSessionTime<Craft.AuthManager.checkInterval&&(this.showLoginModalTimer&&clearTimeout(this.showLoginModalTimer),this.showLoginModalTimer=setTimeout(a.proxy(this,"showLoginModal"),1e3*this.remainingSessionTime))):this.showingLoginModal?this.submitLoginIfLoggedOut&&this.submitLogin():
// Show the login modal
this.showLoginModal(),this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval)):(
// Everything's good!
this.hideLogoutWarningModal(),this.hideLoginModal(),
// Will be be within the minSafeSessiotTime before the next update?
this.remainingSessionTime!=-1&&this.remainingSessionTime<Craft.AuthManager.minSafeSessiotTime+Craft.AuthManager.checkInterval?this.setCheckRemainingSessionTimer(this.remainingSessionTime-Craft.AuthManager.minSafeSessiotTime+1):this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval))},/**
	 * Shows the logout warning modal.
	 */
showLogoutWarningModal:function(){var b;if(this.showingLoginModal?(this.hideLoginModal(!0),b=!0):b=!1,this.showingLogoutWarningModal=!0,!this.logoutWarningModal){var c=a('<form id="logoutwarningmodal" class="modal alert fitted"/>'),d=a('<div class="body"/>').appendTo(c),e=a('<div class="buttons right"/>').appendTo(d),f=a('<div class="btn">'+Craft.t("app","Log out now")+"</div>").appendTo(e),g=a('<input type="submit" class="btn submit" value="'+Craft.t("app","Keep me logged in")+'" />').appendTo(e);this.$logoutWarningPara=a("<p/>").prependTo(d),this.logoutWarningModal=new Garnish.Modal(c,{autoShow:!1,closeOtherModals:!1,hideOnEsc:!1,hideOnShadeClick:!1,shadeClass:"modal-shade dark",onFadeIn:function(){Garnish.isMobileBrowser(!0)||
// Auto-focus the renew button
setTimeout(function(){g.focus()},100)}}),this.addListener(f,"activate","logout"),this.addListener(c,"submit","renewSession")}b?this.logoutWarningModal.quickShow():this.logoutWarningModal.show(),this.updateLogoutWarningMessage(),this.decrementLogoutWarningInterval=setInterval(a.proxy(this,"decrementLogoutWarning"),1e3)},/**
	 * Updates the logout warning message indicating that the session is about to expire.
	 */
updateLogoutWarningMessage:function(){this.$logoutWarningPara.text(Craft.t("app","Your session will expire in {time}.",{time:Craft.secondsToHumanTimeDuration(this.remainingSessionTime)})),this.logoutWarningModal.updateSizeAndPosition()},decrementLogoutWarning:function(){this.remainingSessionTime>0&&(this.remainingSessionTime--,this.updateLogoutWarningMessage()),0==this.remainingSessionTime&&clearInterval(this.decrementLogoutWarningInterval)},/**
	 * Hides the logout warning modal.
	 */
hideLogoutWarningModal:function(a){this.showingLogoutWarningModal=!1,this.logoutWarningModal&&(a?this.logoutWarningModal.quickHide():this.logoutWarningModal.hide(),this.decrementLogoutWarningInterval&&clearInterval(this.decrementLogoutWarningInterval))},/**
	 * Shows the login modal.
	 */
showLoginModal:function(){var b;if(this.showingLogoutWarningModal?(this.hideLogoutWarningModal(!0),b=!0):b=!1,this.showingLoginModal=!0,!this.loginModal){var c=a('<form id="loginmodal" class="modal alert fitted"/>'),d=a('<div class="body"><h2>'+Craft.t("app","Your session has ended.")+"</h2><p>"+Craft.t("app","Enter your password to log back in.")+"</p></div>").appendTo(c),e=a('<div class="inputcontainer">').appendTo(d),f=a('<table class="inputs fullwidth"/>').appendTo(e),g=a("<tr/>").appendTo(f),h=a("<td/>").appendTo(g),i=a('<td class="thin"/>').appendTo(g),j=a('<div class="passwordwrapper"/>').appendTo(h);this.$passwordInput=a('<input type="password" class="text password fullwidth" placeholder="'+Craft.t("app","Password")+'"/>').appendTo(j),this.$passwordSpinner=a('<div class="spinner hidden"/>').appendTo(e),this.$loginBtn=a('<input type="submit" class="btn submit disabled" value="'+Craft.t("app","Login")+'" />').appendTo(i),this.$loginErrorPara=a('<p class="error"/>').appendTo(d),this.loginModal=new Garnish.Modal(c,{autoShow:!1,closeOtherModals:!1,hideOnEsc:!1,hideOnShadeClick:!1,shadeClass:"modal-shade dark",onFadeIn:a.proxy(function(){Garnish.isMobileBrowser(!0)||
// Auto-focus the password input
setTimeout(a.proxy(function(){this.$passwordInput.focus()},this),100)},this),onFadeOut:a.proxy(function(){this.$passwordInput.val("")},this)}),new Craft.PasswordInput(this.$passwordInput,{onToggleInput:a.proxy(function(a){this.$passwordInput=a},this)}),this.addListener(this.$passwordInput,"textchange","validatePassword"),this.addListener(c,"submit","login")}b?this.loginModal.quickShow():this.loginModal.show()},/**
	 * Hides the login modal.
	 */
hideLoginModal:function(a){this.showingLoginModal=!1,this.loginModal&&(a?this.loginModal.quickHide():this.loginModal.hide())},logout:function(){a.get({url:Craft.getActionUrl("users/logout"),dataType:"json",success:a.proxy(function(){Craft.redirectTo("")},this)})},renewSession:function(a){a&&a.preventDefault(),this.hideLogoutWarningModal(),this.checkRemainingSessionTime(!0)},validatePassword:function(){return this.$passwordInput.val().length>=6?(this.$loginBtn.removeClass("disabled"),!0):(this.$loginBtn.addClass("disabled"),!1)},login:function(a){a&&a.preventDefault(),this.validatePassword()&&(this.$passwordSpinner.removeClass("hidden"),this.clearLoginError(),"undefined"!=typeof Craft.csrfTokenValue?(
// Check the auth status one last time before sending this off,
// in case the user has already logged back in from another window/tab
this.submitLoginIfLoggedOut=!0,this.checkRemainingSessionTime()):this.submitLogin())},submitLogin:function(){var b={loginName:Craft.username,password:this.$passwordInput.val()};Craft.postActionRequest("users/login",b,a.proxy(function(a,b){this.$passwordSpinner.addClass("hidden"),"success"==b?a.success?(this.hideLoginModal(),this.checkRemainingSessionTime()):(this.showLoginError(a.error),Garnish.shake(this.loginModal.$container),Garnish.isMobileBrowser(!0)||this.$passwordInput.focus()):this.showLoginError()},this))},showLoginError:function(a){null!==a&&"undefined"!=typeof a||(a=Craft.t("app","An unknown error occurred.")),this.$loginErrorPara.text(a),this.loginModal.updateSizeAndPosition()},clearLoginError:function(){this.showLoginError("")}},{checkInterval:60,minSafeSessiotTime:120}),/**
 * Category index class
 */
Craft.CategoryIndex=Craft.BaseElementIndex.extend({editableGroups:null,$newCategoryBtnGroup:null,$newCategoryBtn:null,afterInit:function(){
// Find which of the visible groups the user has permission to create new categories in
this.editableGroups=[];for(var a=0;a<Craft.editableCategoryGroups.length;a++){var b=Craft.editableCategoryGroups[a];this.getSourceByKey("group:"+b.id)&&this.editableGroups.push(b)}this.base()},getDefaultSourceKey:function(){
// Did they request a specific category group in the URL?
if("index"==this.settings.context&&"undefined"!=typeof defaultGroupHandle)for(var b=0;b<this.$sources.length;b++){var c=a(this.$sources[b]);if(c.data("handle")==defaultGroupHandle)return c.data("key")}return this.base()},onSelectSource:function(){
// Get the handle of the selected source
var b=this.$source.data("handle");
// Update the New Category button
// ---------------------------------------------------------------------
if(this.editableGroups.length){
// Remove the old button, if there is one
this.$newCategoryBtnGroup&&this.$newCategoryBtnGroup.remove();
// Determine if they are viewing a group that they have permission to create categories in
var c;if(b)for(var d=0;d<this.editableGroups.length;d++)if(this.editableGroups[d].handle==b){c=this.editableGroups[d];break}this.$newCategoryBtnGroup=a('<div class="btngroup submit"/>');var e;
// If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
// Otherwise only show a menu button
if(c){var f=this._getGroupTriggerHref(c),g="index"==this.settings.context?Craft.t("app","New category"):Craft.t("app","New {group} category",{group:c.name});this.$newCategoryBtn=a('<a class="btn submit add icon" '+f+">"+g+"</a>").appendTo(this.$newCategoryBtnGroup),"index"!=this.settings.context&&this.addListener(this.$newCategoryBtn,"click",function(a){this._openCreateCategoryModal(a.currentTarget.getAttribute("data-id"))}),this.editableGroups.length>1&&(e=a('<div class="btn submit menubtn"></div>').appendTo(this.$newCategoryBtnGroup))}else this.$newCategoryBtn=e=a('<div class="btn submit add icon menubtn">'+Craft.t("app","New category")+"</div>").appendTo(this.$newCategoryBtnGroup);if(e){for(var h='<div class="menu"><ul>',d=0;d<this.editableGroups.length;d++){var i=this.editableGroups[d];if("index"==this.settings.context||i!=c){var f=this._getGroupTriggerHref(i),g="index"==this.settings.context?i.name:Craft.t("app","New {group} category",{group:i.name});h+="<li><a "+f+'">'+g+"</a></li>"}}h+="</ul></div>";var j=(a(h).appendTo(this.$newCategoryBtnGroup),new Garnish.MenuBtn(e));"index"!=this.settings.context&&j.on("optionSelect",a.proxy(function(a){this._openCreateCategoryModal(a.option.getAttribute("data-id"))},this))}this.addButton(this.$newCategoryBtnGroup)}
// Update the URL if we're on the Categories index
// ---------------------------------------------------------------------
if("index"==this.settings.context&&"undefined"!=typeof history){var k="categories";b&&(k+="/"+b),history.replaceState({},"",Craft.getUrl(k))}this.base()},_getGroupTriggerHref:function(a){return"index"==this.settings.context?'href="'+Craft.getUrl("categories/"+a.handle+"/new")+'"':'data-id="'+a.id+'"'},_openCreateCategoryModal:function(b){if(!this.$newCategoryBtn.hasClass("loading")){for(var c,d=0;d<this.editableGroups.length;d++)if(this.editableGroups[d].id==b){c=this.editableGroups[d];break}if(c){this.$newCategoryBtn.addClass("inactive");var e=this.$newCategoryBtn.text();this.$newCategoryBtn.text(Craft.t("app","New {group} category",{group:c.name})),new Craft.ElementEditor({hudTrigger:this.$newCategoryBtnGroup,elementType:"Category",siteId:this.siteId,attributes:{groupId:b},onBeginLoading:a.proxy(function(){this.$newCategoryBtn.addClass("loading")},this),onEndLoading:a.proxy(function(){this.$newCategoryBtn.removeClass("loading")},this),onHideHud:a.proxy(function(){this.$newCategoryBtn.removeClass("inactive").text(e)},this),onSaveElement:a.proxy(function(a){
// Make sure the right group is selected
var c="group:"+b;this.sourceKey!=c&&this.selectSourceByKey(c),this.selectElementAfterUpdate(a.id),this.updateElements()},this)})}}}}),
// Register it!
Craft.registerElementIndexClass("craft\\app\\elements\\Category",Craft.CategoryIndex),/**
 * Category Select input
 */
Craft.CategorySelectInput=Craft.BaseElementSelectInput.extend({setSettings:function(){this.base.apply(this,arguments),this.settings.sortable=!1},getModalSettings:function(){var a=this.base();return a.hideOnSelect=!1,a},getElements:function(){return this.$elementsContainer.find(".element")},onModalSelect:function(b){
// Disable the modal
this.modal.disable(),this.modal.disableCancelBtn(),this.modal.disableSelectBtn(),this.modal.showFooterSpinner();for(var c=this.getSelectedElementIds(),d=0;d<b.length;d++)c.push(b[d].id);var e={categoryIds:c,siteId:b[0].siteId,id:this.settings.id,name:this.settings.name,limit:this.settings.limit,selectionLabel:this.settings.selectionLabel};Craft.postActionRequest("elements/get-categories-input-html",e,a.proxy(function(c,d){if(this.modal.enable(),this.modal.enableCancelBtn(),this.modal.enableSelectBtn(),this.modal.hideFooterSpinner(),"success"==d){var e=a(c.html),f=e.children(".elements");this.$elementsContainer.replaceWith(f),this.$elementsContainer=f,this.resetElements();for(var g=0;g<b.length;g++){var h=b[g],i=this.getElementById(h.id);i&&this.animateElementIntoPlace(h.$element,i)}this.updateDisabledElementsInModal(),this.modal.hide(),this.onSelectElements()}},this))},removeElement:function(a){
// Find any descendants this category might have
var b=a.add(a.parent().siblings("ul").find(".element"));
// Remove our record of them all at once
this.removeElements(b);
// Animate them away one at a time
for(var c=0;c<b.length;c++)this._animateCategoryAway(b,c)},_animateCategoryAway:function(b,c){var d;
// Is this the last one?
c==b.length-1&&(d=a.proxy(function(){var a=b.first().parent().parent(),c=a.parent();c[0]==this.$elementsContainer[0]||a.siblings().length?a.remove():c.remove()},this));var e=a.proxy(function(){this.animateElementAway(b.eq(c),d)},this);0==c?e():setTimeout(e,100*c)}}),/**
 * CP class
 */
Craft.CP=Garnish.Base.extend({authManager:null,$container:null,$alerts:null,$globalSidebar:null,$globalSidebarTopbar:null,$siteNameLink:null,$siteName:null,$nav:null,$subnav:null,$pageHeader:null,$containerTopbar:null,$overflowNavMenuItem:null,$overflowNavMenuBtn:null,$overflowNavMenu:null,$overflowNavMenuList:null,$overflowSubnavMenuItem:null,$overflowSubnavMenuBtn:null,$overflowSubnavMenu:null,$overflowSubnavMenuList:null,$notificationWrapper:null,$notificationContainer:null,$main:null,$content:null,$collapsibleTables:null,$primaryForm:null,navItems:null,totalNavItems:null,visibleNavItems:null,totalNavWidth:null,showingOverflowNavMenu:!1,showingNavToggle:null,showingSidebarToggle:null,subnavItems:null,totalSubnavItems:null,visibleSubnavItems:null,totalSubnavWidth:null,showingOverflowSubnavMenu:!1,selectedItemLabel:null,fixedNotifications:!1,runningTaskInfo:null,trackTaskProgressTimeout:null,taskProgressIcon:null,$edition:null,upgradeModal:null,checkingForUpdates:!1,forcingRefreshOnUpdatesCheck:!1,checkForUpdatesCallbacks:null,init:function(){
// Is this session going to expire?
0!=Craft.remainingSessionTime&&(this.authManager=new Craft.AuthManager),
// Find all the key elements
this.$container=a("#container"),this.$alerts=a("#alerts"),this.$globalSidebar=a("#global-sidebar"),this.$pageHeader=a("#page-header"),this.$containerTopbar=a("#container").find(".topbar"),this.$globalSidebarTopbar=this.$globalSidebar.children(".topbar"),this.$siteNameLink=this.$globalSidebarTopbar.children("a.site-name"),this.$siteName=this.$siteNameLink.children("h2"),this.$nav=a("#nav"),this.$subnav=a("#subnav"),this.$sidebar=a("#sidebar"),this.$notificationWrapper=a("#notifications-wrapper"),this.$notificationContainer=a("#notifications"),this.$main=a("#main"),this.$content=a("#content"),this.$collapsibleTables=a("table.collapsible"),this.$edition=a("#edition"),
// global sidebar
this.addListener(Garnish.$win,"touchend","updateResponsiveGlobalSidebar"),
// Find all the nav items
this.navItems=[],this.totalNavWidth=Craft.CP.baseNavWidth;var b=this.$nav.children();this.totalNavItems=b.length,this.visibleNavItems=this.totalNavItems;for(var c=0;c<this.totalNavItems;c++){var d=a(b[c]),e=d.width();this.navItems.push(d),this.totalNavWidth+=e}
// Find all the sub nav items
this.subnavItems=[],this.totalSubnavWidth=Craft.CP.baseSubnavWidth;var f=this.$subnav.children();this.totalSubnavItems=f.length,this.visibleSubnavItems=this.totalSubnavItems;for(var c=0;c<this.totalSubnavItems;c++){var d=a(f[c]),e=d.width();this.subnavItems.push(d),this.totalSubnavWidth+=e}
// sidebar
this.addListener(this.$sidebar.find("nav ul"),"resize","updateResponsiveSidebar"),this.$sidebarLinks=a("nav a",this.$sidebar),this.addListener(this.$sidebarLinks,"click","selectSidebarItem"),this.addListener(this.$container,"scroll","updateFixedNotifications"),this.updateFixedNotifications(),Garnish.$doc.ready(a.proxy(function(){
// Set up the window resize listener
this.addListener(Garnish.$win,"resize","onWindowResize"),this.onWindowResize();
// Fade the notification out two seconds after page load
var a=this.$notificationContainer.children(".error"),b=this.$notificationContainer.children(":not(.error)");a.delay(2*Craft.CP.notificationDuration).velocity("fadeOut"),b.delay(Craft.CP.notificationDuration).velocity("fadeOut")},this)),
// Alerts
this.$alerts.length&&this.initAlerts(),
// Does this page have a primary form?
"FORM"==this.$container.prop("nodeName")?this.$primaryForm=this.$container:this.$primaryForm=a("form[data-saveshortcut]:first"),
// Does the primary form support the save shortcut?
this.$primaryForm.length&&Garnish.hasAttr(this.$primaryForm,"data-saveshortcut")&&this.addListener(Garnish.$doc,"keydown",function(a){return Garnish.isCtrlKeyPressed(a)&&a.keyCode==Garnish.S_KEY&&(a.preventDefault(),this.submitPrimaryForm()),!0}),Garnish.$win.on("load",a.proxy(function(){if(
// Look for forms that we should watch for changes on
this.$confirmUnloadForms=a("form[data-confirm-unload]"),this.$confirmUnloadForms.length){Craft.forceConfirmUnload||(this.initialFormValues=[]);for(var b=0;b<this.$confirmUnloadForms.length;b++){var c=a(this.$confirmUnloadForms);Craft.forceConfirmUnload||(this.initialFormValues[b]=c.serialize()),this.addListener(c,"submit",function(){this.removeListener(Garnish.$win,"beforeunload")})}this.addListener(Garnish.$win,"beforeunload",function(b){for(var c=0;c<this.$confirmUnloadForms.length;c++)if(Craft.forceConfirmUnload||this.initialFormValues[c]!=a(this.$confirmUnloadForms[c]).serialize()){var d=Craft.t("app","Any changes will be lost if you leave this page.");return b?b.originalEvent.returnValue=d:window.event.returnValue=d,d}})}},this)),this.$edition.hasClass("hot")&&this.addListener(this.$edition,"click","showUpgradeModal")},submitPrimaryForm:function(){
// Give other stuff on the page a chance to prepare
this.trigger("beforeSaveShortcut"),this.$primaryForm.data("saveshortcut-redirect")&&a('<input type="hidden" name="redirect" value="'+this.$primaryForm.data("saveshortcut-redirect")+'"/>').appendTo(this.$primaryForm),this.$primaryForm.submit()},updateSidebarMenuLabel:function(){Garnish.$win.trigger("resize");var b=a("a.sel:first",this.$sidebar);this.selectedItemLabel=b.html()},/**
	 * Handles stuff that should happen when the window is resized.
	 */
onWindowResize:function(){
// Get the new window width
this.onWindowResize._cpWidth=Math.min(Garnish.$win.width(),Craft.CP.maxWidth),
// Update the responsive global sidebar
this.updateResponsiveGlobalSidebar(),
// Update the responsive nav
this.updateResponsiveNav(),
// Update the responsive sidebar
this.updateResponsiveSidebar(),
// Update any responsive tables
this.updateResponsiveTables()},updateResponsiveGlobalSidebar:function(){var a=window.innerHeight;this.$globalSidebar.height(a)},updateResponsiveNav:function(){this.onWindowResize._cpWidth<=992?this.showingNavToggle||this.showNavToggle():this.showingNavToggle&&this.hideNavToggle()},showNavToggle:function(){this.$navBtn=a('<a class="show-nav" title="'+Craft.t("app","Show nav")+'"></a>').prependTo(this.$containerTopbar),this.addListener(this.$navBtn,"click","toggleNav"),this.showingNavToggle=!0},hideNavToggle:function(){this.$navBtn.remove(),this.showingNavToggle=!1},toggleNav:function(){Garnish.$bod.hasClass("showing-nav")?Garnish.$bod.toggleClass("showing-nav"):Garnish.$bod.toggleClass("showing-nav")},updateResponsiveSidebar:function(){this.$sidebar.length>0&&(this.onWindowResize._cpWidth<769?this.showingSidebarToggle||this.showSidebarToggle():this.showingSidebarToggle&&this.hideSidebarToggle())},showSidebarToggle:function(){var b=a("a.sel:first",this.$sidebar);this.selectedItemLabel=b.html(),this.$sidebarBtn=a('<a class="show-sidebar" title="'+Craft.t("app","Show sidebar")+'">'+this.selectedItemLabel+"</a>").prependTo(this.$content),this.addListener(this.$sidebarBtn,"click","toggleSidebar"),this.showingSidebarToggle=!0},selectSidebarItem:function(b){var c=a(b.currentTarget);this.selectedItemLabel=c.html(),this.$sidebarBtn&&(this.$sidebarBtn.html(this.selectedItemLabel),this.toggleSidebar())},hideSidebarToggle:function(){this.$sidebarBtn&&this.$sidebarBtn.remove(),this.showingSidebarToggle=!1},toggleSidebar:function(){var a=this.$content.filter(".has-sidebar");a.toggleClass("showing-sidebar"),this.updateResponsiveContent()},updateResponsiveContent:function(){var b=this.$content.filter(".has-sidebar");if(b.hasClass("showing-sidebar")){var c=a("nav",this.$sidebar).height();if(b.height()<=c){var d=c+48;b.css("height",d+"px")}}else b.css("min-height",0),b.css("height","auto")},updateResponsiveTables:function(){for(this.updateResponsiveTables._i=0;this.updateResponsiveTables._i<this.$collapsibleTables.length;this.updateResponsiveTables._i++)this.updateResponsiveTables._$table=this.$collapsibleTables.eq(this.updateResponsiveTables._i),this.updateResponsiveTables._containerWidth=this.updateResponsiveTables._$table.parent().width(),this.updateResponsiveTables._check=!1,this.updateResponsiveTables._containerWidth>0&&(
// Is this the first time we've checked this table?
"undefined"==typeof this.updateResponsiveTables._$table.data("lastContainerWidth")?this.updateResponsiveTables._check=!0:(this.updateResponsiveTables._isCollapsed=this.updateResponsiveTables._$table.hasClass("collapsed"),
// Getting wider?
this.updateResponsiveTables._containerWidth>this.updateResponsiveTables._$table.data("lastContainerWidth")?this.updateResponsiveTables._isCollapsed&&(this.updateResponsiveTables._$table.removeClass("collapsed"),this.updateResponsiveTables._check=!0):this.updateResponsiveTables._isCollapsed||(this.updateResponsiveTables._check=!0)),
// Are we checking the table width?
this.updateResponsiveTables._check&&this.updateResponsiveTables._$table.width()>this.updateResponsiveTables._containerWidth&&this.updateResponsiveTables._$table.addClass("collapsed"),
// Remember the container width for next time
this.updateResponsiveTables._$table.data("lastContainerWidth",this.updateResponsiveTables._containerWidth))},/**
	 * Adds the last visible nav item to the overflow menu.
	 */
addLastVisibleNavItemToOverflowMenu:function(){this.navItems[this.visibleNavItems-1].prependTo(this.$overflowNavMenuList),this.visibleNavItems--},/**
	 * Adds the first overflow nav item back to the main nav menu.
	 */
addFirstOverflowNavItemToMainMenu:function(){this.navItems[this.visibleNavItems].insertBefore(this.$overflowNavMenuItem),this.visibleNavItems++},/**
	 * Adds the last visible nav item to the overflow menu.
	 */
addLastVisibleSubnavItemToOverflowMenu:function(){this.subnavItems[this.visibleSubnavItems-1].prependTo(this.$overflowSubnavMenuList),this.visibleSubnavItems--},/**
	 * Adds the first overflow nav item back to the main nav menu.
	 */
addFirstOverflowSubnavItemToMainMenu:function(){this.subnavItems[this.visibleSubnavItems].insertBefore(this.$overflowSubnavMenuItem),this.visibleSubnavItems++},updateFixedNotifications:function(){this.updateFixedNotifications._headerHeight=this.$globalSidebar.height(),this.$container.scrollTop()>this.updateFixedNotifications._headerHeight?this.fixedNotifications||(this.$notificationWrapper.addClass("fixed"),this.fixedNotifications=!0):this.fixedNotifications&&(this.$notificationWrapper.removeClass("fixed"),this.fixedNotifications=!1)},/**
	 * Dispays a notification.
	 *
	 * @param {string} type
	 * @param {string} message
	 */
displayNotification:function(b,c){var d=Craft.CP.notificationDuration;"error"==b&&(d*=2);var e=a('<div class="notification '+b+'">'+c+"</div>").appendTo(this.$notificationContainer),f=-(e.outerWidth()/2)+"px";e.hide().css({opacity:0,"margin-left":f,"margin-right":f}).velocity({opacity:1,"margin-left":"2px","margin-right":"2px"},{display:"inline-block",duration:"fast"}).delay(d).velocity({opacity:0,"margin-left":f,"margin-right":f},{complete:function(){e.remove()}}),this.trigger("displayNotification",{notificationType:b,message:c})},/**
	 * Displays a notice.
	 *
	 * @param {string} message
	 */
displayNotice:function(a){this.displayNotification("notice",a)},/**
	 * Displays an error.
	 *
	 * @param {string} message
	 */
displayError:function(a){a||(a=Craft.t("app","An unknown error occurred.")),this.displayNotification("error",a)},fetchAlerts:function(){var b={path:Craft.path};Craft.queueActionRequest("app/get-cp-alerts",b,a.proxy(this,"displayAlerts"))},displayAlerts:function(b){if(Garnish.isArray(b)&&b.length){this.$alerts=a('<ul id="alerts"/>').insertBefore(this.$containerTopbar);for(var c=0;c<b.length;c++)a("<li>"+b[c]+"</li>").appendTo(this.$alerts);var d=this.$alerts.outerHeight();this.$alerts.css("margin-top",-d).velocity({"margin-top":0},"fast"),this.initAlerts()}},initAlerts:function(){
// Is there a domain mismatch?
var b=this.$alerts.find(".domain-mismatch:first");b.length&&this.addListener(b,"click",a.proxy(function(c){c.preventDefault(),confirm(Craft.t("app","Are you sure you want to transfer your license to this domain?"))&&Craft.queueActionRequest("app/transfer-license-to-current-domain",a.proxy(function(a,c){"success"==c&&(a.success?(b.parent().remove(),this.displayNotice(Craft.t("app","License transferred."))):this.displayError(a.error))},this))},this));for(var c=this.$alerts.find('a[class^="shun:"]'),d=0;d<c.length;d++)this.addListener(c[d],"click",a.proxy(function(b){b.preventDefault();var c=a(b.currentTarget),d={message:c.prop("className").substr(5)};Craft.queueActionRequest("app/shun-cp-alert",d,a.proxy(function(a,b){"success"==b&&(a.success?c.parent().remove():this.displayError(a.error))},this))},this));
// Is there an edition resolution link?
var e=this.$alerts.find(".edition-resolution:first");e.length&&this.addListener(e,"click","showUpgradeModal")},checkForUpdates:function(b,c){
// If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
// then just seta new callback that re-checks for updates when the current one is done.
if(this.checkingForUpdates&&b===!0&&!this.forcingRefreshOnUpdatesCheck){var d=c;c=function(){Craft.cp.checkForUpdates(!0,d)}}if(
// Callback function?
"function"==typeof c&&(Garnish.isArray(this.checkForUpdatesCallbacks)||(this.checkForUpdatesCallbacks=[]),this.checkForUpdatesCallbacks.push(c)),!this.checkingForUpdates){this.checkingForUpdates=!0,this.forcingRefreshOnUpdatesCheck=b===!0;var e={forceRefresh:b===!0};Craft.queueActionRequest("app/check-for-updates",e,a.proxy(function(a){if(this.displayUpdateInfo(a),this.checkingForUpdates=!1,Garnish.isArray(this.checkForUpdatesCallbacks)){var b=this.checkForUpdatesCallbacks;this.checkForUpdatesCallbacks=null;for(var c=0;c<b.length;c++)b[c](a)}this.trigger("checkForUpdates",{updateInfo:a})},this))}},displayUpdateInfo:function(b){if(
// Remove the existing header badge, if any
this.$globalSidebarTopbar.children("a.updates").remove(),b.total){var c;c=1==b.total?Craft.t("app","1 update available"):Craft.t("app","{num} updates available",{num:b.total}),
// Topbar badge
a('<a class="updates'+(b.critical?" critical":"")+'" href="'+Craft.getUrl("updates")+'" title="'+c+'"><span data-icon="newstamp"><span>'+b.total+"</span></span></span>").insertAfter(this.$siteNameLink),
// Footer link
a("#footer-updates").text(c)}},runPendingTasks:function(){Craft.runTasksAutomatically?Craft.queueActionRequest("tasks/run-pending-tasks",a.proxy(function(a,b){"success"==b&&this.trackTaskProgress(0)},this)):this.trackTaskProgress(0)},trackTaskProgress:function(b){
// Ignore if we're already tracking tasks
this.trackTaskProgressTimeout||(this.trackTaskProgressTimeout=setTimeout(a.proxy(function(){Craft.queueActionRequest("tasks/get-running-task-info",a.proxy(function(a,b){"success"==b&&(this.trackTaskProgressTimeout=null,this.setRunningTaskInfo(a.task,!0),a.task&&("running"==a.task.status?
// Check again in one second
this.trackTaskProgress():"pending"==a.task.status&&
// Check again in 30 seconds
this.trackTaskProgress(3e4)))},this))},this),"undefined"!=typeof b?b:Craft.CP.taskTrackerUpdateInterval))},stopTrackingTaskProgress:function(){this.trackTaskProgressTimeout&&(clearTimeout(this.trackTaskProgressTimeout),this.trackTaskProgressTimeout=null)},setRunningTaskInfo:function(a,c){this.runningTaskInfo=a,a?(this.taskProgressIcon||(this.taskProgressIcon=new b),"running"==a.status||"pending"==a.status?(this.taskProgressIcon.hideFailMode(),this.taskProgressIcon.setDescription(a.description),this.taskProgressIcon.setProgress(a.progress,c)):"error"==a.status&&this.taskProgressIcon.showFailMode()):this.taskProgressIcon&&(this.taskProgressIcon.hideFailMode(),this.taskProgressIcon.complete(),delete this.taskProgressIcon)},showUpgradeModal:function(){this.upgradeModal?this.upgradeModal.show():this.upgradeModal=new Craft.UpgradeModal}},{maxWidth:1051,//1024,
navHeight:38,baseNavWidth:30,subnavHeight:38,baseSubnavWidth:30,notificationDuration:2e3,taskTrackerUpdateInterval:1e3,taskTrackerHudUpdateInterval:500}),Craft.cp=new Craft.CP;/**
 * Task progress icon class
 */
var b=Garnish.Base.extend({$li:null,$a:null,$label:null,hud:null,completed:!1,failMode:!1,_canvasSupported:null,_$bgCanvas:null,_$staticCanvas:null,_$hoverCanvas:null,_$failCanvas:null,_staticCtx:null,_hoverCtx:null,_canvasSize:null,_arcPos:null,_arcRadius:null,_lineWidth:null,_arcStartPos:0,_arcEndPos:0,_arcStartStepSize:null,_arcEndStepSize:null,_arcStep:null,_arcStepTimeout:null,_arcAnimateCallback:null,_progressBar:null,init:function(){if(this.$li=a("<li/>").appendTo(Craft.cp.$nav),this.$a=a('<a id="taskicon"/>').appendTo(this.$li),this.$canvasContainer=a('<span class="icon"/>').appendTo(this.$a),this.$label=a('<span class="label"></span>').appendTo(this.$a),this._canvasSupported=!!document.createElement("canvas").getContext,this._canvasSupported){var b=window.devicePixelRatio>1?2:1;this._canvasSize=18*b,this._arcPos=this._canvasSize/2,this._arcRadius=7*b,this._lineWidth=3*b,this._$bgCanvas=this._createCanvas("bg","#61666b"),this._$staticCanvas=this._createCanvas("static","#d7d9db"),this._$hoverCanvas=this._createCanvas("hover","#fff"),this._$failCanvas=this._createCanvas("fail","#da5a47").hide(),this._staticCtx=this._$staticCanvas[0].getContext("2d"),this._hoverCtx=this._$hoverCanvas[0].getContext("2d"),this._drawArc(this._$bgCanvas[0].getContext("2d"),0,1),this._drawArc(this._$failCanvas[0].getContext("2d"),0,1)}else this._progressBar=new Craft.ProgressBar(this.$canvasContainer),this._progressBar.showProgressBar();this.addListener(this.$a,"click","toggleHud")},setDescription:function(a){this.$a.attr("title",a),this.$label.html(a)},setProgress:function(a,b){this._canvasSupported?b?this._animateArc(0,a):this._setArc(0,a):this._progressBar.setProgressPercentage(100*a)},complete:function(){this.completed=!0,this._canvasSupported?this._animateArc(0,1,a.proxy(function(){this._$bgCanvas.velocity("fadeOut"),this._animateArc(1,1,a.proxy(function(){this.$a.remove(),this.destroy()},this))},this)):(this._progressBar.setProgressPercentage(100),this.$a.velocity("fadeOut"))},showFailMode:function(){this.failMode||(this.failMode=!0,this._canvasSupported?(this._$bgCanvas.hide(),this._$staticCanvas.hide(),this._$hoverCanvas.hide(),this._$failCanvas.show()):(this._progressBar.$progressBar.css("border-color","#da5a47"),this._progressBar.$innerProgressBar.css("background-color","#da5a47"),this._progressBar.setProgressPercentage(50)),this.setDescription(Craft.t("app","Failed task")))},hideFailMode:function(){this.failMode&&(this.failMode=!1,this._canvasSupported?(this._$bgCanvas.show(),this._$staticCanvas.show(),this._$hoverCanvas.show(),this._$failCanvas.hide()):(this._progressBar.$progressBar.css("border-color",""),this._progressBar.$innerProgressBar.css("background-color",""),this._progressBar.setProgressPercentage(50)))},toggleHud:function(){this.hud?this.hud.toggle():this.hud=new c},_createCanvas:function(b,c){var d=a('<canvas id="taskicon-'+b+'" width="'+this._canvasSize+'" height="'+this._canvasSize+'"/>').appendTo(this.$canvasContainer),e=d[0].getContext("2d");return e.strokeStyle=c,e.lineWidth=this._lineWidth,e.lineCap="round",d},_setArc:function(a,b){this._arcStartPos=a,this._arcEndPos=b,this._drawArc(this._staticCtx,a,b),this._drawArc(this._hoverCtx,a,b)},_drawArc:function(a,b,c){a.clearRect(0,0,this._canvasSize,this._canvasSize),a.beginPath(),a.arc(this._arcPos,this._arcPos,this._arcRadius,(1.5+2*b)*Math.PI,(1.5+2*c)*Math.PI),a.stroke(),a.closePath()},_animateArc:function(a,b,c){this._arcStepTimeout&&clearTimeout(this._arcStepTimeout),this._arcStep=0,this._arcStartStepSize=(a-this._arcStartPos)/10,this._arcEndStepSize=(b-this._arcEndPos)/10,this._arcAnimateCallback=c,this._takeNextArcStep()},_takeNextArcStep:function(){this._setArc(this._arcStartPos+this._arcStartStepSize,this._arcEndPos+this._arcEndStepSize),this._arcStep++,this._arcStep<10?this._arcStepTimeout=setTimeout(a.proxy(this,"_takeNextArcStep"),50):this._arcAnimateCallback&&this._arcAnimateCallback()}}),c=Garnish.HUD.extend({icon:null,tasksById:null,completedTasks:null,updateTasksTimeout:null,completed:!1,init:function(){this.icon=Craft.cp.taskProgressIcon,this.tasksById={},this.completedTasks=[],this.base(this.icon.$a),this.$main.attr("id","tasks-hud"),
// Use the known task as a starting point
Craft.cp.runningTaskInfo&&"error"!=Craft.cp.runningTaskInfo.status&&this.showTaskInfo([Craft.cp.runningTaskInfo]),this.$main.trigger("resize")},onShow:function(){Craft.cp.stopTrackingTaskProgress(),this.updateTasks(),this.base()},onHide:function(){
// Clear out any completed tasks
if(this.updateTasksTimeout&&clearTimeout(this.updateTasksTimeout),this.completed||Craft.cp.trackTaskProgress(),this.completedTasks.length){for(var a=0;a<this.completedTasks.length;a++)this.completedTasks[a].destroy();this.completedTasks=[]}this.base()},updateTasks:function(){this.completed=!1,Craft.postActionRequest("tasks/get-task-info",a.proxy(function(a,b){"success"==b&&this.showTaskInfo(a.tasks)},this))},showTaskInfo:function(b){
// First remove any tasks that have completed
var d=[];if(b)for(var e=0;e<b.length;e++)d.push(b[e].id);for(var f in this.tasksById)this.tasksById.hasOwnProperty(f)&&(Craft.inArray(f,d)||(this.tasksById[f].complete(),this.completedTasks.push(this.tasksById[f]),delete this.tasksById[f]));
// Now display the tasks that are still around
if(b&&b.length){for(var g=!1,h=!1,e=0;e<b.length;e++){var i=b[e];if(g||"running"!=i.status?h||"error"!=i.status||(h=!0):g=!0,this.tasksById[i.id])this.tasksById[i.id].updateStatus(i);else{this.tasksById[i.id]=new c.Task(this,i);
// Place it before the next already known task
for(var j=e+1;j<b.length;j++)if(this.tasksById[b[j].id]){this.tasksById[i.id].$container.insertBefore(this.tasksById[b[j].id].$container);break}}}g?this.updateTasksTimeout=setTimeout(a.proxy(this,"updateTasks"),Craft.CP.taskTrackerHudUpdateInterval):(this.completed=!0,h&&Craft.cp.setRunningTaskInfo({status:"error"}))}else this.completed=!0,Craft.cp.setRunningTaskInfo(null),this.hide()}});c.Task=Garnish.Base.extend({hud:null,id:null,level:null,description:null,status:null,progress:null,$container:null,$statusContainer:null,$descriptionContainer:null,_progressBar:null,init:function(b,c){this.hud=b,this.id=c.id,this.level=c.level,this.description=c.description,this.$container=a('<div class="task"/>').appendTo(this.hud.$main),this.$statusContainer=a('<div class="task-status"/>').appendTo(this.$container),this.$descriptionContainer=a('<div class="task-description"/>').appendTo(this.$container).text(c.description),this.$container.data("task",this),0!=this.level&&(this.$container.css("padding-"+Craft.left,24+24*this.level),a('<div class="indent" data-icon="'+("ltr"==Craft.orientation?"rarr":"larr")+'"/>').appendTo(this.$descriptionContainer)),this.updateStatus(c)},updateStatus:function(b){if(this.status!=b.status)switch(this.$statusContainer.empty(),this.status=b.status,this.status){case"pending":this.$statusContainer.text(Craft.t("app","Pending"));break;case"running":this._progressBar=new Craft.ProgressBar(this.$statusContainer),this._progressBar.showProgressBar();break;case"error":if(a('<span class="error">'+Craft.t("app","Failed")+"</span>").appendTo(this.$statusContainer),0==this.level){var c=a('<a class="menubtn error" title="'+Craft.t("app","Options")+'"/>').appendTo(this.$statusContainer);a('<div class="menu"><ul><li><a data-action="rerun">'+Craft.t("app","Try again")+'</a></li><li><a data-action="cancel">'+Craft.t("app","Cancel")+"</a></li></ul></div>").appendTo(this.$statusContainer),new Garnish.MenuBtn(c,{onOptionSelect:a.proxy(this,"performErrorAction")})}}"running"==this.status&&(this._progressBar.setProgressPercentage(100*b.progress),0==this.level&&
// Update the task icon
Craft.cp.setRunningTaskInfo(b,!0))},performErrorAction:function(b){for(var c=this.$container.nextAll(),d=0;d<c.length;d++){var e=a(c[d]).data("task");if(!e||0==e.level)break;e.destroy()}
// What option did they choose?
switch(a(b).data("action")){case"rerun":Craft.postActionRequest("tasks/rerun-task",{taskId:this.id},a.proxy(function(a,b){"success"==b&&(this.updateStatus(a.task),this.hud.completed&&this.hud.updateTasks())},this));break;case"cancel":Craft.postActionRequest("tasks/delete-task",{taskId:this.id},a.proxy(function(a,b){"success"==b&&(this.destroy(),this.hud.completed&&this.hud.updateTasks())},this))}},complete:function(){this.$statusContainer.empty(),a('<div data-icon="check"/>').appendTo(this.$statusContainer)},destroy:function(){this.hud.tasksById[this.id]&&delete this.hud.tasksById[this.id],this.$container.remove(),this.base()}}),/**
* Customize Sources modal
*/
Craft.CustomizeSourcesModal=Garnish.Modal.extend({elementIndex:null,$elementIndexSourcesContainer:null,$sidebar:null,$sourcesContainer:null,$sourceSettingsContainer:null,$newHeadingBtn:null,$footer:null,$footerBtnContainer:null,$saveBtn:null,$cancelBtn:null,$saveSpinner:null,$loadingSpinner:null,sourceSort:null,sources:null,selectedSource:null,updateSourcesOnSave:!1,availableTableAttributes:null,init:function(b,c){this.base(),this.setSettings(c,{resizable:!0}),this.elementIndex=b,this.$elementIndexSourcesContainer=this.elementIndex.$sidebar.children("nav").children("ul");var d=a('<form class="modal customize-sources-modal"/>').appendTo(Garnish.$bod);this.$sidebar=a('<div class="cs-sidebar block-types"/>').appendTo(d),this.$sourcesContainer=a('<div class="sources">').appendTo(this.$sidebar),this.$sourceSettingsContainer=a('<div class="source-settings">').appendTo(d),this.$footer=a('<div class="footer"/>').appendTo(d),this.$footerBtnContainer=a('<div class="buttons right"/>').appendTo(this.$footer),this.$cancelBtn=a('<div class="btn" role="button"/>').text(Craft.t("app","Cancel")).appendTo(this.$footerBtnContainer),this.$saveBtn=a('<div class="btn submit disabled" role="button"/>').text(Craft.t("app","Save")).appendTo(this.$footerBtnContainer),this.$saveSpinner=a('<div class="spinner hidden"/>').appendTo(this.$footerBtnContainer),this.$newHeadingBtn=a('<div class="btn submit add icon"/>').text(Craft.t("app","New heading")).appendTo(a('<div class="buttons left secondary-buttons"/>').appendTo(this.$footer)),this.$loadingSpinner=a('<div class="spinner"/>').appendTo(d),this.setContainer(d),this.show();var e={elementType:this.elementIndex.elementType};Craft.postActionRequest("element-index-settings/get-customize-sources-modal-data",e,a.proxy(function(a,b){this.$loadingSpinner.remove(),"success"==b&&(this.$saveBtn.removeClass("disabled"),this.buildModal(a))},this)),this.addListener(this.$newHeadingBtn,"click","handleNewHeadingBtnClick"),this.addListener(this.$cancelBtn,"click","hide"),this.addListener(this.$saveBtn,"click","save"),this.addListener(this.$container,"submit","save")},buildModal:function(b){
// Store the available table attribute options
this.availableTableAttributes=b.availableTableAttributes,
// Create the source item sorter
this.sourceSort=new Garnish.DragSort({handle:".move",axis:"y",onSortChange:a.proxy(function(){this.updateSourcesOnSave=!0},this)}),
// Create the sources
this.sources=[];for(var c=0;c<b.sources.length;c++){var d=this.addSource(b.sources[c]);this.sources.push(d)}this.selectedSource||"undefined"==typeof this.sources[0]||this.sources[0].select()},addSource:function(b){var c,d=a('<div class="customize-sources-item"/>').appendTo(this.$sourcesContainer),e=a('<div class="label"/>').appendTo(d),f=a('<input type="hidden"/>').appendTo(d);a('<a class="move icon" title="'+Craft.t("app","Reorder")+'" role="button"></a>').appendTo(d);
// Is this a heading?
// Select this by default?
return"undefined"!=typeof b.heading?(d.addClass("heading"),f.attr("name","sourceOrder[][heading]"),c=new Craft.CustomizeSourcesModal.Heading(this,d,e,f,b),c.updateItemLabel(b.heading)):(f.attr("name","sourceOrder[][key]").val(b.key),c=new Craft.CustomizeSourcesModal.Source(this,d,e,f,b),c.updateItemLabel(b.label),b.key==this.elementIndex.sourceKey&&c.select()),this.sourceSort.addItems(d),c},handleNewHeadingBtnClick:function(){var a=this.addSource({heading:""});Garnish.scrollContainerToElement(this.$sidebar,a.$item),a.select(),this.updateSourcesOnSave=!0},save:function(b){if(b&&b.preventDefault(),!this.$saveBtn.hasClass("disabled")&&this.$saveSpinner.hasClass("hidden")){this.$saveSpinner.removeClass("hidden");var c=this.$container.serialize()+"&elementType="+this.elementIndex.elementType;Craft.postActionRequest("element-index-settings/save-customize-sources-modal-settings",c,a.proxy(function(a,b){if(this.$saveSpinner.addClass("hidden"),"success"==b&&a.success){
// Have any changes been made to the source list?
if(this.updateSourcesOnSave&&this.$elementIndexSourcesContainer.length){for(var c,d,e=0;e<this.sourceSort.$items.length;e++){var f=this.sourceSort.$items.eq(e),g=f.data("source"),h=g.getIndexSource();h&&(g.isHeading()?d=h:(d&&(this.appendSource(d,c),c=d,d=null),this.appendSource(h,c),c=h))}
// Remove any additional sources (most likely just old headings)
if(c){var i=c.nextAll();this.elementIndex.sourceSelect.removeItems(i),i.remove()}}
// If a source is selected, have the element index select that one by default on the next request
this.selectedSource&&this.selectedSource.sourceData.key&&(this.elementIndex.selectSourceByKey(this.selectedSource.sourceData.key),this.elementIndex.updateElements()),Craft.cp.displayNotice(Craft.t("app","Source settings saved")),this.hide()}else{var j="success"==b&&a.error?a.error:Craft.t("app","An unknown error occurred.");Craft.cp.displayError(j)}},this))}},appendSource:function(a,b){b?a.insertAfter(b):a.prependTo(this.$elementIndexSourcesContainer)},destroy:function(){for(var a=0;a<this.sources.length;a++)this.sources[a].destroy();delete this.sources,this.base()}}),Craft.CustomizeSourcesModal.BaseSource=Garnish.Base.extend({modal:null,$item:null,$itemLabel:null,$itemInput:null,$settingsContainer:null,sourceData:null,init:function(a,b,c,d,e){this.modal=a,this.$item=b,this.$itemLabel=c,this.$itemInput=d,this.sourceData=e,this.$item.data("source",this),this.addListener(this.$item,"click","select")},isHeading:function(){return!1},isSelected:function(){return this.modal.selectedSource==this},select:function(){this.isSelected()||(this.modal.selectedSource&&this.modal.selectedSource.deselect(),this.$item.addClass("sel"),this.modal.selectedSource=this,this.$settingsContainer?this.$settingsContainer.removeClass("hidden"):this.$settingsContainer=a("<div/>").append(this.createSettings()).appendTo(this.modal.$sourceSettingsContainer),this.modal.$sourceSettingsContainer.scrollTop(0))},createSettings:function(){},getIndexSource:function(){},deselect:function(){this.$item.removeClass("sel"),this.modal.selectedSource=null,this.$settingsContainer.addClass("hidden")},updateItemLabel:function(a){this.$itemLabel.text(a)},destroy:function(){this.$item.data("source",null),this.base()}}),Craft.CustomizeSourcesModal.Source=Craft.CustomizeSourcesModal.BaseSource.extend({createSettings:function(){if(this.sourceData.tableAttributes.length){
// Create the title column option
var b=this.sourceData.tableAttributes[0],c=b[0],d=b[1],e=this.createTableColumnOption(c,d,!0,!0),f=a("<div/>"),g=[c];a('<input type="hidden" name="sources['+this.sourceData.key+'][tableAttributes][]" value=""/>').appendTo(f);
// Add the selected columns, in the selected order
for(var h=1;h<this.sourceData.tableAttributes.length;h++){var i=this.sourceData.tableAttributes[h],j=i[0],k=i[1];f.append(this.createTableColumnOption(j,k,!1,!0)),g.push(j)}
// Add the rest
for(var h=0;h<this.modal.availableTableAttributes.length;h++){var i=this.modal.availableTableAttributes[h],j=i[0],k=i[1];Craft.inArray(j,g)||f.append(this.createTableColumnOption(j,k,!1,!1))}return new Garnish.DragSort(f.children(),{handle:".move",axis:"y"}),Craft.ui.createField(a([e[0],f[0]]),{label:Craft.t("app","Table Columns"),instructions:Craft.t("app","Choose which table columns should be visible for this source, and in which order.")})}},createTableColumnOption:function(b,c,d,e){var f=a('<div class="customize-sources-table-column"/>').append('<div class="icon move"/>').append(Craft.ui.createCheckbox({label:c,name:"sources["+this.sourceData.key+"][tableAttributes][]",value:b,checked:e,disabled:d}));return d&&f.children(".move").addClass("disabled"),f},getIndexSource:function(){var a=this.modal.elementIndex.getSourceByKey(this.sourceData.key);if(a)return a.closest("li")}}),Craft.CustomizeSourcesModal.Heading=Craft.CustomizeSourcesModal.BaseSource.extend({$labelField:null,$labelInput:null,$deleteBtn:null,isHeading:function(){return!0},select:function(){this.base(),this.$labelInput.focus()},createSettings:function(){return this.$labelField=Craft.ui.createTextField({label:Craft.t("app","Heading"),instructions:Craft.t("app","This can be left blank if you just want an unlabeled separator."),value:this.sourceData.heading}),this.$labelInput=this.$labelField.find(".text"),this.$deleteBtn=a('<a class="error delete"/>').text(Craft.t("app","Delete heading")),this.addListener(this.$labelInput,"textchange","handleLabelInputChange"),this.addListener(this.$deleteBtn,"click","deleteHeading"),a([this.$labelField[0],a("<hr/>")[0],this.$deleteBtn[0]])},handleLabelInputChange:function(){this.updateItemLabel(this.$labelInput.val()),this.modal.updateSourcesOnSave=!0},updateItemLabel:function(a){this.$itemLabel.html((a?Craft.escapeHtml(a):'<em class="light">'+Craft.t("app","(blank)")+"</em>")+"&nbsp;"),this.$itemInput.val(a)},deleteHeading:function(){this.modal.sourceSort.removeItems(this.$item),this.modal.sources.splice(a.inArray(this,this.modal.sources),1),this.modal.updateSourcesOnSave=!0,this.isSelected()&&(this.deselect(),this.modal.sources.length&&this.modal.sources[0].select()),this.$item.remove(),this.$settingsContainer.remove(),this.destroy()},getIndexSource:function(){var b=this.$labelInput?this.$labelInput.val():this.sourceData.heading;return a('<li class="heading"/>').append(a("<span/>").text(b))}}),/**
 * DataTableSorter
 */
Craft.DataTableSorter=Garnish.DragSort.extend({$table:null,init:function(b,c){this.$table=a(b);var d=this.$table.children("tbody").children(":not(.filler)");c=a.extend({},Craft.DataTableSorter.defaults,c),c.container=this.$table.children("tbody"),c.helper=a.proxy(this,"getHelper"),c.caboose="<tr/>",c.axis=Garnish.Y_AXIS,c.magnetStrength=4,c.helperLagBase=1.5,this.base(d,c)},getHelper:function(b){var c=a('<div class="'+this.settings.helperClass+'"/>').appendTo(Garnish.$bod),d=a("<table/>").appendTo(c),e=a("<tbody/>").appendTo(d);b.appendTo(e),
// Copy the table width and classes
d.width(this.$table.width()),d.prop("className",this.$table.prop("className"));for(var f=this.$table.find("tr:first"),g=f.children(),h=b.children(),i=0;i<h.length;i++)a(h[i]).width(a(g[i]).width());return c}},{defaults:{handle:".move",helperClass:"datatablesorthelper"}}),/**
 * Delete User Modal
 */
Craft.DeleteUserModal=Garnish.Modal.extend({id:null,userId:null,$deleteActionRadios:null,$deleteSpinner:null,userSelect:null,_deleting:!1,init:function(b,c){this.id=Math.floor(1e9*Math.random()),this.userId=b,c=a.extend(Craft.DeleteUserModal.defaults,c);var d=a('<form class="modal fitted deleteusermodal" method="post" accept-charset="UTF-8">'+Craft.getCsrfInput()+'<input type="hidden" name="action" value="users/delete-user"/>'+(Garnish.isArray(this.userId)?"":'<input type="hidden" name="userId" value="'+this.userId+'"/>')+(c.redirect?'<input type="hidden" name="redirect" value="'+c.redirect+'"/>':"")+"</form>").appendTo(Garnish.$bod),e=a('<div class="body"><p>'+Craft.t("app","What do you want to do with their content?")+'</p><div class="options"><label><input type="radio" name="contentAction" value="transfer"/> '+Craft.t("app","Transfer it to:")+'</label><div id="transferselect'+this.id+'" class="elementselect"><div class="elements"></div><div class="btn add icon dashed">'+Craft.t("app","Choose a user")+'</div></div></div><div><label><input type="radio" name="contentAction" value="delete"/> '+Craft.t("app","Delete it")+"</label></div></div>").appendTo(d),f=a('<div class="buttons right"/>').appendTo(e),g=a('<div class="btn">'+Craft.t("app","Cancel")+"</div>").appendTo(f);this.$deleteActionRadios=e.find("input[type=radio]"),this.$deleteSubmitBtn=a('<input type="submit" class="btn submit disabled" value="'+(Garnish.isArray(this.userId)?Craft.t("app","Delete users"):Craft.t("app","Delete user"))+'" />').appendTo(f),this.$deleteSpinner=a('<div class="spinner hidden"/>').appendTo(f);var h;if(Garnish.isArray(this.userId)){h=["and"];for(var i=0;i<this.userId.length;i++)h.push("not "+this.userId[i])}else h="not "+this.userId;this.userSelect=new Craft.BaseElementSelectInput({id:"transferselect"+this.id,name:"transferContentTo",elementType:"User",criteria:{id:h},limit:1,modalSettings:{closeOtherModals:!1},onSelectElements:a.proxy(function(){this.updateSizeAndPosition(),this.$deleteActionRadios.first().prop("checked")?this.validateDeleteInputs():this.$deleteActionRadios.first().click()},this),onRemoveElements:a.proxy(this,"validateDeleteInputs"),selectable:!1,editable:!1}),this.addListener(g,"click","hide"),this.addListener(this.$deleteActionRadios,"change","validateDeleteInputs"),this.addListener(d,"submit","handleSubmit"),this.base(d,c)},validateDeleteInputs:function(){var a=!1;return this.$deleteActionRadios.eq(0).prop("checked")?a=!!this.userSelect.totalSelected:this.$deleteActionRadios.eq(1).prop("checked")&&(a=!0),a?this.$deleteSubmitBtn.removeClass("disabled"):this.$deleteSubmitBtn.addClass("disabled"),a},handleSubmit:function(a){
// Let the onSubmit callback prevent the form from getting submitted
return this._deleting||!this.validateDeleteInputs()?void a.preventDefault():(this.$deleteSubmitBtn.addClass("active"),this.$deleteSpinner.removeClass("hidden"),this.disable(),this.userSelect.disable(),this._deleting=!0,void(this.settings.onSubmit()===!1&&a.preventDefault()))},onFadeIn:function(){
// Auto-focus the first radio
Garnish.isMobileBrowser(!0)||this.$deleteActionRadios.first().focus(),this.base()}},{defaults:{onSubmit:a.noop,redirect:null}}),/**
 * Editable table class
 */
Craft.EditableTable=Garnish.Base.extend({initialized:!1,id:null,baseName:null,columns:null,sorter:null,biggestId:-1,$table:null,$tbody:null,$addRowBtn:null,init:function(b,c,d,e){this.id=b,this.baseName=c,this.columns=d,this.setSettings(e,Craft.EditableTable.defaults),this.$table=a("#"+b),this.$tbody=this.$table.children("tbody"),this.sorter=new Craft.DataTableSorter(this.$table,{helperClass:"editabletablesorthelper",copyDraggeeInputValuesToHelper:!0}),this.isVisible()?this.initialize():this.addListener(Garnish.$win,"resize","initializeIfVisible")},isVisible:function(){return this.$table.height()>0},initialize:function(){if(!this.initialized){this.initialized=!0,this.removeListener(Garnish.$win,"resize");for(var a=this.$tbody.children(),b=0;b<a.length;b++)new Craft.EditableTable.Row(this,a[b]);this.$addRowBtn=this.$table.next(".add"),this.addListener(this.$addRowBtn,"activate","addRow")}},initializeIfVisible:function(){this.isVisible()&&this.initialize()},addRow:function(){var a=this.settings.rowIdPrefix+(this.biggestId+1),b=this.createRow(a,this.columns,this.baseName,{});b.appendTo(this.$tbody),new Craft.EditableTable.Row(this,b),this.sorter.addItems(b),
// Focus the first input in the row
b.find("input,textarea,select").first().focus(),
// onAddRow callback
this.settings.onAddRow(b)},createRow:function(a,b,c,d){return Craft.EditableTable.createRow(a,b,c,d)}},{textualColTypes:["singleline","multiline","number"],defaults:{rowIdPrefix:"",onAddRow:a.noop,onDeleteRow:a.noop},createRow:function(b,c,d,e){var f=a("<tr/>",{"data-id":b});for(var g in c)if(c.hasOwnProperty(g)){var h,i=c[g],j="undefined"!=typeof e[g]?e[g]:"";if("heading"==i.type)h=a("<th/>",{scope:"row",class:i.class,html:j});else{var k=d+"["+b+"]["+g+"]",l=Craft.inArray(i.type,Craft.EditableTable.textualColTypes);switch(h=a("<td/>",{class:i.class,width:i.width}),l&&h.addClass("textual"),i.code&&h.addClass("code"),i.type){case"select":Craft.ui.createSelect({name:k,options:i.options,value:j,class:"small"}).appendTo(h);break;case"checkbox":Craft.ui.createCheckbox({name:k,value:i.value||"1",checked:!!j}).appendTo(h);break;case"lightswitch":Craft.ui.createLightswitch({name:k,value:j}).appendTo(h);break;default:a("<textarea/>",{name:k,rows:1,value:j,placeholder:i.placeholder}).appendTo(h)}}h.appendTo(f)}return a("<td/>",{class:"thin action"}).append(a("<a/>",{class:"move icon",title:Craft.t("app","Reorder")})).appendTo(f),a("<td/>",{class:"thin action"}).append(a("<a/>",{class:"delete icon",title:Craft.t("app","Delete")})).appendTo(f),f}}),/**
 * Editable table row class
 */
Craft.EditableTable.Row=Garnish.Base.extend({table:null,id:null,niceTexts:null,$tr:null,$tds:null,$textareas:null,$deleteBtn:null,init:function(b,c){this.table=b,this.$tr=a(c),this.$tds=this.$tr.children();
// Get the row ID, sans prefix
var d=parseInt(this.$tr.attr("data-id").substr(this.table.settings.rowIdPrefix.length));d>this.table.biggestId&&(this.table.biggestId=d),this.$textareas=a(),this.niceTexts=[];var e={},f=0;for(var g in this.table.columns)if(this.table.columns.hasOwnProperty(g)){var h=this.table.columns[g];if(Craft.inArray(h.type,Craft.EditableTable.textualColTypes)){var i=a("textarea",this.$tds[f]);this.$textareas=this.$textareas.add(i),this.addListener(i,"focus","onTextareaFocus"),this.addListener(i,"mousedown","ignoreNextTextareaFocus"),this.niceTexts.push(new Garnish.NiceText(i,{onHeightChange:a.proxy(this,"onTextareaHeightChange")})),"singleline"!=h.type&&"number"!=h.type||(this.addListener(i,"keypress",{type:h.type},"validateKeypress"),this.addListener(i,"textchange",{type:h.type},"validateValue")),e[g]=i}f++}
// Now that all of the text cells have been nice-ified, let's normalize the heights
this.onTextareaHeightChange();
// Now look for any autopopulate columns
for(var g in this.table.columns)if(this.table.columns.hasOwnProperty(g)){var h=this.table.columns[g];h.autopopulate&&"undefined"!=typeof e[h.autopopulate]&&!e[g].val()&&new Craft.HandleGenerator(e[g],e[h.autopopulate])}var j=this.$tr.children().last().find(".delete");this.addListener(j,"click","deleteRow")},onTextareaFocus:function(b){this.onTextareaHeightChange();var c=a(b.currentTarget);return c.data("ignoreNextFocus")?void c.data("ignoreNextFocus",!1):void setTimeout(function(){var a=c.val();
// Does the browser support setSelectionRange()?
if("undefined"!=typeof c[0].setSelectionRange){
// Select the whole value
var b=2*a.length;c[0].setSelectionRange(0,b)}else
// Refresh the value to get the cursor positioned at the end
c.val(a)},0)},ignoreNextTextareaFocus:function(b){a.data(b.currentTarget,"ignoreNextFocus",!0)},validateKeypress:function(a){var b=a.keyCode?a.keyCode:a.charCode;Garnish.isCtrlKeyPressed(a)||b!=Garnish.RETURN_KEY&&("number"!=a.data.type||Craft.inArray(b,Craft.EditableTable.Row.numericKeyCodes))||a.preventDefault()},validateValue:function(a){var b;if("number"==a.data.type){
// Only grab the number at the beginning of the value (if any)
var c=a.currentTarget.value.match(/^\s*(-?[\d\.]*)/);b=null!==c?c[1]:""}else
// Just strip any newlines
b=a.currentTarget.value.replace(/[\r\n]/g,"");b!==a.currentTarget.value&&(a.currentTarget.value=b)},onTextareaHeightChange:function(){for(var a=-1,b=0;b<this.niceTexts.length;b++)this.niceTexts[b].height>a&&(a=this.niceTexts[b].height);this.$textareas.css("min-height",a);
// If the <td> is still taller, go with that insted
var c=this.$textareas.first().parent().height();c>a&&this.$textareas.css("min-height",c)},deleteRow:function(){this.table.sorter.removeItems(this.$tr),this.$tr.remove(),
// onDeleteRow callback
this.table.settings.onDeleteRow(this.$tr)}},{numericKeyCodes:[9,8,37,38,39,40,45,91,46,190,48,49,50,51,52,53,54,55,56,57]}),/**
 * Element Action Trigger
 */
Craft.ElementActionTrigger=Garnish.Base.extend({maxLevels:null,newChildUrl:null,$trigger:null,$selectedItems:null,triggerEnabled:!0,init:function(b){this.setSettings(b,Craft.ElementActionTrigger.defaults),this.$trigger=a("#"+b.type.replace(/[\[\]\\]+/g,"-")+"-actiontrigger"),
// Do we have a custom handler?
this.settings.activate&&(
// Prevent the element index's click handler
this.$trigger.data("custom-handler",!0),
// Is this a custom trigger?
"FORM"==this.$trigger.prop("nodeName")?this.addListener(this.$trigger,"submit","handleTriggerActivation"):this.addListener(this.$trigger,"click","handleTriggerActivation")),this.updateTrigger(),Craft.elementIndex.on("selectionChange",a.proxy(this,"updateTrigger"))},updateTrigger:function(){
// Ignore if the last element was just unselected
0!=Craft.elementIndex.getSelectedElements().length&&(this.validateSelection()?this.enableTrigger():this.disableTrigger())},/**
	 * Determines if this action can be performed on the currently selected elements.
	 *
	 * @return boolean
	 */
validateSelection:function(){var a=!0;return this.$selectedItems=Craft.elementIndex.getSelectedElements(),!this.settings.batch&&this.$selectedItems.length>1?a=!1:"function"==typeof this.settings.validateSelection&&(a=this.settings.validateSelection(this.$selectedItems)),a},enableTrigger:function(){this.triggerEnabled||(this.$trigger.removeClass("disabled"),this.triggerEnabled=!0)},disableTrigger:function(){this.triggerEnabled&&(this.$trigger.addClass("disabled"),this.triggerEnabled=!1)},handleTriggerActivation:function(a){a.preventDefault(),a.stopPropagation(),this.triggerEnabled&&this.settings.activate(this.$selectedItems)}},{defaults:{type:null,batch:!0,validateSelection:null,activate:null}}),/**
 * Element editor
 */
Craft.ElementEditor=Garnish.Base.extend({$element:null,elementId:null,siteId:null,$form:null,$fieldsContainer:null,$cancelBtn:null,$saveBtn:null,$spinner:null,$siteSelect:null,$siteSpinner:null,hud:null,init:function(b,c){
// Param mapping
"undefined"==typeof c&&a.isPlainObject(b)&&(
// (settings)
c=b,b=null),this.$element=b,this.setSettings(c,Craft.ElementEditor.defaults),this.loadHud()},setElementAttribute:function(a,b){this.settings.attributes||(this.settings.attributes={}),null===b?delete this.settings.attributes[a]:this.settings.attributes[a]=b},getBaseData:function(){var b=a.extend({},this.settings.params);return this.settings.siteId?b.siteId=this.settings.siteId:this.$element&&this.$element.data("site-id")&&(b.siteId=this.$element.data("site-id")),this.settings.elementId?b.elementId=this.settings.elementId:this.$element&&this.$element.data("id")&&(b.elementId=this.$element.data("id")),this.settings.elementType&&(b.elementType=this.settings.elementType),this.settings.attributes&&(b.attributes=this.settings.attributes),b},loadHud:function(){this.onBeginLoading();var b=this.getBaseData();b.includeSites=this.settings.showSiteSwitcher,Craft.postActionRequest("elements/get-editor-html",b,a.proxy(this,"showHud"))},showHud:function(b,c){if(this.onEndLoading(),"success"==c){var d=a();if(b.sites){var e=a('<div class="header"/>'),f=a('<div class="select"/>').appendTo(e);this.$siteSelect=a("<select/>").appendTo(f),this.$siteSpinner=a('<div class="spinner hidden"/>').appendTo(e);for(var g=0;g<b.sites.length;g++){var h=b.sites[g];a('<option value="'+h.id+'"'+(h.id==b.siteId?' selected="selected"':"")+">"+h.name+"</option>").appendTo(this.$siteSelect)}this.addListener(this.$siteSelect,"change","switchSite"),d=d.add(e)}this.$form=a("<div/>"),this.$fieldsContainer=a('<div class="fields"/>').appendTo(this.$form),this.updateForm(b),this.onCreateForm(this.$form);var i=a('<div class="footer"/>').appendTo(this.$form),j=a('<div class="buttons right"/>').appendTo(i);if(this.$cancelBtn=a('<div class="btn">'+Craft.t("app","Cancel")+"</div>").appendTo(j),this.$saveBtn=a('<input class="btn submit" type="submit" value="'+Craft.t("app","Save")+'"/>').appendTo(j),this.$spinner=a('<div class="spinner hidden"/>').appendTo(j),d=d.add(this.$form),this.hud)this.hud.updateBody(d),this.hud.updateSizeAndPosition();else{var k=this.settings.hudTrigger||this.$element;this.hud=new Garnish.HUD(k,d,{bodyClass:"body elementeditor",closeOtherHUDs:!1,onShow:a.proxy(this,"onShowHud"),onHide:a.proxy(this,"onHideHud"),onSubmit:a.proxy(this,"saveElement")}),this.hud.$hud.data("elementEditor",this),this.hud.on("hide",a.proxy(function(){delete this.hud},this))}
// Focus on the first text input
d.find(".text:first").focus(),this.addListener(this.$cancelBtn,"click",function(){this.hud.hide()})}},switchSite:function(){var b=this.$siteSelect.val();if(b!=this.siteId){this.$siteSpinner.removeClass("hidden");var c=this.getBaseData();c.siteId=b,Craft.postActionRequest("elements/get-editor-html",c,a.proxy(function(a,b){this.$siteSpinner.addClass("hidden"),"success"==b?this.updateForm(a):this.$siteSelect.val(this.siteId)},this))}},updateForm:function(b){this.siteId=b.siteId,this.$fieldsContainer.html(b.html);for(var c=this.$fieldsContainer.find("> .meta > .field > .heading > .instructions"),d=0;d<c.length;d++)c.eq(d).replaceWith(a("<span/>",{class:"info",html:c.eq(d).children().html()})).infoicon();Garnish.requestAnimationFrame(a.proxy(function(){Craft.appendHeadHtml(b.headHtml),Craft.appendFootHtml(b.footHtml),Craft.initUiElements(this.$fieldsContainer)},this))},saveElement:function(){var b=this.settings.validators;if(a.isArray(b))for(var c=0;c<b.length;c++)if(a.isFunction(b[c])&&!b[c].call())return!1;this.$spinner.removeClass("hidden");var d=a.param(this.getBaseData())+"&"+this.hud.$body.serialize();Craft.postActionRequest("elements/save-element",d,a.proxy(function(a,b){if(this.$spinner.addClass("hidden"),"success"==b)if("success"==b&&a.success){if(this.$element&&this.siteId==this.$element.data("site-id")){
// Update the label
var c=this.$element.find(".title"),d=c.find("a");d.length&&a.cpEditUrl?(d.attr("href",a.cpEditUrl),d.text(a.newTitle)):c.text(a.newTitle)}
// Update Live Preview
"undefined"!=typeof Craft.livePreview&&Craft.livePreview.updateIframe(!0),this.closeHud(),this.onSaveElement(a)}else this.updateForm(a),Garnish.shake(this.hud.$hud)},this))},closeHud:function(){this.hud.hide(),delete this.hud},
// Events
// -------------------------------------------------------------------------
onShowHud:function(){this.settings.onShowHud(),this.trigger("showHud")},onHideHud:function(){this.settings.onHideHud(),this.trigger("hideHud")},onBeginLoading:function(){this.$element&&this.$element.addClass("loading"),this.settings.onBeginLoading(),this.trigger("beginLoading")},onEndLoading:function(){this.$element&&this.$element.removeClass("loading"),this.settings.onEndLoading(),this.trigger("endLoading")},onSaveElement:function(a){this.settings.onSaveElement(a),this.trigger("saveElement",{response:a})},onCreateForm:function(a){this.settings.onCreateForm(a)}},{defaults:{hudTrigger:null,showSiteSwitcher:!0,elementId:null,elementType:null,siteId:null,attributes:null,params:null,onShowHud:a.noop,onHideHud:a.noop,onBeginLoading:a.noop,onEndLoading:a.noop,onCreateForm:a.noop,onSaveElement:a.noop,validators:[]}}),/**
 * Elevated Session Form
 */
Craft.ElevatedSessionForm=Garnish.Base.extend({$form:null,inputs:null,init:function(b,c){
// Only check specific inputs?
if(this.$form=a(b),"undefined"!=typeof c){this.inputs=[];for(var c=a.makeArray(c),d=0;d<c.length;d++)for(var e=a(c[d]),f=0;f<e.length;f++){var g=e.eq(f);this.inputs.push({input:g,val:Garnish.getInputPostVal(g)})}}this.addListener(this.$form,"submit","handleFormSubmit")},handleFormSubmit:function(b){
// Ignore if we're in the middle of getting the elevated session timeout
if(Craft.elevatedSessionManager.fetchingTimeout)return void b.preventDefault();
// Are we only interested in certain inputs?
if(this.inputs){for(var c=!1,d=0;d<this.inputs.length;d++)
// Has this input's value changed?
if(Garnish.getInputPostVal(this.inputs[d].input)!=this.inputs[d].val){c=!0;break}if(!c)
// No need to interrupt the submit
return}
// Prevent the form from submitting until the user has an elevated session
b.preventDefault(),Craft.elevatedSessionManager.requireElevatedSession(a.proxy(this,"submitForm"))},submitForm:function(){
// Don't let handleFormSubmit() interrupt this time
this.disable(),this.$form.submit(),this.enable()}}),/**
 * Elevated Session Manager
 */
Craft.ElevatedSessionManager=Garnish.Base.extend({fetchingTimeout:!1,passwordModal:null,$passwordInput:null,$passwordSpinner:null,$submitBtn:null,$errorPara:null,callback:null,/**
	 * Requires that the user has an elevated session.
	 *
	 * @param {function} callback The callback function that should be called once the user has an elevated session
	 */
requireElevatedSession:function(b){this.callback=b,
// Check the time remaining on the user's elevated session (if any)
this.fetchingTimeout=!0,Craft.postActionRequest("users/get-elevated-session-timeout",a.proxy(function(a,b){this.fetchingTimeout=!1,"success"==b&&(
// Is there still enough time left or has it been disabled?
a.timeout===!1||a.timeout>=Craft.ElevatedSessionManager.minSafeElevatedSessionTimeout?this.callback():
// Show the password modal
this.showPasswordModal())},this))},showPasswordModal:function(){if(this.passwordModal)this.passwordModal.show();else{var b=a('<form id="elevatedsessionmodal" class="modal secure fitted"/>'),c=a('<div class="body"><p>'+Craft.t("app","Enter your password to continue.")+"</p></div>").appendTo(b),d=a('<div class="inputcontainer">').appendTo(c),e=a('<table class="inputs fullwidth"/>').appendTo(d),f=a("<tr/>").appendTo(e),g=a("<td/>").appendTo(f),h=a('<td class="thin"/>').appendTo(f),i=a('<div class="passwordwrapper"/>').appendTo(g);this.$passwordInput=a('<input type="password" class="text password fullwidth" placeholder="'+Craft.t("app","Password")+'"/>').appendTo(i),this.$passwordSpinner=a('<div class="spinner hidden"/>').appendTo(d),this.$submitBtn=a('<input type="submit" class="btn submit disabled" value="'+Craft.t("app","Submit")+'" />').appendTo(h),this.$errorPara=a('<p class="error"/>').appendTo(c),this.passwordModal=new Garnish.Modal(b,{closeOtherModals:!1,onFadeIn:a.proxy(function(){setTimeout(a.proxy(this,"focusPasswordInput"),100)},this),onFadeOut:a.proxy(function(){this.$passwordInput.val("")},this)}),new Craft.PasswordInput(this.$passwordInput,{onToggleInput:a.proxy(function(a){this.$passwordInput=a},this)}),this.addListener(this.$passwordInput,"textchange","validatePassword"),this.addListener(b,"submit","submitPassword")}},focusPasswordInput:function(){Garnish.isMobileBrowser(!0)||this.$passwordInput.focus()},validatePassword:function(){return this.$passwordInput.val().length>=6?(this.$submitBtn.removeClass("disabled"),!0):(this.$submitBtn.addClass("disabled"),!1)},submitPassword:function(b){if(b&&b.preventDefault(),this.validatePassword()){this.$passwordSpinner.removeClass("hidden"),this.clearLoginError();var c={password:this.$passwordInput.val()};Craft.postActionRequest("users/startElevatedSession",c,a.proxy(function(a,b){this.$passwordSpinner.addClass("hidden"),"success"==b?a.success?(this.passwordModal.hide(),this.callback()):(this.showPasswordError(Craft.t("app","Incorrect password.")),Garnish.shake(this.passwordModal.$container),this.focusPasswordInput()):this.showPasswordError()},this))}},showPasswordError:function(a){null!==a&&"undefined"!=typeof a||(a=Craft.t("app","An unknown error occurred.")),this.$errorPara.text(a),this.passwordModal.updateSizeAndPosition()},clearLoginError:function(){this.showPasswordError("")}},{minSafeElevatedSessionTimeout:5}),
// Instantiate it
Craft.elevatedSessionManager=new Craft.ElevatedSessionManager,/**
 * Entry index class
 */
Craft.EntryIndex=Craft.BaseElementIndex.extend({publishableSections:null,$newEntryBtnGroup:null,$newEntryBtn:null,afterInit:function(){
// Find which of the visible sections the user has permission to create new entries in
this.publishableSections=[];for(var a=0;a<Craft.publishableSections.length;a++){var b=Craft.publishableSections[a];this.getSourceByKey("section:"+b.id)&&this.publishableSections.push(b)}this.base()},getDefaultSourceKey:function(){
// Did they request a specific section in the URL?
if("index"==this.settings.context&&"undefined"!=typeof defaultSectionHandle){if("singles"==defaultSectionHandle)return"singles";for(var b=0;b<this.$sources.length;b++){var c=a(this.$sources[b]);if(c.data("handle")==defaultSectionHandle)return c.data("key")}}return this.base()},onSelectSource:function(){var b;
// Update the New Entry button
// ---------------------------------------------------------------------
if(
// Get the handle of the selected source
b="singles"==this.$source.data("key")?"singles":this.$source.data("handle"),this.publishableSections.length){
// Remove the old button, if there is one
this.$newEntryBtnGroup&&this.$newEntryBtnGroup.remove();
// Determine if they are viewing a section that they have permission to create entries in
var c;if(b)for(var d=0;d<this.publishableSections.length;d++)if(this.publishableSections[d].handle==b){c=this.publishableSections[d];break}this.$newEntryBtnGroup=a('<div class="btngroup submit"/>');var e;
// If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
// Otherwise only show a menu button
if(c){var f=this._getSectionTriggerHref(c),g="index"==this.settings.context?Craft.t("app","New entry"):Craft.t("app","New {section} entry",{section:c.name});this.$newEntryBtn=a('<a class="btn submit add icon" '+f+">"+g+"</a>").appendTo(this.$newEntryBtnGroup),"index"!=this.settings.context&&this.addListener(this.$newEntryBtn,"click",function(a){this._openCreateEntryModal(a.currentTarget.getAttribute("data-id"))}),this.publishableSections.length>1&&(e=a('<div class="btn submit menubtn"></div>').appendTo(this.$newEntryBtnGroup))}else this.$newEntryBtn=e=a('<div class="btn submit add icon menubtn">'+Craft.t("app","New entry")+"</div>").appendTo(this.$newEntryBtnGroup);if(e){for(var h='<div class="menu"><ul>',d=0;d<this.publishableSections.length;d++){var i=this.publishableSections[d];if("index"==this.settings.context||i!=c){var f=this._getSectionTriggerHref(i),g="index"==this.settings.context?i.name:Craft.t("app","New {section} entry",{section:i.name});h+="<li><a "+f+'">'+g+"</a></li>"}}h+="</ul></div>";var j=(a(h).appendTo(this.$newEntryBtnGroup),new Garnish.MenuBtn(e));"index"!=this.settings.context&&j.on("optionSelect",a.proxy(function(a){this._openCreateEntryModal(a.option.getAttribute("data-id"))},this))}this.addButton(this.$newEntryBtnGroup)}
// Update the URL if we're on the Entries index
// ---------------------------------------------------------------------
if("index"==this.settings.context&&"undefined"!=typeof history){var k="entries";b&&(k+="/"+b),history.replaceState({},"",Craft.getUrl(k))}this.base()},_getSectionTriggerHref:function(a){return"index"==this.settings.context?'href="'+Craft.getUrl("entries/"+a.handle+"/new")+'"':'data-id="'+a.id+'"'},_openCreateEntryModal:function(b){if(!this.$newEntryBtn.hasClass("loading")){for(var c,d=0;d<this.publishableSections.length;d++)if(this.publishableSections[d].id==b){c=this.publishableSections[d];break}if(c){this.$newEntryBtn.addClass("inactive");var e=this.$newEntryBtn.text();this.$newEntryBtn.text(Craft.t("app","New {section} entry",{section:c.name})),new Craft.ElementEditor({hudTrigger:this.$newEntryBtnGroup,elementType:"Entry",siteId:this.siteId,attributes:{sectionId:b},onBeginLoading:a.proxy(function(){this.$newEntryBtn.addClass("loading")},this),onEndLoading:a.proxy(function(){this.$newEntryBtn.removeClass("loading")},this),onHideHud:a.proxy(function(){this.$newEntryBtn.removeClass("inactive").text(e)},this),onSaveElement:a.proxy(function(a){
// Make sure the right section is selected
var c="section:"+b;this.sourceKey!=c&&this.selectSourceByKey(c),this.selectElementAfterUpdate(a.id),this.updateElements()},this)})}}}}),
// Register it!
Craft.registerElementIndexClass("craft\\app\\elements\\Entry",Craft.EntryIndex),Craft.FieldLayoutDesigner=Garnish.Base.extend({$container:null,$tabContainer:null,$unusedFieldContainer:null,$newTabBtn:null,$allFields:null,tabGrid:null,unusedFieldGrid:null,tabDrag:null,fieldDrag:null,init:function(b,c){this.$container=a(b),this.setSettings(c,Craft.FieldLayoutDesigner.defaults),this.$tabContainer=this.$container.children(".fld-tabs"),this.$unusedFieldContainer=this.$container.children(".unusedfields"),this.$newTabBtn=this.$container.find("> .newtabbtn-container > .btn"),this.$allFields=this.$unusedFieldContainer.find(".fld-field"),
// Set up the layout grids
this.tabGrid=new Craft.Grid(this.$tabContainer,Craft.FieldLayoutDesigner.gridSettings),this.unusedFieldGrid=new Craft.Grid(this.$unusedFieldContainer,Craft.FieldLayoutDesigner.gridSettings);for(var d=this.$tabContainer.children(),e=0;e<d.length;e++)this.initTab(a(d[e]));this.fieldDrag=new Craft.FieldLayoutDesigner.FieldDrag(this),this.settings.customizableTabs&&(this.tabDrag=new Craft.FieldLayoutDesigner.TabDrag(this),this.addListener(this.$newTabBtn,"activate","addTab"))},initTab:function(b){if(this.settings.customizableTabs){var c=b.find(".tabs .settings"),d=a('<div class="menu" data-align="center"/>').insertAfter(c),e=a("<ul/>").appendTo(d);a('<li><a data-action="rename">'+Craft.t("app","Rename")+"</a></li>").appendTo(e),a('<li><a data-action="delete">'+Craft.t("app","Delete")+"</a></li>").appendTo(e),new Garnish.MenuBtn(c,{onOptionSelect:a.proxy(this,"onTabOptionSelect")})}for(var f=b.children(".fld-tabcontent").children(),g=0;g<f.length;g++)this.initField(a(f[g]))},initField:function(b){var c=b.find(".settings"),d=a('<div class="menu" data-align="center"/>').insertAfter(c),e=a("<ul/>").appendTo(d);b.hasClass("fld-required")?a('<li><a data-action="toggle-required">'+Craft.t("app","Make not required")+"</a></li>").appendTo(e):a('<li><a data-action="toggle-required">'+Craft.t("app","Make required")+"</a></li>").appendTo(e),a('<li><a data-action="remove">'+Craft.t("app","Remove")+"</a></li>").appendTo(e),new Garnish.MenuBtn(c,{onOptionSelect:a.proxy(this,"onFieldOptionSelect")})},onTabOptionSelect:function(b){if(this.settings.customizableTabs){var c=a(b),d=c.data("menu").$anchor.parent().parent().parent(),e=c.data("action");switch(e){case"rename":this.renameTab(d);break;case"delete":this.deleteTab(d)}}},onFieldOptionSelect:function(b){var c=a(b),d=c.data("menu").$anchor.parent(),e=c.data("action");switch(e){case"toggle-required":this.toggleRequiredField(d,c);break;case"remove":this.removeField(d)}},renameTab:function(a){if(this.settings.customizableTabs){var b=a.find(".tabs .tab span"),c=b.text(),d=prompt(Craft.t("app","Give your tab a name."),c);d&&d!=c&&(b.text(d),a.find(".id-input").attr("name",this.getFieldInputName(d)))}},deleteTab:function(b){if(this.settings.customizableTabs){for(var c=b.find(".fld-field"),d=0;d<c.length;d++){var e=a(c[d]).attr("data-id");this.removeFieldById(e)}this.tabGrid.removeItems(b),this.tabDrag.removeItems(b),b.remove()}},toggleRequiredField:function(b,c){b.hasClass("fld-required")?(b.removeClass("fld-required"),b.find(".required-input").remove(),setTimeout(function(){c.text(Craft.t("app","Make required"))},500)):(b.addClass("fld-required"),a('<input class="required-input" type="hidden" name="'+this.settings.requiredFieldInputName+'" value="'+b.data("id")+'">').appendTo(b),setTimeout(function(){c.text(Craft.t("app","Make not required"))},500))},removeField:function(a){var b=a.attr("data-id");a.remove(),this.removeFieldById(b),this.tabGrid.refreshCols(!0)},removeFieldById:function(a){var b=this.$allFields.filter("[data-id="+a+"]:first"),c=b.closest(".fld-tab");b.removeClass("hidden"),c.hasClass("hidden")?(c.removeClass("hidden"),this.unusedFieldGrid.addItems(c),this.settings.customizableTabs&&this.tabDrag.addItems(c)):this.unusedFieldGrid.refreshCols(!0)},addTab:function(){if(this.settings.customizableTabs){var b=a('<div class="fld-tab"><div class="tabs"><div class="tab sel draggable"><span>Tab '+(this.tabGrid.$items.length+1)+'</span><a class="settings icon" title="'+Craft.t("app","Rename")+'"></a></div></div><div class="fld-tabcontent"></div></div>').appendTo(this.$tabContainer);this.tabGrid.addItems(b),this.tabDrag.addItems(b),this.initTab(b)}},getFieldInputName:function(a){return this.settings.fieldInputName.replace(/__TAB_NAME__/g,Craft.encodeUriComponent(a))}},{gridSettings:{itemSelector:".fld-tab:not(.hidden)",minColWidth:240,percentageWidths:!1,fillMode:"grid",snapToGrid:30},defaults:{customizableTabs:!0,fieldInputName:"fieldLayout[__TAB_NAME__][]",requiredFieldInputName:"requiredFields[]"}}),Craft.FieldLayoutDesigner.BaseDrag=Garnish.Drag.extend({designer:null,$insertion:null,showingInsertion:!1,$caboose:null,draggingUnusedItem:!1,addToTabGrid:!1,/**
	 * Constructor
	 */
init:function(a,b){this.designer=a;
// Find all the items from both containers
var c=this.designer.$tabContainer.find(this.itemSelector).add(this.designer.$unusedFieldContainer.find(this.itemSelector));this.base(c,b)},/**
	 * On Drag Start
	 */
onDragStart:function(){this.base(),
// Are we dragging an unused item?
this.draggingUnusedItem=this.$draggee.hasClass("unused"),
// Create the insertion
this.$insertion=this.getInsertion(),
// Add the caboose
this.addCaboose(),this.$items=a().add(this.$items.add(this.$caboose)),this.addToTabGrid&&this.designer.tabGrid.addItems(this.$caboose),
// Swap the draggee with the insertion if dragging a selected item
this.draggingUnusedItem?this.showingInsertion=!1:(
// Actually replace the draggee with the insertion
this.$insertion.insertBefore(this.$draggee),this.$draggee.detach(),this.$items=a().add(this.$items.not(this.$draggee).add(this.$insertion)),this.showingInsertion=!0,this.addToTabGrid&&(this.designer.tabGrid.removeItems(this.$draggee),this.designer.tabGrid.addItems(this.$insertion))),this.setMidpoints()},/**
	 * Append the caboose
	 */
addCaboose:a.noop,/**
	 * Returns the item's container
	 */
getItemContainer:a.noop,/**
	 * Tests if an item is within the tab container.
	 */
isItemInTabContainer:function(a){return this.getItemContainer(a)[0]==this.designer.$tabContainer[0]},/**
	 * Sets the item midpoints up front so we don't have to keep checking on every mouse move
	 */
setMidpoints:function(){for(var b=0;b<this.$items.length;b++){var c=a(this.$items[b]);
// Skip the unused tabs
if(this.isItemInTabContainer(c)){var d=c.offset();c.data("midpoint",{left:d.left+c.outerWidth()/2,top:d.top+c.outerHeight()/2})}}},/**
	 * On Drag
	 */
onDrag:function(){
// Are we hovering over the tab container?
this.draggingUnusedItem&&!Garnish.hitTest(this.mouseX,this.mouseY,this.designer.$tabContainer)?this.showingInsertion&&(this.$insertion.remove(),this.$items=a().add(this.$items.not(this.$insertion)),this.showingInsertion=!1,this.addToTabGrid?this.designer.tabGrid.removeItems(this.$insertion):this.designer.tabGrid.refreshCols(!0),this.setMidpoints()):(
// Is there a new closest item?
this.onDrag._closestItem=this.getClosestItem(),this.onDrag._closestItem!=this.$insertion[0]&&(this.showingInsertion&&a.inArray(this.$insertion[0],this.$items)<a.inArray(this.onDrag._closestItem,this.$items)&&a.inArray(this.onDrag._closestItem,this.$caboose)==-1?this.$insertion.insertAfter(this.onDrag._closestItem):this.$insertion.insertBefore(this.onDrag._closestItem),this.$items=a().add(this.$items.add(this.$insertion)),this.showingInsertion=!0,this.addToTabGrid?this.designer.tabGrid.addItems(this.$insertion):this.designer.tabGrid.refreshCols(!0),this.setMidpoints())),this.base()},/**
	 * Returns the closest item to the cursor.
	 */
getClosestItem:function(){for(this.getClosestItem._closestItem=null,this.getClosestItem._closestItemMouseDiff=null,this.getClosestItem._i=0;this.getClosestItem._i<this.$items.length;this.getClosestItem._i++)this.getClosestItem._$item=a(this.$items[this.getClosestItem._i]),
// Skip the unused tabs
this.isItemInTabContainer(this.getClosestItem._$item)&&(this.getClosestItem._midpoint=this.getClosestItem._$item.data("midpoint"),this.getClosestItem._mouseDiff=Garnish.getDist(this.getClosestItem._midpoint.left,this.getClosestItem._midpoint.top,this.mouseX,this.mouseY),(null===this.getClosestItem._closestItem||this.getClosestItem._mouseDiff<this.getClosestItem._closestItemMouseDiff)&&(this.getClosestItem._closestItem=this.getClosestItem._$item[0],this.getClosestItem._closestItemMouseDiff=this.getClosestItem._mouseDiff));return this.getClosestItem._closestItem},/**
	 * On Drag Stop
	 */
onDragStop:function(){this.showingInsertion&&(this.$insertion.replaceWith(this.$draggee),this.$items=a().add(this.$items.not(this.$insertion).add(this.$draggee)),this.addToTabGrid&&(this.designer.tabGrid.removeItems(this.$insertion),this.designer.tabGrid.addItems(this.$draggee))),
// Drop the caboose
this.$items=this.$items.not(this.$caboose),this.$caboose.remove(),this.addToTabGrid&&this.designer.tabGrid.removeItems(this.$caboose),
// "show" the drag items, but make them invisible
this.$draggee.css({display:this.draggeeDisplay,visibility:"hidden"}),this.designer.tabGrid.refreshCols(!0),this.designer.unusedFieldGrid.refreshCols(!0),
// return the helpers to the draggees
this.returnHelpersToDraggees(),this.base()}}),Craft.FieldLayoutDesigner.TabDrag=Craft.FieldLayoutDesigner.BaseDrag.extend({itemSelector:"> div.fld-tab",addToTabGrid:!0,/**
	 * Constructor
	 */
init:function(a){var b={handle:".tab"};this.base(a,b)},/**
	 * Append the caboose
	 */
addCaboose:function(){this.$caboose=a('<div class="fld-tab fld-tab-caboose"/>').appendTo(this.designer.$tabContainer)},/**
	 * Returns the insertion
	 */
getInsertion:function(){var b=this.$draggee.find(".tab");return a('<div class="fld-tab fld-insertion" style="height: '+this.$draggee.height()+'px;"><div class="tabs"><div class="tab sel draggable" style="width: '+b.width()+"px; height: "+b.height()+'px;"></div></div><div class="fld-tabcontent" style="height: '+this.$draggee.find(".fld-tabcontent").height()+'px;"></div></div>')},/**
	 * Returns the item's container
	 */
getItemContainer:function(a){return a.parent()},/**
	 * On Drag Stop
	 */
onDragStop:function(){if(this.draggingUnusedItem&&this.showingInsertion){
// Create a new tab based on that field group
var b=this.$draggee.clone().removeClass("unused"),c=b.find(".tab span").text();b.find(".fld-field").removeClass("unused"),
// Add the edit button
b.find(".tabs .tab").append('<a class="settings icon" title="'+Craft.t("app","Edit")+'"></a>');
// Remove any hidden fields
var d=b.find(".fld-field"),e=d.filter(".hidden").remove();d=d.not(e),d.prepend('<a class="settings icon" title="'+Craft.t("app","Edit")+'"></a>');for(var f=0;f<d.length;f++){var g=a(d[f]),h=this.designer.getFieldInputName(c);g.append('<input class="id-input" type="hidden" name="'+h+'" value="'+g.data("id")+'">')}this.designer.fieldDrag.addItems(d),this.designer.initTab(b),
// Set the unused field group and its fields to hidden
this.$draggee.css({visibility:"inherit",display:"field"}).addClass("hidden"),this.$draggee.find(".fld-field").addClass("hidden"),
// Set this.$draggee to the clone, as if we were dragging that all along
this.$draggee=b,
// Remember it for later
this.addItems(b),
// Update the grids
this.designer.tabGrid.addItems(b),this.designer.unusedFieldGrid.removeItems(this.$draggee)}this.base()}}),Craft.FieldLayoutDesigner.FieldDrag=Craft.FieldLayoutDesigner.BaseDrag.extend({itemSelector:"> div.fld-tab .fld-field",/**
	 * Append the caboose
	 */
addCaboose:function(){this.$caboose=a();for(var b=this.designer.$tabContainer.children().children(".fld-tabcontent"),c=0;c<b.length;c++){var d=a('<div class="fld-tab fld-tab-caboose"/>').appendTo(b[c]);this.$caboose=this.$caboose.add(d)}},/**
	 * Returns the insertion
	 */
getInsertion:function(){return a('<div class="fld-field fld-insertion" style="height: '+this.$draggee.height()+'px;"/>')},/**
	 * Returns the item's container
	 */
getItemContainer:function(a){return a.parent().parent().parent()},/**
	 * On Drag Stop
	 */
onDragStop:function(){if(this.draggingUnusedItem&&this.showingInsertion){
// Create a new field based on that one
var a=this.$draggee.clone().removeClass("unused");
// Hide the group too?
if(a.prepend('<a class="settings icon" title="'+Craft.t("app","Edit")+'"></a>'),this.designer.initField(a),
// Hide the unused field
this.$draggee.css({visibility:"inherit",display:"field"}).addClass("hidden"),0==this.$draggee.siblings(":not(.hidden)").length){var b=this.$draggee.parent().parent();b.addClass("hidden"),this.designer.unusedFieldGrid.removeItems(b)}
// Set this.$draggee to the clone, as if we were dragging that all along
this.$draggee=a,
// Remember it for later
this.addItems(a)}if(this.showingInsertion){
// Find the field's new tab name
var c=this.$insertion.parent().parent().find(".tab span").text(),d=this.designer.getFieldInputName(c);this.draggingUnusedItem?this.$draggee.append('<input class="id-input" type="hidden" name="'+d+'" value="'+this.$draggee.data("id")+'">'):this.$draggee.find(".id-input").attr("name",d)}this.base()}}),/**
 * FieldToggle
 */
Craft.FieldToggle=Garnish.Base.extend({$toggle:null,targetPrefix:null,targetSelector:null,reverseTargetSelector:null,_$target:null,_$reverseTarget:null,type:null,init:function(b){this.$toggle=a(b),
// Is this already a field toggle?
this.$toggle.data("fieldtoggle")&&(Garnish.log("Double-instantiating a field toggle on an element"),this.$toggle.data("fieldtoggle").destroy()),this.$toggle.data("fieldtoggle",this),this.type=this.getType(),"select"==this.type?this.targetPrefix=this.$toggle.attr("data-target-prefix")||"":(this.targetSelector=this.normalizeTargetSelector(this.$toggle.data("target")),this.reverseTargetSelector=this.normalizeTargetSelector(this.$toggle.data("reverse-target"))),this.findTargets(),"link"==this.type?this.addListener(this.$toggle,"click","onToggleChange"):this.addListener(this.$toggle,"change","onToggleChange")},normalizeTargetSelector:function(a){return a&&!a.match(/^[#\.]/)&&(a="#"+a),a},getType:function(){return"INPUT"==this.$toggle.prop("nodeName")&&"checkbox"==this.$toggle.attr("type").toLowerCase()?"checkbox":"SELECT"==this.$toggle.prop("nodeName")?"select":"A"==this.$toggle.prop("nodeName")?"link":"DIV"==this.$toggle.prop("nodeName")&&this.$toggle.hasClass("lightswitch")?"lightswitch":void 0},findTargets:function(){"select"==this.type?this._$target=a(this.normalizeTargetSelector(this.targetPrefix+this.getToggleVal())):(this.targetSelector&&(this._$target=a(this.targetSelector)),this.reverseTargetSelector&&(this._$reverseTarget=a(this.reverseTargetSelector)))},getToggleVal:function(){if("lightswitch"==this.type)return this.$toggle.children("input").val();var a=Garnish.getInputPostVal(this.$toggle);return null===a?null:a.replace(/[\[\]\\]+/g,"-")},onToggleChange:function(){"select"==this.type?(this.hideTarget(this._$target),this.findTargets(),this.showTarget(this._$target)):("link"==this.type?this.onToggleChange._show=this.$toggle.hasClass("collapsed")||!this.$toggle.hasClass("expanded"):this.onToggleChange._show=!!this.getToggleVal(),this.onToggleChange._show?(this.showTarget(this._$target),this.hideTarget(this._$reverseTarget)):(this.hideTarget(this._$target),this.showTarget(this._$reverseTarget)),delete this.onToggleChange._show)},showTarget:function(a){a&&a.length&&(this.showTarget._currentHeight=a.height(),a.removeClass("hidden"),"select"!=this.type&&("link"==this.type&&(this.$toggle.removeClass("collapsed"),this.$toggle.addClass("expanded")),a.height("auto"),this.showTarget._targetHeight=a.height(),a.css({height:this.showTarget._currentHeight,overflow:"hidden"}),a.velocity("stop"),a.velocity({height:this.showTarget._targetHeight},"fast",function(){a.css({height:"",overflow:""})}),delete this.showTarget._targetHeight),delete this.showTarget._currentHeight,
// Trigger a resize event in case there are any grids in the target that need to initialize
Garnish.$win.trigger("resize"))},hideTarget:function(a){a&&a.length&&("select"==this.type?a.addClass("hidden"):("link"==this.type&&(this.$toggle.removeClass("expanded"),this.$toggle.addClass("collapsed")),a.css("overflow","hidden"),a.velocity("stop"),a.velocity({height:0},"fast",function(){a.addClass("hidden")})))}}),Craft.Grid=Garnish.Base.extend({$container:null,$items:null,items:null,totalCols:null,colPctWidth:null,sizeUnit:null,possibleItemColspans:null,possibleItemPositionsByColspan:null,itemPositions:null,itemColspansByPosition:null,layouts:null,layout:null,itemHeights:null,leftPadding:null,_refreshingCols:!1,_refreshColsAfterRefresh:!1,_forceRefreshColsAfterRefresh:!1,init:function(b,c){this.$container=a(b),
// Is this already a grid?
this.$container.data("grid")&&(Garnish.log("Double-instantiating a grid on an element"),this.$container.data("grid").destroy()),this.$container.data("grid",this),this.setSettings(c,Craft.Grid.defaults),"pct"==this.settings.mode?this.sizeUnit="%":this.sizeUnit="px",
// Set the refreshCols() proxy that container resizes will trigger
this.handleContainerHeightProxy=a.proxy(function(){this.refreshCols(!1,!0)},this),this.$items=this.$container.children(this.settings.itemSelector),this.setItems(),this.refreshCols(!0,!1),Garnish.$doc.ready(a.proxy(function(){this.refreshCols(!1,!1)},this))},addItems:function(b){this.$items=a().add(this.$items.add(b)),this.setItems(),this.refreshCols(!0,!0),a(b).velocity("finish")},removeItems:function(b){this.$items=a().add(this.$items.not(b)),this.setItems(),this.refreshCols(!0,!0)},resetItemOrder:function(){this.$items=a().add(this.$items),this.setItems(),this.refreshCols(!0,!0)},setItems:function(){for(this.setItems._={},this.items=[],this.setItems._.i=0;this.setItems._.i<this.$items.length;this.setItems._.i++)this.items.push(a(this.$items[this.setItems._.i]));delete this.setItems._},refreshCols:function(b,c){if(this._refreshingCols)return this._refreshColsAfterRefresh=!0,void(b&&(this._forceRefreshColsAfterRefresh=!0));if(this._refreshingCols=!0,!this.items.length)return void this.completeRefreshCols();if(this.refreshCols._={},
// Check to see if the grid is actually visible
this.refreshCols._.oldHeight=this.$container[0].style.height,this.$container[0].style.height=1,this.refreshCols._.scrollHeight=this.$container[0].scrollHeight,this.$container[0].style.height=this.refreshCols._.oldHeight,0==this.refreshCols._.scrollHeight)return void this.completeRefreshCols();
// Same number of columns as before?
if(this.settings.cols?this.refreshCols._.totalCols=this.settings.cols:(this.refreshCols._.totalCols=Math.floor(this.$container.width()/this.settings.minColWidth),this.settings.maxCols&&this.refreshCols._.totalCols>this.settings.maxCols&&(this.refreshCols._.totalCols=this.settings.maxCols)),0==this.refreshCols._.totalCols&&(this.refreshCols._.totalCols=1),b!==!0&&this.totalCols===this.refreshCols._.totalCols)return void this.completeRefreshCols();if(this.totalCols=this.refreshCols._.totalCols,
// Temporarily stop listening to container resizes
this.removeListener(this.$container,"resize"),"grid"==this.settings.fillMode)for(this.refreshCols._.itemIndex=0;this.refreshCols._.itemIndex<this.items.length;){for(
// Append the next X items and figure out which one is the tallest
this.refreshCols._.tallestItemHeight=-1,this.refreshCols._.colIndex=0,this.refreshCols._.i=this.refreshCols._.itemIndex;this.refreshCols._.i<this.refreshCols._.itemIndex+this.totalCols&&this.refreshCols._.i<this.items.length;this.refreshCols._.i++)this.refreshCols._.itemHeight=this.items[this.refreshCols._.i].height("auto").height(),this.refreshCols._.itemHeight>this.refreshCols._.tallestItemHeight&&(this.refreshCols._.tallestItemHeight=this.refreshCols._.itemHeight),this.refreshCols._.colIndex++;
// Now set their heights to the tallest one
for(this.settings.snapToGrid&&(this.refreshCols._.remainder=this.refreshCols._.tallestItemHeight%this.settings.snapToGrid,this.refreshCols._.remainder&&(this.refreshCols._.tallestItemHeight+=this.settings.snapToGrid-this.refreshCols._.remainder)),this.refreshCols._.i=this.refreshCols._.itemIndex;this.refreshCols._.i<this.refreshCols._.itemIndex+this.totalCols&&this.refreshCols._.i<this.items.length;this.refreshCols._.i++)this.items[this.refreshCols._.i].height(this.refreshCols._.tallestItemHeight);
// set the this.refreshCols._.itemIndex pointer to the next one up
this.refreshCols._.itemIndex+=this.totalCols}else
// If there's only one column, sneak out early
if(this.removeListener(this.$items,"resize"),1==this.totalCols)this.$container.height("auto"),this.$items.show().css({position:"relative",width:"auto",top:0}).css(Craft.left,0);else{for(this.$items.css("position","absolute"),"pct"==this.settings.mode&&(this.colPctWidth=100/this.totalCols),
// The setup
this.layouts=[],this.itemPositions=[],this.itemColspansByPosition=[],
// Figure out all of the possible colspans for each item,
// as well as all the possible positions for each item at each of its colspans
this.possibleItemColspans=[],this.possibleItemPositionsByColspan=[],this.itemHeightsByColspan=[],this.refreshCols._.item=0;this.refreshCols._.item<this.items.length;this.refreshCols._.item++)for(this.possibleItemColspans[this.refreshCols._.item]=[],this.possibleItemPositionsByColspan[this.refreshCols._.item]={},this.itemHeightsByColspan[this.refreshCols._.item]={},this.refreshCols._.$item=this.items[this.refreshCols._.item].show(),this.refreshCols._.positionRight="right"==this.refreshCols._.$item.data("position"),this.refreshCols._.positionLeft="left"==this.refreshCols._.$item.data("position"),this.refreshCols._.minColspan=this.refreshCols._.$item.data("colspan")?this.refreshCols._.$item.data("colspan"):this.refreshCols._.$item.data("min-colspan")?this.refreshCols._.$item.data("min-colspan"):1,this.refreshCols._.maxColspan=this.refreshCols._.$item.data("colspan")?this.refreshCols._.$item.data("colspan"):this.refreshCols._.$item.data("max-colspan")?this.refreshCols._.$item.data("max-colspan"):this.totalCols,this.refreshCols._.minColspan>this.totalCols&&(this.refreshCols._.minColspan=this.totalCols),this.refreshCols._.maxColspan>this.totalCols&&(this.refreshCols._.maxColspan=this.totalCols),this.refreshCols._.colspan=this.refreshCols._.minColspan;this.refreshCols._.colspan<=this.refreshCols._.maxColspan;this.refreshCols._.colspan++)for(
// Get the height for this colspan
this.refreshCols._.$item.css("width",this.getItemWidth(this.refreshCols._.colspan)+this.sizeUnit),this.itemHeightsByColspan[this.refreshCols._.item][this.refreshCols._.colspan]=this.refreshCols._.$item.outerHeight(),this.possibleItemColspans[this.refreshCols._.item].push(this.refreshCols._.colspan),this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan]=[],this.refreshCols._.positionLeft?(this.refreshCols._.minPosition=0,this.refreshCols._.maxPosition=0):this.refreshCols._.positionRight?(this.refreshCols._.minPosition=this.totalCols-this.refreshCols._.colspan,this.refreshCols._.maxPosition=this.refreshCols._.minPosition):(this.refreshCols._.minPosition=0,this.refreshCols._.maxPosition=this.totalCols-this.refreshCols._.colspan),this.refreshCols._.position=this.refreshCols._.minPosition;this.refreshCols._.position<=this.refreshCols._.maxPosition;this.refreshCols._.position++)this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan].push(this.refreshCols._.position);for(
// Find all the possible layouts
this.refreshCols._.colHeights=[],this.refreshCols._.i=0;this.refreshCols._.i<this.totalCols;this.refreshCols._.i++)this.refreshCols._.colHeights.push(0);for(this.createLayouts(0,[],[],this.refreshCols._.colHeights,0),
// Now find the layout that looks the best.
// First find the layouts with the highest number of used columns
this.refreshCols._.layoutTotalCols=[],this.refreshCols._.i=0;this.refreshCols._.i<this.layouts.length;this.refreshCols._.i++)for(this.refreshCols._.layoutTotalCols[this.refreshCols._.i]=0,this.refreshCols._.j=0;this.refreshCols._.j<this.totalCols;this.refreshCols._.j++)this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j]&&this.refreshCols._.layoutTotalCols[this.refreshCols._.i]++;
// Filter out the ones that aren't using as many columns as they could be
for(this.refreshCols._.highestTotalCols=Math.max.apply(null,this.refreshCols._.layoutTotalCols),this.refreshCols._.i=this.layouts.length-1;this.refreshCols._.i>=0;this.refreshCols._.i--)this.refreshCols._.layoutTotalCols[this.refreshCols._.i]!=this.refreshCols._.highestTotalCols&&this.layouts.splice(this.refreshCols._.i,1);for(
// Find the layout(s) with the least overall height
this.refreshCols._.layoutHeights=[],this.refreshCols._.i=0;this.refreshCols._.i<this.layouts.length;this.refreshCols._.i++)this.refreshCols._.layoutHeights.push(Math.max.apply(null,this.layouts[this.refreshCols._.i].colHeights));for(this.refreshCols._.shortestHeight=Math.min.apply(null,this.refreshCols._.layoutHeights),this.refreshCols._.shortestLayouts=[],this.refreshCols._.emptySpaces=[],this.refreshCols._.i=0;this.refreshCols._.i<this.refreshCols._.layoutHeights.length;this.refreshCols._.i++)if(this.refreshCols._.layoutHeights[this.refreshCols._.i]==this.refreshCols._.shortestHeight){for(this.refreshCols._.shortestLayouts.push(this.layouts[this.refreshCols._.i]),
// Now get its total empty space, including any trailing empty space
this.refreshCols._.emptySpace=this.layouts[this.refreshCols._.i].emptySpace,this.refreshCols._.j=0;this.refreshCols._.j<this.totalCols;this.refreshCols._.j++)this.refreshCols._.emptySpace+=this.refreshCols._.shortestHeight-this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j];this.refreshCols._.emptySpaces.push(this.refreshCols._.emptySpace)}for(
// And the layout with the least empty space is...
this.layout=this.refreshCols._.shortestLayouts[a.inArray(Math.min.apply(null,this.refreshCols._.emptySpaces),this.refreshCols._.emptySpaces)],
// Figure out the left padding based on the number of empty columns
this.refreshCols._.totalEmptyCols=0,this.refreshCols._.i=this.layout.colHeights.length-1;this.refreshCols._.i>=0&&0==this.layout.colHeights[this.refreshCols._.i];this.refreshCols._.i--)this.refreshCols._.totalEmptyCols++;
// Set the item widths and left positions
for(this.leftPadding=this.getItemWidth(this.refreshCols._.totalEmptyCols)/2,"fixed"==this.settings.mode&&(this.leftPadding+=(this.$container.width()-this.settings.minColWidth*this.totalCols)/2),this.refreshCols._.i=0;this.refreshCols._.i<this.items.length;this.refreshCols._.i++)this.refreshCols._.css={width:this.getItemWidth(this.layout.colspans[this.refreshCols._.i])+this.sizeUnit},this.refreshCols._.css[Craft.left]=this.leftPadding+this.getItemWidth(this.layout.positions[this.refreshCols._.i])+this.sizeUnit,c?this.items[this.refreshCols._.i].velocity(this.refreshCols._.css,{queue:!1}):this.items[this.refreshCols._.i].velocity("finish").css(this.refreshCols._.css);
// If every item is at position 0, then let them lay out au naturel
this.isSimpleLayout()?(this.$container.height("auto"),this.$items.css("position","relative")):(this.$items.css("position","absolute"),
// Now position the items
this.positionItems(c),
// Update the positions as the items' heigthts change
this.addListener(this.$items,"resize","onItemResize"))}this.completeRefreshCols(),
// Resume container resize listening
this.addListener(this.$container,"resize",this.handleContainerHeightProxy),this.onRefreshCols()},completeRefreshCols:function(){if(
// Delete the internal variable object
"undefined"!=typeof this.refreshCols._&&delete this.refreshCols._,this._refreshingCols=!1,this._refreshColsAfterRefresh){var b=this._forceRefreshColsAfterRefresh;this._refreshColsAfterRefresh=!1,this._forceRefreshColsAfterRefresh=!1,Garnish.requestAnimationFrame(a.proxy(function(){this.refreshCols(b)},this))}},getItemWidth:function(a){return"pct"==this.settings.mode?this.colPctWidth*a:this.settings.minColWidth*a},createLayouts:function(a,b,c,d,e){new Craft.Grid.LayoutGenerator(this).createLayouts(a,b,c,d,e)},isSimpleLayout:function(){for(this.isSimpleLayout._={},this.isSimpleLayout._.i=0;this.isSimpleLayout._.i<this.layout.positions.length;this.isSimpleLayout._.i++)if(0!=this.layout.positions[this.isSimpleLayout._.i])return delete this.isSimpleLayout._,!1;return delete this.isSimpleLayout._,!0},positionItems:function(a){for(this.positionItems._={},this.positionItems._.colHeights=[],this.positionItems._.i=0;this.positionItems._.i<this.totalCols;this.positionItems._.i++)this.positionItems._.colHeights.push(0);for(this.positionItems._.i=0;this.positionItems._.i<this.items.length;this.positionItems._.i++){for(this.positionItems._.endingCol=this.layout.positions[this.positionItems._.i]+this.layout.colspans[this.positionItems._.i]-1,this.positionItems._.affectedColHeights=[],this.positionItems._.col=this.layout.positions[this.positionItems._.i];this.positionItems._.col<=this.positionItems._.endingCol;this.positionItems._.col++)this.positionItems._.affectedColHeights.push(this.positionItems._.colHeights[this.positionItems._.col]);
// Now add the new heights to those columns
for(this.positionItems._.top=Math.max.apply(null,this.positionItems._.affectedColHeights),a?this.items[this.positionItems._.i].velocity({top:this.positionItems._.top},{queue:!1}):this.items[this.positionItems._.i].velocity("finish").css("top",this.positionItems._.top),this.positionItems._.col=this.layout.positions[this.positionItems._.i];this.positionItems._.col<=this.positionItems._.endingCol;this.positionItems._.col++)this.positionItems._.colHeights[this.positionItems._.col]=this.positionItems._.top+this.itemHeightsByColspan[this.positionItems._.i][this.layout.colspans[this.positionItems._.i]]}
// Set the container height
this.$container.height(Math.max.apply(null,this.positionItems._.colHeights)),delete this.positionItems._},onItemResize:function(b){this.onItemResize._={},
// Prevent this from bubbling up to the container, which has its own resize listener
b.stopPropagation(),this.onItemResize._.item=a.inArray(b.currentTarget,this.$items),this.onItemResize._.item!=-1&&(
// Update the height and reposition the items
this.onItemResize._.newHeight=this.items[this.onItemResize._.item].outerHeight(),this.onItemResize._.newHeight!=this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]]&&(this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]]=this.onItemResize._.newHeight,this.positionItems(!1))),delete this.onItemResize._},onRefreshCols:function(){this.trigger("refreshCols"),this.settings.onRefreshCols()}},{defaults:{itemSelector:".item",cols:null,maxCols:null,minColWidth:320,mode:"pct",fillMode:"top",colClass:"col",snapToGrid:null,onRefreshCols:a.noop}}),Craft.Grid.LayoutGenerator=Garnish.Base.extend({grid:null,_:null,init:function(a){this.grid=a},createLayouts:function(b,c,d,e,f){
// Loop through all possible colspans
for(this._={},this._.c=0;this._.c<this.grid.possibleItemColspans[b].length;this._.c++){for(this._.colspan=this.grid.possibleItemColspans[b][this._.c],
// Loop through all the possible positions for this colspan,
// and find the one that is closest to the top
this._.tallestColHeightsByPosition=[],this._.p=0;this._.p<this.grid.possibleItemPositionsByColspan[b][this._.colspan].length;this._.p++){for(this._.position=this.grid.possibleItemPositionsByColspan[b][this._.colspan][this._.p],this._.colHeightsForPosition=[],this._.endingCol=this._.position+this._.colspan-1,this._.col=this._.position;this._.col<=this._.endingCol;this._.col++)this._.colHeightsForPosition.push(e[this._.col]);this._.tallestColHeightsByPosition[this._.p]=Math.max.apply(null,this._.colHeightsForPosition)}for(
// And the shortest position for this colspan is...
this._.p=a.inArray(Math.min.apply(null,this._.tallestColHeightsByPosition),this._.tallestColHeightsByPosition),this._.position=this.grid.possibleItemPositionsByColspan[b][this._.colspan][this._.p],
// Now log the colspan/position placement
this._.positions=c.slice(0),this._.colspans=d.slice(0),this._.colHeights=e.slice(0),this._.emptySpace=f,this._.positions.push(this._.position),this._.colspans.push(this._.colspan),
// Add the new heights to those columns
this._.tallestColHeight=this._.tallestColHeightsByPosition[this._.p],this._.endingCol=this._.position+this._.colspan-1,this._.col=this._.position;this._.col<=this._.endingCol;this._.col++)this._.emptySpace+=this._.tallestColHeight-this._.colHeights[this._.col],this._.colHeights[this._.col]=this._.tallestColHeight+this.grid.itemHeightsByColspan[b][this._.colspan];
// If this is the last item, create the layout
b==this.grid.items.length-1?this.grid.layouts.push({positions:this._.positions,colspans:this._.colspans,colHeights:this._.colHeights,emptySpace:this._.emptySpace}):
// Dive deeper
this.grid.createLayouts(b+1,this._.positions,this._.colspans,this._.colHeights,this._.emptySpace)}delete this._}}),/**
 * Handle Generator
 */
Craft.HandleGenerator=Craft.BaseInputGenerator.extend({generateTargetValue:function(a){
// Remove HTML tags
var b=a.replace("/<(.*?)>/g","");
// Remove inner-word punctuation
b=b.replace(/['"‘’“”\[\]\(\)\{\}:]/g,""),
// Make it lowercase
b=b.toLowerCase(),
// Convert extended ASCII characters to basic ASCII
b=Craft.asciiString(b),
// Handle must start with a letter
b=b.replace(/^[^a-z]+/,"");
// Make it camelCase
for(var c=Craft.filterArray(b.split(/[^a-z0-9]+/)),b="",d=0;d<c.length;d++)b+=0==d?c[d]:c[d].charAt(0).toUpperCase()+c[d].substr(1);return b}}),/**
 * Image upload class for user photos, site icon and logo.
 */
Craft.ImageUpload=Garnish.Base.extend({$container:null,progressBar:null,uploader:null,init:function(a){this.setSettings(a,Craft.ImageUpload.defaults),this.initImageUpload()},initImageUpload:function(){this.$container=a(this.settings.containerSelector),this.progressBar=new Craft.ProgressBar(a('<div class="progress-shade"></div>').appendTo(this.$container));var b={url:Craft.getActionUrl(this.settings.uploadAction),formData:this.settings.postParameters,fileInput:this.$container.find(this.settings.fileInputSelector)};
// If CSRF protection isn't enabled, these won't be defined.
"undefined"!=typeof Craft.csrfTokenName&&"undefined"!=typeof Craft.csrfTokenValue&&(
// Add the CSRF token
b.formData[Craft.csrfTokenName]=Craft.csrfTokenValue),b.events={},b.events.fileuploadstart=a.proxy(this,"_onUploadStart"),b.events.fileuploadprogressall=a.proxy(this,"_onUploadProgress"),b.events.fileuploaddone=a.proxy(this,"_onUploadComplete"),this.uploader=new Craft.Uploader(this.$container,b),this.initButtons()},initButtons:function(){this.$container.find(this.settings.uploadButtonSelector).on("click",a.proxy(function(a){this.$container.find(this.settings.fileInputSelector).click()},this)),this.$container.find(this.settings.deleteButtonSelector).on("click",a.proxy(function(b){confirm(Craft.t("app","Are you sure you want to delete this image?"))&&(a(b.currentTarget).parent().append('<div class="blocking-modal"></div>'),Craft.postActionRequest(this.settings.deleteAction,this.settings.postParameters,a.proxy(function(a,b){"success"==b&&this.refreshImage(a)},this)))},this))},refreshImage:function(b){a(this.settings.containerSelector).replaceWith(b.html),this.settings.onAfterRefreshImage(b),this.initImageUpload()},/**
	 * On upload start.
	 */
_onUploadStart:function(a){this.progressBar.$progressBar.css({top:Math.round(this.$container.outerHeight()/2)-6}),this.$container.addClass("uploading"),this.progressBar.resetProgressBar(),this.progressBar.showProgressBar()},/**
	 * On upload progress.
	 */
_onUploadProgress:function(a,b){var c=parseInt(b.loaded/b.total*100,10);this.progressBar.setProgressPercentage(c)},/**
	 * On a file being uploaded.
	 */
_onUploadComplete:function(b,c){if(c.result.error)alert(c.result.error);else{a(c.result.html);this.refreshImage(c.result)}
// Last file
this.uploader.isLastUpload()&&(this.progressBar.hideProgressBar(),this.$container.removeClass("uploading"))}},{defaults:{postParameters:{},uploadAction:"",deleteAction:"",fileInputSelector:"",onAfterRefreshImage:a.noop,containerSelector:null,uploadButtonSelector:null,deleteButtonSelector:null}}),/**
 * Info icon class
 */
Craft.InfoIcon=Garnish.Base.extend({$icon:null,hud:null,init:function(b){this.$icon=a(b),this.addListener(this.$icon,"click","showHud")},showHud:function(){this.hud?this.hud.show():this.hud=new Garnish.HUD(this.$icon,this.$icon.html(),{hudClass:"hud info-hud",closeOtherHUDs:!1})}}),/**
 * Light Switch
 */
Craft.LightSwitch=Garnish.Base.extend({settings:null,$outerContainer:null,$innerContainer:null,$input:null,small:!1,on:null,dragger:null,dragStartMargin:null,init:function(b,c){this.$outerContainer=a(b),
// Is this already a lightswitch?
this.$outerContainer.data("lightswitch")&&(Garnish.log("Double-instantiating a lightswitch on an element"),this.$outerContainer.data("lightswitch").destroy()),this.$outerContainer.data("lightswitch",this),this.small=this.$outerContainer.hasClass("small"),this.setSettings(c,Craft.LightSwitch.defaults),this.$innerContainer=this.$outerContainer.find(".lightswitch-container:first"),this.$input=this.$outerContainer.find("input:first"),
// If the input is disabled, go no further
this.$input.prop("disabled")||(this.on=this.$outerContainer.hasClass("on"),this.$outerContainer.attr({role:"checkbox","aria-checked":this.on?"true":"false"}),this.addListener(this.$outerContainer,"mousedown","_onMouseDown"),this.addListener(this.$outerContainer,"keydown","_onKeyDown"),this.dragger=new Garnish.BaseDrag(this.$outerContainer,{axis:Garnish.X_AXIS,ignoreHandleSelector:null,onDragStart:a.proxy(this,"_onDragStart"),onDrag:a.proxy(this,"_onDrag"),onDragStop:a.proxy(this,"_onDragStop")}))},turnOn:function(){this.$outerContainer.addClass("dragging");var b={};b["margin-"+Craft.left]=0,this.$innerContainer.velocity("stop").velocity(b,Craft.LightSwitch.animationDuration,a.proxy(this,"_onSettle")),this.$input.val(this.settings.value),this.$outerContainer.addClass("on"),this.$outerContainer.attr("aria-checked","true"),this.on=!0,this.onChange()},turnOff:function(){this.$outerContainer.addClass("dragging");var b={};b["margin-"+Craft.left]=this._getOffMargin(),this.$innerContainer.velocity("stop").velocity(b,Craft.LightSwitch.animationDuration,a.proxy(this,"_onSettle")),this.$input.val(""),this.$outerContainer.removeClass("on"),this.$outerContainer.attr("aria-checked","false"),this.on=!1,this.onChange()},toggle:function(a){this.on?this.turnOff():this.turnOn()},onChange:function(){this.trigger("change"),this.settings.onChange(),this.$outerContainer.trigger("change")},_onMouseDown:function(){this.addListener(Garnish.$doc,"mouseup","_onMouseUp")},_onMouseUp:function(){this.removeListener(Garnish.$doc,"mouseup"),
// Was this a click?
this.dragger.dragging||this.toggle()},_onKeyDown:function(a){switch(a.keyCode){case Garnish.SPACE_KEY:this.toggle(),a.preventDefault();break;case Garnish.RIGHT_KEY:"ltr"==Craft.orientation?this.turnOn():this.turnOff(),a.preventDefault();break;case Garnish.LEFT_KEY:"ltr"==Craft.orientation?this.turnOff():this.turnOn(),a.preventDefault()}},_getMargin:function(){return parseInt(this.$innerContainer.css("margin-"+Craft.left))},_onDragStart:function(){this.$outerContainer.addClass("dragging"),this.dragStartMargin=this._getMargin()},_onDrag:function(){var a;a="ltr"==Craft.orientation?this.dragStartMargin+this.dragger.mouseDistX:this.dragStartMargin-this.dragger.mouseDistX,a<this._getOffMargin()?a=this._getOffMargin():a>0&&(a=0),this.$innerContainer.css("margin-"+Craft.left,a)},_onDragStop:function(){var a=this._getMargin();a>this._getOffMargin()/2?this.turnOn():this.turnOff()},_onSettle:function(){this.$outerContainer.removeClass("dragging")},destroy:function(){this.base(),this.dragger.destroy()},_getOffMargin:function(){return this.small?-9:-11}},{animationDuration:100,defaults:{value:"1",onChange:a.noop}}),/**
 * Live Preview
 */
Craft.LivePreview=Garnish.Base.extend({$extraFields:null,$trigger:null,$spinner:null,$shade:null,$editorContainer:null,$editor:null,$dragHandle:null,$iframeContainer:null,$iframe:null,$fieldPlaceholder:null,previewUrl:null,basePostData:null,inPreviewMode:!1,fields:null,lastPostData:null,updateIframeInterval:null,loading:!1,checkAgain:!1,dragger:null,dragStartEditorWidth:null,_handleSuccessProxy:null,_handleErrorProxy:null,_scrollX:null,_scrollY:null,_editorWidth:null,_editorWidthInPx:null,init:function(b){this.setSettings(b,Craft.LivePreview.defaults),
// Should preview requests use a specific URL?
// This won't affect how the request gets routed (the action param will override it),
// but it will allow the templates to change behavior based on the request URI.
this.settings.previewUrl?this.previewUrl=this.settings.previewUrl:this.previewUrl=Craft.baseSiteUrl.replace(/\/+$/,"")+"/",
// Load the preview over SSL if the current request is
"https:"==document.location.protocol&&(this.previewUrl=this.previewUrl.replace(/^http:/,"https:")),
// Set the base post data
this.basePostData=a.extend({action:this.settings.previewAction,livePreview:!0},this.settings.previewParams),Craft.csrfTokenName&&(this.basePostData[Craft.csrfTokenName]=Craft.csrfTokenValue),this._handleSuccessProxy=a.proxy(this,"handleSuccess"),this._handleErrorProxy=a.proxy(this,"handleError"),
// Find the DOM elements
this.$extraFields=a(this.settings.extraFields),this.$trigger=a(this.settings.trigger),this.$spinner=this.settings.spinner?a(this.settings.spinner):this.$trigger.find(".spinner"),this.$fieldPlaceholder=a("<div/>"),
// Set the initial editor width
this.editorWidth=Craft.getLocalStorage("LivePreview.editorWidth",Craft.LivePreview.defaultEditorWidth),
// Event Listeners
this.addListener(this.$trigger,"activate","toggle"),Craft.cp.on("beforeSaveShortcut",a.proxy(function(){this.inPreviewMode&&this.moveFieldsBack()},this))},get editorWidth(){return this._editorWidth},get editorWidthInPx(){return this._editorWidthInPx},set editorWidth(a){var b;
// Is this getting set in pixels?
a>=1?(b=a,a/=Garnish.$win.width()):b=Math.round(a*Garnish.$win.width()),
// Make sure it's no less than the minimum
b<Craft.LivePreview.minEditorWidthInPx&&(b=Craft.LivePreview.minEditorWidthInPx,a=b/Garnish.$win.width()),this._editorWidth=a,this._editorWidthInPx=b},toggle:function(){this.inPreviewMode?this.exit():this.enter()},enter:function(){if(!this.inPreviewMode){if(this.trigger("beforeEnter"),a(document.activeElement).blur(),!this.$editor){this.$shade=a('<div class="modal-shade dark"/>').appendTo(Garnish.$bod).css("z-index",2),this.$editorContainer=a('<div class="lp-editor-container"/>').appendTo(Garnish.$bod),this.$editor=a('<div class="lp-editor"/>').appendTo(this.$editorContainer),this.$iframeContainer=a('<div class="lp-iframe-container"/>').appendTo(Garnish.$bod),this.$iframe=a('<iframe class="lp-iframe" frameborder="0"/>').appendTo(this.$iframeContainer),this.$dragHandle=a('<div class="lp-draghandle"/>').appendTo(this.$editorContainer);var b=a('<header class="header"></header>').appendTo(this.$editor),c=a('<div class="btn">'+Craft.t("app","Close Live Preview")+"</div>").appendTo(b),d=a('<div class="btn submit">'+Craft.t("app","Save")+"</div>").appendTo(b);this.dragger=new Garnish.BaseDrag(this.$dragHandle,{axis:Garnish.X_AXIS,onDragStart:a.proxy(this,"_onDragStart"),onDrag:a.proxy(this,"_onDrag"),onDragStop:a.proxy(this,"_onDragStop")}),this.addListener(c,"click","exit"),this.addListener(d,"click","save")}
// Set the sizes
this.handleWindowResize(),this.addListener(Garnish.$win,"resize","handleWindowResize"),this.$editorContainer.css(Craft.left,-(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth)+"px"),this.$iframeContainer.css(Craft.right,-this.getIframeWidth()),
// Move all the fields into the editor rather than copying them
// so any JS that's referencing the elements won't break.
this.fields=[];for(var e=a(this.settings.fields),f=0;f<e.length;f++){var g=a(e[f]),h=this._getClone(g);
// It's important that the actual field is added to the DOM *after* the clone,
// so any radio buttons in the field get deselected from the clone rather than the actual field.
this.$fieldPlaceholder.insertAfter(g),g.detach(),this.$fieldPlaceholder.replaceWith(h),g.appendTo(this.$editor),this.fields.push({$field:g,$clone:h})}this.updateIframe()?(this.$spinner.removeClass("hidden"),this.addListener(this.$iframe,"load",function(){this.slideIn(),this.removeListener(this.$iframe,"load")})):this.slideIn(),this.inPreviewMode=!0,this.trigger("enter")}},save:function(){Craft.cp.submitPrimaryForm()},handleWindowResize:function(){
// Reset the width so the min width is enforced
this.editorWidth=this.editorWidth,
// Update the editor/iframe sizes
this.updateWidths()},slideIn:function(){a("html").addClass("noscroll"),this.$spinner.addClass("hidden"),this.$shade.velocity("fadeIn"),this.$editorContainer.show().velocity("stop").animateLeft(0,"slow",a.proxy(function(){this.trigger("slideIn"),Garnish.$win.trigger("resize")},this)),this.$iframeContainer.show().velocity("stop").animateRight(0,"slow",a.proxy(function(){this.updateIframeInterval=setInterval(a.proxy(this,"updateIframe"),1e3),this.addListener(Garnish.$bod,"keyup",function(a){a.keyCode==Garnish.ESC_KEY&&this.exit()})},this))},exit:function(){if(this.inPreviewMode){this.trigger("beforeExit"),a("html").removeClass("noscroll"),this.removeListener(Garnish.$win,"resize"),this.removeListener(Garnish.$bod,"keyup"),this.updateIframeInterval&&clearInterval(this.updateIframeInterval),this.moveFieldsBack();Garnish.$win.width();this.$shade.delay(200).velocity("fadeOut"),this.$editorContainer.velocity("stop").animateLeft(-(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth),"slow",a.proxy(function(){for(var a=0;a<this.fields.length;a++)this.fields[a].$newClone.remove();this.$editorContainer.hide(),this.trigger("slideOut")},this)),this.$iframeContainer.velocity("stop").animateRight(-this.getIframeWidth(),"slow",a.proxy(function(){this.$iframeContainer.hide()},this)),this.inPreviewMode=!1,this.trigger("exit")}},moveFieldsBack:function(){for(var a=0;a<this.fields.length;a++){var b=this.fields[a];b.$newClone=this._getClone(b.$field),
// It's important that the actual field is added to the DOM *after* the clone,
// so any radio buttons in the field get deselected from the clone rather than the actual field.
this.$fieldPlaceholder.insertAfter(b.$field),b.$field.detach(),this.$fieldPlaceholder.replaceWith(b.$newClone),b.$clone.replaceWith(b.$field)}Garnish.$win.trigger("resize")},getIframeWidth:function(){return Garnish.$win.width()-(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth)},updateWidths:function(){this.$editorContainer.css("width",this.editorWidthInPx+"px"),this.$iframeContainer.width(this.getIframeWidth())},updateIframe:function(b){if(b&&(this.lastPostData=null),!this.inPreviewMode)return!1;if(this.loading)return this.checkAgain=!0,!1;
// Has the post data changed?
var c=a.extend(Garnish.getPostData(this.$editor),Garnish.getPostData(this.$extraFields));if(this.lastPostData&&Craft.compare(c,this.lastPostData))return!1;this.lastPostData=c,this.loading=!0;var d=a(this.$iframe[0].contentWindow.document);return this._scrollX=d.scrollLeft(),this._scrollY=d.scrollTop(),a.ajax({url:this.previewUrl,method:"POST",data:a.extend({},c,this.basePostData),xhrFields:{withCredentials:!0},crossDomain:!0,success:this._handleSuccessProxy,error:this._handleErrorProxy}),!0},handleSuccess:function(b,c,d){var e=b+'<script type="text/javascript">window.scrollTo('+this._scrollX+", "+this._scrollY+");</script>";
// Set the iframe to use the same bg as the iframe body,
// to reduce the blink when reloading the DOM
this.$iframe.css("background",a(this.$iframe[0].contentWindow.document.body).css("background")),this.$iframe[0].contentWindow.document.open(),this.$iframe[0].contentWindow.document.write(e),this.$iframe[0].contentWindow.document.close(),this.onResponse()},handleError:function(a,b,c){this.onResponse()},onResponse:function(){this.loading=!1,this.checkAgain&&(this.checkAgain=!1,this.updateIframe())},_getClone:function(a){var b=a.clone();
// clone() won't account for input values that have changed since the original HTML set them
// Remove any id= attributes
return Garnish.copyInputValues(a,b),b.attr("id",""),b.find("[id]").attr("id",""),b},_onDragStart:function(){this.dragStartEditorWidth=this.editorWidthInPx,this.$iframeContainer.addClass("dragging")},_onDrag:function(){"ltr"==Craft.orientation?this.editorWidth=this.dragStartEditorWidth+this.dragger.mouseDistX:this.editorWidth=this.dragStartEditorWidth-this.dragger.mouseDistX,this.updateWidths()},_onDragStop:function(){this.$iframeContainer.removeClass("dragging"),Craft.setLocalStorage("LivePreview.editorWidth",this.editorWidth)}},{defaultEditorWidth:.33,minEditorWidthInPx:320,dragHandleWidth:4,defaults:{trigger:".livepreviewbtn",spinner:null,fields:null,extraFields:null,previewUrl:null,previewAction:null,previewParams:{}}}),Craft.LivePreview.init=function(a){Craft.livePreview=new Craft.LivePreview(a)},/**
 * Pane class
 */
Craft.Pane=Garnish.Base.extend({$pane:null,$content:null,$sidebar:null,$tabsContainer:null,tabs:null,selectedTab:null,hasSidebar:null,init:function(b){this.$pane=a(b),
// Is this already a pane?
this.$pane.data("pane")&&(Garnish.log("Double-instantiating a pane on an element"),this.$pane.data("pane").destroy()),this.$pane.data("pane",this),this.$content=this.$pane.find(".content:not(.hidden):first"),
// Initialize the tabs
this.$tabsContainer=this.$pane.children(".tabs");var c=this.$tabsContainer.find("a");if(c.length){this.tabs={};
// Find the tabs that link to a div on the page
for(var d=0;d<c.length;d++){var e=a(c[d]),f=e.attr("href");f&&"#"==f.charAt(0)&&(this.tabs[f]={$tab:e,$target:a(f)},this.addListener(e,"activate","selectTab")),!this.selectedTab&&e.hasClass("sel")&&(this.selectedTab=f)}document.location.hash&&"undefined"!=typeof this.tabs[document.location.hash]?this.tabs[document.location.hash].$tab.trigger("activate"):this.selectedTab||a(c[0]).trigger("activate")}if(this.$pane.hasClass("meta")){var g=Garnish.findInputs(this.$pane);this.addListener(g,"focus","focusMetaField"),this.addListener(g,"blur","blurMetaField")}this.initContent()},focusMetaField:function(b){a(b.currentTarget).closest(".field").removeClass("has-errors").addClass("has-focus")},blurMetaField:function(b){a(b.currentTarget).closest(".field").removeClass("has-focus")},/**
	 * Selects a tab.
	 */
selectTab:function(b){if(!this.selectedTab||b.currentTarget!=this.tabs[this.selectedTab].$tab[0]){
// Hide the selected tab
this.deselectTab();var c=a(b.currentTarget).addClass("sel");this.selectedTab=c.attr("href");var d=this.tabs[this.selectedTab].$target;d.removeClass("hidden"),d.hasClass("content")&&(this.$content=d),Garnish.$win.trigger("resize"),
// Fixes Redactor fixed toolbars on previously hidden panes
Garnish.$doc.trigger("scroll")}},/**
	 * Deselects the current tab.
	 */
deselectTab:function(){this.selectedTab&&(this.tabs[this.selectedTab].$tab.removeClass("sel"),this.tabs[this.selectedTab].$target.addClass("hidden"))},initContent:function(){this.hasSidebar=this.$content.hasClass("has-sidebar"),this.hasSidebar&&(this.$sidebar=this.$content.children(".sidebar"),this.addListener(this.$content,"resize",function(){this.updateSidebarStyles()}),this.addListener(this.$sidebar,"resize","setMinContentSizeForSidebar"),this.setMinContentSizeForSidebar(),this.addListener(Garnish.$win,"resize","updateSidebarStyles"),this.addListener(Garnish.$win,"scroll","updateSidebarStyles"),this.updateSidebarStyles())},setMinContentSizeForSidebar:function(){this.setMinContentSizeForSidebar._minHeight=this.$sidebar.prop("scrollHeight")-this.$tabsContainer.height()-48,this.$content.css("min-height",this.setMinContentSizeForSidebar._minHeight)},updateSidebarStyles:function(){this.updateSidebarStyles._styles={},this.updateSidebarStyles._scrollTop=Garnish.$win.scrollTop(),this.updateSidebarStyles._paneOffset=this.$pane.offset().top+this.$tabsContainer.height(),this.updateSidebarStyles._paneHeight=this.$pane.outerHeight()-this.$tabsContainer.height(),this.updateSidebarStyles._windowHeight=Garnish.$win.height(),
// Have we scrolled passed the top of the pane?
Garnish.$win.width()>992&&this.updateSidebarStyles._scrollTop>this.updateSidebarStyles._paneOffset?(
// Set the top position to the difference
this.updateSidebarStyles._styles.position="fixed",this.updateSidebarStyles._styles.top="24px"):(this.updateSidebarStyles._styles.position="absolute",this.updateSidebarStyles._styles.top="auto"),
// Now figure out how tall the sidebar can be
this.updateSidebarStyles._styles.maxHeight=Math.min(this.updateSidebarStyles._paneHeight-(this.updateSidebarStyles._scrollTop-this.updateSidebarStyles._paneOffset),this.updateSidebarStyles._windowHeight),this.updateSidebarStyles._paneHeight>this.updateSidebarStyles._windowHeight?this.updateSidebarStyles._styles.height=this.updateSidebarStyles._styles.maxHeight:this.updateSidebarStyles._styles.height=this.updateSidebarStyles._paneHeight,this.$sidebar.css(this.updateSidebarStyles._styles)},destroy:function(){this.base(),this.$pane.data("pane",null)}}),/**
 * Password Input
 */
Craft.PasswordInput=Garnish.Base.extend({$passwordInput:null,$textInput:null,$currentInput:null,$showPasswordToggle:null,showingPassword:null,init:function(b,c){this.$passwordInput=a(b),this.settings=a.extend({},Craft.PasswordInput.defaults,c),
// Is this already a password input?
this.$passwordInput.data("passwordInput")&&(Garnish.log("Double-instantiating a password input on an element"),this.$passwordInput.data("passwordInput").destroy()),this.$passwordInput.data("passwordInput",this),this.$showPasswordToggle=a("<a/>").hide(),this.$showPasswordToggle.addClass("password-toggle"),this.$showPasswordToggle.insertAfter(this.$passwordInput),this.addListener(this.$showPasswordToggle,"mousedown","onToggleMouseDown"),this.hidePassword()},setCurrentInput:function(a){this.$currentInput&&(
// Swap the inputs, while preventing the focus animation
a.addClass("focus"),this.$currentInput.replaceWith(a),a.focus(),a.removeClass("focus"),
// Restore the input value
a.val(this.$currentInput.val())),this.$currentInput=a,this.addListener(this.$currentInput,"keypress,keyup,change,blur","onInputChange")},updateToggleLabel:function(a){this.$showPasswordToggle.text(a)},showPassword:function(){this.showingPassword||(this.$textInput||(this.$textInput=this.$passwordInput.clone(!0),this.$textInput.attr("type","text")),this.setCurrentInput(this.$textInput),this.updateToggleLabel(Craft.t("app","Hide")),this.showingPassword=!0)},hidePassword:function(){
// showingPassword could be null, which is acceptable
this.showingPassword!==!1&&(this.setCurrentInput(this.$passwordInput),this.updateToggleLabel(Craft.t("app","Show")),this.showingPassword=!1,
// Alt key temporarily shows the password
this.addListener(this.$passwordInput,"keydown","onKeyDown"))},togglePassword:function(){this.showingPassword?this.hidePassword():this.showPassword(),this.settings.onToggleInput(this.$currentInput)},onKeyDown:function(a){a.keyCode==Garnish.ALT_KEY&&this.$currentInput.val()&&(this.showPassword(),this.$showPasswordToggle.hide(),this.addListener(this.$textInput,"keyup","onKeyUp"))},onKeyUp:function(a){a.preventDefault(),a.keyCode==Garnish.ALT_KEY&&(this.hidePassword(),this.$showPasswordToggle.show())},onInputChange:function(){this.$currentInput.val()?this.$showPasswordToggle.show():this.$showPasswordToggle.hide()},onToggleMouseDown:function(a){if(
// Prevent focus change
a.preventDefault(),this.$currentInput[0].setSelectionRange){var b=this.$currentInput[0].selectionStart,c=this.$currentInput[0].selectionEnd;this.togglePassword(),this.$currentInput[0].setSelectionRange(b,c)}else this.togglePassword()}},{defaults:{onToggleInput:a.noop}}),/**
 * File Manager.
 */
Craft.ProgressBar=Garnish.Base.extend({$progressBar:null,$innerProgressBar:null,_itemCount:0,_processedItemCount:0,init:function(b){this.$progressBar=a('<div class="progressbar pending hidden"/>').appendTo(b),this.$innerProgressBar=a('<div class="progressbar-inner"/>').appendTo(this.$progressBar),this.resetProgressBar()},/**
     * Reset the progress bar
     */
resetProgressBar:function(){
// Since setting the progress percentage implies that there is progress to be shown
// It removes the pending class - we must add it back.
this.setProgressPercentage(100),this.$progressBar.addClass("pending"),
// Reset all the counters
this.setItemCount(1),this.setProcessedItemCount(0)},/**
     * Fade to invisible, hide it using a class and reset opacity to visible
     */
hideProgressBar:function(){this.$progressBar.fadeTo("fast",.01,a.proxy(function(){this.$progressBar.addClass("hidden").fadeTo(1,1,a.noop)},this))},showProgressBar:function(){this.$progressBar.removeClass("hidden")},setItemCount:function(a){this._itemCount=a},incrementItemCount:function(a){this._itemCount+=a},setProcessedItemCount:function(a){this._processedItemCount=a},incrementProcessedItemCount:function(a){this._processedItemCount+=a},updateProgressBar:function(){
// Only fools would allow accidental division by zero.
this._itemCount=Math.max(this._itemCount,1);var a=Math.min(100,Math.round(100*this._processedItemCount/this._itemCount));this.setProgressPercentage(a)},setProgressPercentage:function(a,b){0==a?this.$progressBar.addClass("pending"):(this.$progressBar.removeClass("pending"),b?this.$innerProgressBar.velocity("stop").velocity({width:a+"%"},"fast"):this.$innerProgressBar.velocity("stop").width(a+"%"))}}),/**
 * File Manager.
 */
Craft.PromptHandler=Garnish.Base.extend({$modalContainerDiv:null,$prompt:null,$promptApplyToRemainingContainer:null,$promptApplyToRemainingCheckbox:null,$promptApplyToRemainingLabel:null,$pomptChoices:null,_prompts:[],_promptBatchCallback:a.noop,_promptBatchReturnData:[],_promptBatchNum:0,init:function(){},resetPrompts:function(){this._prompts=[],this._promptBatchCallback=a.noop,this._promptBatchReturnData=[],this._promptBatchNum=0},addPrompt:function(a){this._prompts.push(a)},getPromptCount:function(){return this._prompts.length},showBatchPrompts:function(a){this._promptBatchCallback=a,this._promptBatchReturnData=[],this._promptBatchNum=0,this._showNextPromptInBatch()},_showNextPromptInBatch:function(){var b=this._prompts[this._promptBatchNum].prompt,c=this._prompts.length-(this._promptBatchNum+1);this._showPrompt(b.message,b.choices,a.proxy(this,"_handleBatchPromptSelection"),c)},/**
     * Handles a prompt choice selection.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
_handleBatchPromptSelection:function(b,c){var d=this._prompts[this._promptBatchNum],e=this._prompts.length-(this._promptBatchNum+1),f=a.extend(d,{choice:b});this._promptBatchReturnData.push(f),
// Are there any remaining items in the batch?
e?(
// Get ready to deal with the next prompt
this._promptBatchNum++,
// Apply the same choice to the remaining items?
c?this._handleBatchPromptSelection(b,!0):
// Show the next prompt
this._showNextPromptInBatch()):
// All done! Call the callback
"function"==typeof this._promptBatchCallback&&this._promptBatchCallback(this._promptBatchReturnData)},/**
     * Show the user prompt with a given message and choices, plus an optional "Apply to remaining" checkbox.
     *
     * @param {string} message
     * @param {object} choices
     * @param {function} callback
     * @param {number} itemsToGo
     */
_showPrompt:function(b,c,d,e){this._promptCallback=d,null==this.modal&&(this.modal=new Garnish.Modal({closeOtherModals:!1})),null==this.$modalContainerDiv&&(this.$modalContainerDiv=a('<div class="modal fitted prompt-modal"></div>').addClass().appendTo(Garnish.$bod)),this.$prompt=a('<div class="body"></div>').appendTo(this.$modalContainerDiv.empty()),this.$promptMessage=a('<p class="prompt-msg"/>').appendTo(this.$prompt),this.$promptChoices=a('<div class="options"></div>').appendTo(this.$prompt),this.$promptApplyToRemainingContainer=a('<label class="assets-applytoremaining"/>').appendTo(this.$prompt).hide(),this.$promptApplyToRemainingCheckbox=a('<input type="checkbox"/>').appendTo(this.$promptApplyToRemainingContainer),this.$promptApplyToRemainingLabel=a("<span/>").appendTo(this.$promptApplyToRemainingContainer),this.$promptButtons=a('<div class="buttons right"/>').appendTo(this.$prompt),this.modal.setContainer(this.$modalContainerDiv),this.$promptMessage.html(b);for(var f=a('<div class="btn">'+Craft.t("app","Cancel")+"</div>").appendTo(this.$promptButtons),g=a('<input type="submit" class="btn submit disabled" value="'+Craft.t("app","OK")+'" />').appendTo(this.$promptButtons),h=0;h<c.length;h++){var i=a('<div><label><input type="radio" name="promptAction" value="'+c[h].value+'"/> '+c[h].title+"</label></div>").appendTo(this.$promptChoices),j=i.find("input");this.addListener(j,"click",function(a){g.removeClass("disabled")})}this.addListener(g,"activate",function(b){var c=a(b.currentTarget).parents(".modal").find("input[name=promptAction]:checked").val(),d=this.$promptApplyToRemainingCheckbox.prop("checked");this._selectPromptChoice(c,d)}),this.addListener(f,"activate",function(a){var b="cancel",c=this.$promptApplyToRemainingCheckbox.prop("checked");this._selectPromptChoice(b,c)}),e&&(this.$promptApplyToRemainingContainer.show(),this.$promptApplyToRemainingLabel.html(" "+Craft.t("app","Apply this to the {number} remaining conflicts?",{number:e}))),this.modal.show(),this.modal.removeListener(Garnish.Modal.$shade,"click"),this.addListener(Garnish.Modal.$shade,"click","_cancelPrompt")},/**
     * Handles when a user selects one of the prompt choices.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
_selectPromptChoice:function(b,c){this.$prompt.fadeOut("fast",a.proxy(function(){this.modal.hide(),this._promptCallback(b,c)},this))},/**
     * Cancels the prompt.
     */
_cancelPrompt:function(){this._selectPromptChoice("cancel",!0)}}),/**
 * Slug Generator
 */
Craft.SlugGenerator=Craft.BaseInputGenerator.extend({generateTargetValue:function(a){
// Remove HTML tags
a=a.replace(/<(.*?)>/g,""),
// Remove inner-word punctuation
a=a.replace(/['"‘’“”\[\]\(\)\{\}:]/g,""),
// Make it lowercase
a=a.toLowerCase(),Craft.limitAutoSlugsToAscii&&(
// Convert extended ASCII characters to basic ASCII
a=Craft.asciiString(a));
// Get the "words". Split on anything that is not alphanumeric.
// Reference: http://www.regular-expressions.info/unicode.html
var b=Craft.filterArray(XRegExp.matchChain(a,[XRegExp("[\\p{L}\\p{N}\\p{M}]+")]));return b.length?b.join(Craft.slugWordSeparator):""}}),/**
 * Structure class
 */
Craft.Structure=Garnish.Base.extend({id:null,$container:null,state:null,structureDrag:null,/**
	 * Init
	 */
init:function(b,c,d){this.id=b,this.$container=a(c),this.setSettings(d,Craft.Structure.defaults),
// Is this already a structure?
this.$container.data("structure")&&(Garnish.log("Double-instantiating a structure on an element"),this.$container.data("structure").destroy()),this.$container.data("structure",this),this.state={},this.settings.storageKey&&a.extend(this.state,Craft.getLocalStorage(this.settings.storageKey,{})),"undefined"==typeof this.state.collapsedElementIds&&(this.state.collapsedElementIds=[]);for(var e=this.$container.find("ul").prev(".row"),f=0;f<e.length;f++){var g=a(e[f]),h=g.parent(),i=a('<div class="toggle" title="'+Craft.t("app","Show/hide children")+'"/>').prependTo(g);a.inArray(g.children(".element").data("id"),this.state.collapsedElementIds)!=-1&&h.addClass("collapsed"),this.initToggle(i)}this.settings.sortable&&(this.structureDrag=new Craft.StructureDrag(this,this.settings.maxLevels)),this.settings.newChildUrl&&this.initNewChildMenus(this.$container.find(".add"))},initToggle:function(b){b.click(a.proxy(function(b){var c=a(b.currentTarget).closest("li"),d=c.children(".row").find(".element:first").data("id"),e=a.inArray(d,this.state.collapsedElementIds);c.hasClass("collapsed")?(c.removeClass("collapsed"),e!=-1&&this.state.collapsedElementIds.splice(e,1)):(c.addClass("collapsed"),e==-1&&this.state.collapsedElementIds.push(d)),this.settings.storageKey&&Craft.setLocalStorage(this.settings.storageKey,this.state)},this))},initNewChildMenus:function(a){this.addListener(a,"click","onNewChildMenuClick")},onNewChildMenuClick:function(b){var c=a(b.currentTarget);if(!c.data("menubtn")){var d=c.parent().children(".element").data("id"),e=Craft.getUrl(this.settings.newChildUrl,"parentId="+d),f=(a('<div class="menu"><ul><li><a href="'+e+'">'+Craft.t("app","New child")+"</a></li></ul></div>").insertAfter(c),new Garnish.MenuBtn(c));f.showMenu()}},getIndent:function(a){return Craft.Structure.baseIndent+(a-1)*Craft.Structure.nestedIndent},addElement:function(b){var c=a('<li data-level="1"/>').appendTo(this.$container),d=a('<div class="row" style="margin-'+Craft.left+": -"+Craft.Structure.baseIndent+"px; padding-"+Craft.left+": "+Craft.Structure.baseIndent+'px;">').appendTo(c);if(d.append(b),this.settings.sortable&&(d.append('<a class="move icon" title="'+Craft.t("app","Move")+'"></a>'),this.structureDrag.addItems(c)),this.settings.newChildUrl){var e=a('<a class="add icon" title="'+Craft.t("app","New child")+'"></a>').appendTo(d);this.initNewChildMenus(e)}d.css("margin-bottom",-30),d.velocity({"margin-bottom":0},"fast")},removeElement:function(b){var c=b.parent().parent();if(this.settings.sortable&&this.structureDrag.removeItems(c),!c.siblings().length)var d=c.parent();c.css("visibility","hidden").velocity({marginBottom:-c.height()},"fast",a.proxy(function(){c.remove(),"undefined"!=typeof d&&this._removeUl(d)},this))},_removeUl:function(a){a.siblings(".row").children(".toggle").remove(),a.remove()}},{baseIndent:8,nestedIndent:35,defaults:{storageKey:null,sortable:!1,newChildUrl:null,maxLevels:null}}),/**
 * Structure drag class
 */
Craft.StructureDrag=Garnish.Drag.extend({structure:null,maxLevels:null,draggeeLevel:null,$helperLi:null,$targets:null,draggeeHeight:null,init:function(b,c){this.structure=b,this.maxLevels=c,this.$insertion=a('<li class="draginsertion"/>');var d=this.structure.$container.find("li");this.base(d,{handle:".element:first, .move:first",helper:a.proxy(this,"getHelper")})},getHelper:function(b){this.$helperLi=b;var c=a('<ul class="structure draghelper"/>').append(b);return b.css("padding-"+Craft.left,this.$draggee.css("padding-"+Craft.left)),b.find(".move").removeAttr("title"),c},onDragStart:function(){this.$targets=a(),
// Recursively find each of the targets, in the order they appear to be in
this.findTargets(this.structure.$container),
// How deep does the rabbit hole go?
this.draggeeLevel=0;var b=this.$draggee;do this.draggeeLevel++,b=b.find("> ul > li");while(b.length);
// Collapse the draggee
this.draggeeHeight=this.$draggee.height(),this.$draggee.velocity({height:0},"fast",a.proxy(function(){this.$draggee.addClass("hidden")},this)),this.base(),this.addListener(Garnish.$doc,"keydown",function(a){a.keyCode==Garnish.ESC_KEY&&this.cancelDrag()})},findTargets:function(b){for(var c=b.children().not(this.$draggee),d=0;d<c.length;d++){var e=a(c[d]);this.$targets=this.$targets.add(e.children(".row")),e.hasClass("collapsed")||this.findTargets(e.children("ul"))}},onDrag:function(){for(this._.$closestTarget&&(this._.$closestTarget.removeClass("draghover"),this.$insertion.remove()),
// First let's find the closest target
this._.$closestTarget=null,this._.closestTargetPos=null,this._.closestTargetYDiff=null,this._.closestTargetOffset=null,this._.closestTargetHeight=null,this._.i=0;this._.i<this.$targets.length&&(this._.$target=a(this.$targets[this._.i]),this._.targetOffset=this._.$target.offset(),this._.targetHeight=this._.$target.outerHeight(),this._.targetYMidpoint=this._.targetOffset.top+this._.targetHeight/2,this._.targetYDiff=Math.abs(this.mouseY-this._.targetYMidpoint),0==this._.i||this.mouseY>=this._.targetOffset.top+5&&this._.targetYDiff<this._.closestTargetYDiff);this._.i++)this._.$closestTarget=this._.$target,this._.closestTargetPos=this._.i,this._.closestTargetYDiff=this._.targetYDiff,this._.closestTargetOffset=this._.targetOffset,this._.closestTargetHeight=this._.targetHeight;if(this._.$closestTarget)
// Are we hovering above the first row?
if(0==this._.closestTargetPos&&this.mouseY<this._.closestTargetOffset.top+5)this.$insertion.prependTo(this.structure.$container);else/**
			 * Scenario 1: Both rows have the same level.
			 *
			 *     * Row 1
			 *     ----------------------
			 *     * Row 2
			 */
if(this._.$closestTargetLi=this._.$closestTarget.parent(),this._.closestTargetLevel=this._.$closestTargetLi.data("level"),
// Is there a next row?
this._.closestTargetPos<this.$targets.length-1?(this._.$nextTargetLi=a(this.$targets[this._.closestTargetPos+1]).parent(),this._.nextTargetLevel=this._.$nextTargetLi.data("level")):(this._.$nextTargetLi=null,this._.nextTargetLevel=null),
// Are we hovering between this row and the next one?
this._.hoveringBetweenRows=this.mouseY>=this._.closestTargetOffset.top+this._.closestTargetHeight-5,this._.$nextTargetLi&&this._.nextTargetLevel==this._.closestTargetLevel)this._.hoveringBetweenRows?(!this.maxLevels||this.maxLevels>=this._.closestTargetLevel+this.draggeeLevel-1)&&
// Position the insertion after the closest target
this.$insertion.insertAfter(this._.$closestTargetLi):(!this.maxLevels||this.maxLevels>=this._.closestTargetLevel+this.draggeeLevel)&&this._.$closestTarget.addClass("draghover");else if(this._.$nextTargetLi&&this._.nextTargetLevel>this._.closestTargetLevel)(!this.maxLevels||this.maxLevels>=this._.nextTargetLevel+this.draggeeLevel-1)&&(this._.hoveringBetweenRows?
// Position the insertion as the first child of the closest target
this.$insertion.insertBefore(this._.$nextTargetLi):(this._.$closestTarget.addClass("draghover"),this.$insertion.appendTo(this._.$closestTargetLi.children("ul"))));else if(this._.hoveringBetweenRows){for(
// Determine which <li> to position the insertion after
this._.draggeeX=this.mouseX-this.targetItemMouseDiffX,"rtl"==Craft.orientation&&(this._.draggeeX+=this.$helperLi.width()),this._.$parentLis=this._.$closestTarget.parentsUntil(this.structure.$container,"li"),this._.$closestParentLi=null,this._.closestParentLiXDiff=null,this._.closestParentLevel=null,this._.i=0;this._.i<this._.$parentLis.length;this._.i++)this._.$parentLi=a(this._.$parentLis[this._.i]),this._.parentLiX=this._.$parentLi.offset().left,"rtl"==Craft.orientation&&(this._.parentLiX+=this._.$parentLi.width()),this._.parentLiXDiff=Math.abs(this._.parentLiX-this._.draggeeX),this._.parentLevel=this._.$parentLi.data("level"),(!this.maxLevels||this.maxLevels>=this._.parentLevel+this.draggeeLevel-1)&&(!this._.$closestParentLi||this._.parentLiXDiff<this._.closestParentLiXDiff&&(!this._.$nextTargetLi||this._.parentLevel>=this._.nextTargetLevel))&&(this._.$closestParentLi=this._.$parentLi,this._.closestParentLiXDiff=this._.parentLiXDiff,this._.closestParentLevel=this._.parentLevel);this._.$closestParentLi&&this.$insertion.insertAfter(this._.$closestParentLi)}else(!this.maxLevels||this.maxLevels>=this._.closestTargetLevel+this.draggeeLevel)&&this._.$closestTarget.addClass("draghover")},cancelDrag:function(){this.$insertion.remove(),this._.$closestTarget&&this._.$closestTarget.removeClass("draghover"),this.onMouseUp()},onDragStop:function(){
// Are we repositioning the draggee?
if(this._.$closestTarget&&(this.$insertion.parent().length||this._.$closestTarget.hasClass("draghover"))){var b,c;if(
// Are we about to leave the draggee's original parent childless?
this.$draggee.siblings().length||(b=this.$draggee.parent()),this.$insertion.parent().length){
// Make sure the insertion isn't right next to the draggee
var d=this.$insertion.next().add(this.$insertion.prev());a.inArray(this.$draggee[0],d)==-1?(this.$insertion.replaceWith(this.$draggee),c=!0):(this.$insertion.remove(),c=!1)}else{var e=this._.$closestTargetLi.children("ul");
// Make sure this is a different parent than the draggee's
if(b&&e.length&&e[0]==b[0])c=!1;else{if(e.length)this._.$closestTargetLi.hasClass("collapsed")&&this._.$closestTarget.children(".toggle").trigger("click");else{var f=a('<div class="toggle" title="'+Craft.t("app","Show/hide children")+'"/>').prependTo(this._.$closestTarget);this.structure.initToggle(f),e=a("<ul>").appendTo(this._.$closestTargetLi)}this.$draggee.appendTo(e),c=!0}}if(
// Remove the class either way
this._.$closestTarget.removeClass("draghover"),c){
// Now deal with the now-childless parent
b&&this.structure._removeUl(b);
// Has the level changed?
var g=this.$draggee.parentsUntil(this.structure.$container,"li").length+1;if(g!=this.$draggee.data("level")){
// Correct the helper's padding if moving to/from level 1
if(1==this.$draggee.data("level")){var h={};h["padding-"+Craft.left]=38,this.$helperLi.velocity(h,"fast")}else if(1==g){var h={};h["padding-"+Craft.left]=Craft.Structure.baseIndent,this.$helperLi.velocity(h,"fast")}this.setLevel(this.$draggee,g)}
// Make it real
var i=this.$draggee.children(".row").children(".element"),j={structureId:this.structure.id,elementId:i.data("id"),siteId:i.data("site-id"),prevId:this.$draggee.prev().children(".row").children(".element").data("id"),parentId:this.$draggee.parent("ul").parent("li").children(".row").children(".element").data("id")};Craft.postActionRequest("structures/move-element",j,function(a,b){"success"==b&&Craft.cp.displayNotice(Craft.t("app","New order saved."))})}}
// Animate things back into place
this.$draggee.velocity("stop").removeClass("hidden").velocity({height:this.draggeeHeight},"fast",a.proxy(function(){this.$draggee.css("height","auto")},this)),this.returnHelpersToDraggees(),this.base()},setLevel:function(b,c){b.data("level",c);var d=this.structure.getIndent(c),e={};e["margin-"+Craft.left]="-"+d+"px",e["padding-"+Craft.left]=d+"px",this.$draggee.children(".row").css(e);for(var f=b.children("ul").children(),g=0;g<f.length;g++)this.setLevel(a(f[g]),c+1)}}),Craft.StructureTableSorter=Garnish.DragSort.extend({
// Properties
// =========================================================================
tableView:null,structureId:null,maxLevels:null,_helperMargin:null,_$firstRowCells:null,_$titleHelperCell:null,_titleHelperCellOuterWidth:null,_ancestors:null,_updateAncestorsFrame:null,_updateAncestorsProxy:null,_draggeeLevel:null,_draggeeLevelDelta:null,draggingLastElements:null,_loadingDraggeeLevelDelta:!1,_targetLevel:null,_targetLevelBounds:null,_positionChanged:null,
// Public methods
// =========================================================================
/**
	 * Constructor
	 */
init:function(b,c,d){this.tableView=b,this.structureId=this.tableView.$table.data("structure-id"),this.maxLevels=parseInt(this.tableView.$table.attr("data-max-levels")),d=a.extend({},Craft.StructureTableSorter.defaults,d,{handle:".move",collapseDraggees:!0,singleHelper:!0,helperSpacingY:2,magnetStrength:4,helper:a.proxy(this,"getHelper"),helperLagBase:1.5,axis:Garnish.Y_AXIS}),this.base(c,d)},/**
	 * Start Dragging
	 */
startDragging:function(){this._helperMargin=Craft.StructureTableSorter.HELPER_MARGIN+(this.tableView.elementIndex.actions?24:0),this.base()},/**
	 * Returns the draggee rows (including any descendent rows).
	 */
findDraggee:function(){this._draggeeLevel=this._targetLevel=this.$targetItem.data("level"),this._draggeeLevelDelta=0;for(var b=a(this.$targetItem),c=this.$targetItem.next();c.length;){
// See if this row is a descendant of the draggee
var d=c.data("level");if(d<=this._draggeeLevel)break;
// Is this the deepest descendant we've seen so far?
var e=d-this._draggeeLevel;e>this._draggeeLevelDelta&&(this._draggeeLevelDelta=e),
// Add it and prep the next row
b=b.add(c),c=c.next()}
// Do we have a maxLevels to enforce,
// and does it look like this draggee has descendants we don't know about yet?
if(
// Are we dragging the last elements on the page?
this.draggingLastElements=!c.length,this.maxLevels&&this.draggingLastElements&&this.tableView.getMorePending()){
// Only way to know the true descendant level delta is to ask PHP
this._loadingDraggeeLevelDelta=!0;var f=this._getAjaxBaseData(this.$targetItem);Craft.postActionRequest("structures/get-element-level-delta",f,a.proxy(function(a,b){"success"==b&&(this._loadingDraggeeLevelDelta=!1,this.dragging&&(this._draggeeLevelDelta=a.delta,this.drag(!1)))},this))}return b},/**
	 * Returns the drag helper.
	 */
getHelper:function(b){var c=a('<div class="elements datatablesorthelper"/>').appendTo(Garnish.$bod),d=a('<div class="tableview"/>').appendTo(c),e=a('<table class="data"/>').appendTo(d),f=a("<tbody/>").appendTo(e);b.appendTo(f),
// Copy the column widths
this._$firstRowCells=this.tableView.$elementContainer.children("tr:first").children();for(var g=b.children(),h=0;h<g.length;h++){var i=a(g[h]);
// Skip the checkbox cell
if(i.hasClass("checkbox-cell"))i.remove();else{
// Hard-set the cell widths
var j=a(this._$firstRowCells[h]),k=j.width();
// Is this the title cell?
if(j.width(k),i.width(k),Garnish.hasAttr(j,"data-titlecell")){this._$titleHelperCell=i;var l=parseInt(j.css("padding-"+Craft.left));this._titleHelperCellOuterWidth=k+l-(this.tableView.elementIndex.actions?12:0),i.css("padding-"+Craft.left,Craft.StructureTableSorter.BASE_PADDING)}}}return c},/**
	 * Returns whether the draggee can be inserted before a given item.
	 */
canInsertBefore:function(a){return!this._loadingDraggeeLevelDelta&&this._getLevelBounds(a.prev(),a)!==!1},/**
	 * Returns whether the draggee can be inserted after a given item.
	 */
canInsertAfter:function(a){return!this._loadingDraggeeLevelDelta&&this._getLevelBounds(a,a.next())!==!1},
// Events
// -------------------------------------------------------------------------
/**
	 * On Drag Start
	 */
onDragStart:function(){
// Get the initial set of ancestors, before the item gets moved
this._ancestors=this._getAncestors(this.$targetItem,this.$targetItem.data("level")),
// Set the initial target level bounds
this._setTargetLevelBounds(),
// Check to see if we should load more elements now
this.tableView.maybeLoadMore(),this.base()},/**
	 * On Drag
	 */
onDrag:function(){this.base(),this._updateIndent()},/**
	 * On Insertion Point Change
	 */
onInsertionPointChange:function(){this._setTargetLevelBounds(),this._updateAncestorsBeforeRepaint(),this.base()},/**
	 * On Drag Stop
	 */
onDragStop:function(){
// Update the draggee's padding if the position just changed
// ---------------------------------------------------------------------
if(this._positionChanged=!1,this.base(),this._targetLevel!=this._draggeeLevel){for(var b=this._targetLevel-this._draggeeLevel,c=0;c<this.$draggee.length;c++){var d=a(this.$draggee[c]),e=d.data("level"),f=e+b,g=Craft.StructureTableSorter.BASE_PADDING+(this.tableView.elementIndex.actions?7:0)+this._getLevelIndent(f);d.data("level",f),d.find(".element").data("level",f),d.children("[data-titlecell]:first").css("padding-"+Craft.left,g)}this._positionChanged=!0}
// Keep in mind this could have also been set by onSortChange()
if(this._positionChanged){for(
// Tell the server about the new position
// -----------------------------------------------------------------
var h=this._getAjaxBaseData(this.$draggee),i=this.$draggee.first().prev();i.length;){var j=i.data("level");if(j==this._targetLevel){h.prevId=i.data("id");break}if(j<this._targetLevel){h.parentId=i.data("id");
// Is this row collapsed?
var k=i.find("> td > .toggle");if(!k.hasClass("expanded")){
// Make it look expanded
k.addClass("expanded");
// Add a temporary row
var l=this.tableView._createSpinnerRowAfter(i);
// Remove the target item
this.tableView.elementSelect&&this.tableView.elementSelect.removeItems(this.$targetItem),this.removeItems(this.$targetItem),this.$targetItem.remove(),this.tableView._totalVisible--}break}i=i.prev()}Craft.postActionRequest("structures/move-element",h,a.proxy(function(a,b){"success"==b&&(Craft.cp.displayNotice(Craft.t("app","New position saved.")),this.onPositionChange(),
// Were we waiting on this to complete so we can expand the new parent?
l&&l.parent().length&&(l.remove(),this.tableView._expandElement(k,!0)),
// See if we should run any pending tasks
Craft.cp.runPendingTasks())},this))}},onSortChange:function(){this.tableView.elementSelect&&this.tableView.elementSelect.resetItemOrder(),this._positionChanged=!0,this.base()},onPositionChange:function(){Garnish.requestAnimationFrame(a.proxy(function(){this.trigger("positionChange"),this.settings.onPositionChange()},this))},onReturnHelpersToDraggees:function(){
// If we were dragging the last elements on the page and ended up loading any additional elements in,
// there could be a gap between the last draggee item and whatever now comes after it.
// So remove the post-draggee elements and possibly load up the next batch.
if(this._$firstRowCells.css("width",""),this.draggingLastElements&&this.tableView.getMorePending()){
// Update the element index's record of how many items are actually visible
this.tableView._totalVisible+=this.newDraggeeIndexes[0]-this.oldDraggeeIndexes[0];var a=this.$draggee.last().nextAll();a.length&&(this.removeItems(a),a.remove(),this.tableView.maybeLoadMore())}this.base()},
// Private methods
// =========================================================================
/**
	 * Returns the min and max levels that the draggee could occupy between
	 * two given rows, or false if it’s not going to work out.
	 */
_getLevelBounds:function(a,b){
// Does this structure have a max level?
if(
// Can't go any lower than the next row, if there is one
b&&b.length?this._getLevelBounds._minLevel=b.data("level"):this._getLevelBounds._minLevel=1,
// Can't go any higher than the previous row + 1
a&&a.length?this._getLevelBounds._maxLevel=a.data("level")+1:this._getLevelBounds._maxLevel=1,this.maxLevels){
// Make sure it's going to fit at all here
if(1!=this._getLevelBounds._minLevel&&this._getLevelBounds._minLevel+this._draggeeLevelDelta>this.maxLevels)return!1;
// Limit the max level if we have to
this._getLevelBounds._maxLevel+this._draggeeLevelDelta>this.maxLevels&&(this._getLevelBounds._maxLevel=this.maxLevels-this._draggeeLevelDelta,this._getLevelBounds._maxLevel<this._getLevelBounds._minLevel&&(this._getLevelBounds._maxLevel=this._getLevelBounds._minLevel))}return{min:this._getLevelBounds._minLevel,max:this._getLevelBounds._maxLevel}},/**
	 * Determines the min and max possible levels at the current draggee's position.
	 */
_setTargetLevelBounds:function(){this._targetLevelBounds=this._getLevelBounds(this.$draggee.first().prev(),this.$draggee.last().next())},/**
	 * Determines the target level based on the current mouse position.
	 */
_updateIndent:function(a){
// Figure out the target level
// ---------------------------------------------------------------------
// How far has the cursor moved?
this._updateIndent._mouseDist=this.realMouseX-this.mousedownX,
// Flip that if this is RTL
"rtl"==Craft.orientation&&(this._updateIndent._mouseDist*=-1),
// What is that in indentation levels?
this._updateIndent._indentationDist=Math.round(this._updateIndent._mouseDist/Craft.StructureTableSorter.LEVEL_INDENT),
// Combine with the original level to get the new target level
this._updateIndent._targetLevel=this._draggeeLevel+this._updateIndent._indentationDist,
// Contain it within our min/max levels
this._updateIndent._targetLevel<this._targetLevelBounds.min?(this._updateIndent._indentationDist+=this._targetLevelBounds.min-this._updateIndent._targetLevel,this._updateIndent._targetLevel=this._targetLevelBounds.min):this._updateIndent._targetLevel>this._targetLevelBounds.max&&(this._updateIndent._indentationDist-=this._updateIndent._targetLevel-this._targetLevelBounds.max,this._updateIndent._targetLevel=this._targetLevelBounds.max),
// Has the target level changed?
this._targetLevel!==(this._targetLevel=this._updateIndent._targetLevel)&&
// Target level is changing, so update the ancestors
this._updateAncestorsBeforeRepaint(),
// Update the UI
// ---------------------------------------------------------------------
// How far away is the cursor from the exact target level distance?
this._updateIndent._targetLevelMouseDiff=this._updateIndent._mouseDist-this._updateIndent._indentationDist*Craft.StructureTableSorter.LEVEL_INDENT,
// What's the magnet impact of that?
this._updateIndent._magnetImpact=Math.round(this._updateIndent._targetLevelMouseDiff/15),
// Put it on a leash
Math.abs(this._updateIndent._magnetImpact)>Craft.StructureTableSorter.MAX_GIVE&&(this._updateIndent._magnetImpact=(this._updateIndent._magnetImpact>0?1:-1)*Craft.StructureTableSorter.MAX_GIVE),
// Apply the new margin/width
this._updateIndent._closestLevelMagnetIndent=this._getLevelIndent(this._targetLevel)+this._updateIndent._magnetImpact,this.helpers[0].css("margin-"+Craft.left,this._updateIndent._closestLevelMagnetIndent+this._helperMargin),this._$titleHelperCell.width(this._titleHelperCellOuterWidth-(this._updateIndent._closestLevelMagnetIndent+Craft.StructureTableSorter.BASE_PADDING))},/**
	 * Returns the indent size for a given level
	 */
_getLevelIndent:function(a){return(a-1)*Craft.StructureTableSorter.LEVEL_INDENT},/**
	 * Returns the base data that should be sent with StructureController Ajax requests.
	 */
_getAjaxBaseData:function(a){return{structureId:this.structureId,elementId:a.data("id"),siteId:a.find(".element:first").data("site-id")}},/**
	 * Returns a row's ancestor rows
	 */
_getAncestors:function(a,b){if(this._getAncestors._ancestors=[],0!=b)for(this._getAncestors._level=b,this._getAncestors._$prevRow=a.prev();this._getAncestors._$prevRow.length&&!(this._getAncestors._$prevRow.data("level")<this._getAncestors._level&&(this._getAncestors._ancestors.unshift(this._getAncestors._$prevRow),this._getAncestors._level=this._getAncestors._$prevRow.data("level"),0==this._getAncestors._level));)this._getAncestors._$prevRow=this._getAncestors._$prevRow.prev();return this._getAncestors._ancestors},/**
	 * Prepares to have the ancestors updated before the screen is repainted.
	 */
_updateAncestorsBeforeRepaint:function(){this._updateAncestorsFrame&&Garnish.cancelAnimationFrame(this._updateAncestorsFrame),this._updateAncestorsProxy||(this._updateAncestorsProxy=a.proxy(this,"_updateAncestors")),this._updateAncestorsFrame=Garnish.requestAnimationFrame(this._updateAncestorsProxy)},_updateAncestors:function(){
// Update the old ancestors
// -----------------------------------------------------------------
for(this._updateAncestorsFrame=null,this._updateAncestors._i=0;this._updateAncestors._i<this._ancestors.length;this._updateAncestors._i++)this._updateAncestors._$ancestor=this._ancestors[this._updateAncestors._i],
// One less descendant now
this._updateAncestors._$ancestor.data("descendants",this._updateAncestors._$ancestor.data("descendants")-1),
// Is it now childless?
0==this._updateAncestors._$ancestor.data("descendants")&&
// Remove its toggle
this._updateAncestors._$ancestor.find("> td > .toggle:first").remove();for(
// Update the new ancestors
// -----------------------------------------------------------------
this._updateAncestors._newAncestors=this._getAncestors(this.$targetItem,this._targetLevel),this._updateAncestors._i=0;this._updateAncestors._i<this._updateAncestors._newAncestors.length;this._updateAncestors._i++)this._updateAncestors._$ancestor=this._updateAncestors._newAncestors[this._updateAncestors._i],
// One more descendant now
this._updateAncestors._$ancestor.data("descendants",this._updateAncestors._$ancestor.data("descendants")+1),
// Is this its first child?
1==this._updateAncestors._$ancestor.data("descendants")&&
// Create its toggle
a('<span class="toggle expanded" title="'+Craft.t("app","Show/hide children")+'"></span>').insertAfter(this._updateAncestors._$ancestor.find("> td .move:first"));this._ancestors=this._updateAncestors._newAncestors,delete this._updateAncestors._i,delete this._updateAncestors._$ancestor,delete this._updateAncestors._newAncestors}},
// Static Properties
// =============================================================================
{BASE_PADDING:36,HELPER_MARGIN:-7,LEVEL_INDENT:44,MAX_GIVE:22,defaults:{onPositionChange:a.noop}}),/**
* Table Element Index View
*/
Craft.TableElementIndexView=Craft.BaseElementIndexView.extend({$table:null,$selectedSortHeader:null,structureTableSort:null,_totalVisiblePostStructureTableDraggee:null,_morePendingPostStructureTableDraggee:!1,getElementContainer:function(){
// Save a reference to the table
return this.$table=this.$container.find("table:first"),this.$table.children("tbody:first")},afterInit:function(){
// Make the table collapsible for mobile devices
Craft.cp.$collapsibleTables=Craft.cp.$collapsibleTables.add(this.$table),Craft.cp.updateResponsiveTables(),
// Set the sort header
this.initTableHeaders(),
// Create the Structure Table Sorter
"index"==this.elementIndex.settings.context&&"structure"==this.elementIndex.getSelectedSortAttribute()&&Garnish.hasAttr(this.$table,"data-structure-id")?this.structureTableSort=new Craft.StructureTableSorter(this,this.getAllElements(),{onSortChange:a.proxy(this,"_onStructureTableSortChange")}):this.structureTableSort=null,
// Handle expand/collapse toggles for Structures
"structure"==this.elementIndex.getSelectedSortAttribute()&&this.addListener(this.$elementContainer,"click",function(b){var c=a(b.target);c.hasClass("toggle")&&this._collapseElement(c)===!1&&this._expandElement(c)})},initTableHeaders:function(){for(var b=this.elementIndex.getSelectedSortAttribute(),c=this.$table.children("thead").children().children("[data-attribute]"),d=0;d<c.length;d++){var e=c.eq(d),f=e.attr("data-attribute");
// Is this the selected sort attribute?
if(f==b){this.$selectedSortHeader=e;var g=this.elementIndex.getSelectedSortDirection();e.addClass("ordered "+g).click(a.proxy(this,"_handleSelectedSortHeaderClick"))}else{
// Is this attribute sortable?
var h=this.elementIndex.getSortAttributeOption(f);h.length&&e.addClass("orderable").click(a.proxy(this,"_handleUnselectedSortHeaderClick"))}}},isVerticalList:function(){return!0},getTotalVisible:function(){return this._isStructureTableDraggingLastElements()?this._totalVisiblePostStructureTableDraggee:this._totalVisible},setTotalVisible:function(a){this._isStructureTableDraggingLastElements()?this._totalVisiblePostStructureTableDraggee=a:this._totalVisible=a},getMorePending:function(){return this._isStructureTableDraggingLastElements()?this._morePendingPostStructureTableDraggee:this._morePending},setMorePending:function(a){this._isStructureTableDraggingLastElements()?this._morePendingPostStructureTableDraggee=a:this._morePending=this._morePendingPostStructureTableDraggee=a},getLoadMoreParams:function(){var a=this.base();
// If we are dragging the last elements on the page,
// tell the controller to only load elements positioned after the draggee.
return this._isStructureTableDraggingLastElements()&&(a.criteria.positionedAfter=this.structureTableSort.$targetItem.data("id")),a},appendElements:function(a){this.base(a),this.structureTableSort&&this.structureTableSort.addItems(a),Craft.cp.updateResponsiveTables()},destroy:function(){this.$table&&(
// Remove the soon-to-be-wiped-out table from the list of collapsible tables
Craft.cp.$collapsibleTables=Craft.cp.$collapsibleTables.not(this.$table)),this.base()},createElementEditor:function(b){new Craft.ElementEditor(b,{params:{includeTableAttributesForSource:this.elementIndex.sourceKey},onSaveElement:a.proxy(function(a){a.tableAttributes&&this._updateTableAttributes(b,a.tableAttributes)},this)})},_collapseElement:function(a,b){if(!b&&!a.hasClass("expanded"))return!1;a.removeClass("expanded");for(
// Find and remove the descendant rows
var c=a.parent().parent(),d=c.data("id"),e=c.data("level"),f=c.next();f.length;){if(!Garnish.hasAttr(f,"data-spinnerrow")){if(f.data("level")<=e)break;this.elementSelect&&this.elementSelect.removeItems(f),this.structureTableSort&&this.structureTableSort.removeItems(f),this._totalVisible--}var g=f.next();f.remove(),f=g}
// Remember that this row should be collapsed
this.elementIndex.instanceState.collapsedElementIds||(this.elementIndex.instanceState.collapsedElementIds=[]),this.elementIndex.instanceState.collapsedElementIds.push(d),this.elementIndex.setInstanceState("collapsedElementIds",this.elementIndex.instanceState.collapsedElementIds),
// Bottom of the index might be viewable now
this.maybeLoadMore()},_expandElement:function(b,c){if(!c&&b.hasClass("expanded"))return!1;
// Remove this element from our list of collapsed elements
if(b.addClass("expanded"),this.elementIndex.instanceState.collapsedElementIds){var d=b.parent().parent(),e=d.data("id"),f=a.inArray(e,this.elementIndex.instanceState.collapsedElementIds);if(f!=-1){this.elementIndex.instanceState.collapsedElementIds.splice(f,1),this.elementIndex.setInstanceState("collapsedElementIds",this.elementIndex.instanceState.collapsedElementIds);
// Add a temporary row
var g=this._createSpinnerRowAfter(d),h=a.extend(!0,{},this.settings.params);h.criteria.descendantOf=e,Craft.postActionRequest("element-indexes/get-more-elements",h,a.proxy(function(b,c){
// Do we even care about this anymore?
if(g.parent().length&&"success"==c){var d=a(b.html),e=this._totalVisible+d.length,f=this.settings.batchSize&&d.length==this.settings.batchSize;if(f){
// Remove all the elements after it
var h=g.nextAll();this.elementSelect&&this.elementSelect.removeItems(h),this.structureTableSort&&this.structureTableSort.removeItems(h),h.remove(),e-=h.length}else
// Maintain the current 'more' status
f=this._morePending;g.replaceWith(d),(this.elementIndex.actions||this.settings.selectable)&&(this.elementSelect.addItems(d.filter(":not(.disabled)")),this.elementIndex.updateActionTriggers()),this.structureTableSort&&this.structureTableSort.addItems(d),Craft.appendHeadHtml(b.headHtml),Craft.appendFootHtml(b.footHtml),Craft.cp.updateResponsiveTables(),this.setTotalVisible(e),this.setMorePending(f),
// Is there room to load more right now?
this.maybeLoadMore()}},this))}}},_createSpinnerRowAfter:function(b){return a('<tr data-spinnerrow><td class="centeralign" colspan="'+b.children().length+'"><div class="spinner"/></td></tr>').insertAfter(b)},_isStructureTableDraggingLastElements:function(){return this.structureTableSort&&this.structureTableSort.dragging&&this.structureTableSort.draggingLastElements},_handleSelectedSortHeaderClick:function(b){var c=a(b.currentTarget);if(!c.hasClass("loading")){
// Reverse the sort direction
var d=this.elementIndex.getSelectedSortDirection(),e="asc"==d?"desc":"asc";this.elementIndex.setSortDirection(e),this._handleSortHeaderClick(b,c)}},_handleUnselectedSortHeaderClick:function(b){var c=a(b.currentTarget);if(!c.hasClass("loading")){var d=c.attr("data-attribute");this.elementIndex.setSortAttribute(d),this._handleSortHeaderClick(b,c)}},_handleSortHeaderClick:function(a,b){this.$selectedSortHeader&&this.$selectedSortHeader.removeClass("ordered asc desc"),b.removeClass("orderable").addClass("ordered loading"),this.elementIndex.storeSortAttributeAndDirection(),this.elementIndex.updateElements(),
// No need for two spinners
this.elementIndex.setIndexAvailable()},_updateTableAttributes:function(a,b){var c=a.closest("tr");for(var d in b)b.hasOwnProperty(d)&&c.children('td[data-attr="'+d+'"]:first').html(b[d])}}),/**
 * Tag select input
 */
Craft.TagSelectInput=Craft.BaseElementSelectInput.extend({searchTimeout:null,searchMenu:null,$container:null,$elementsContainer:null,$elements:null,$addTagInput:null,$spinner:null,_ignoreBlur:!1,init:function(b){
// Normalize the settings
// ---------------------------------------------------------------------
// Are they still passing in a bunch of arguments?
if(!a.isPlainObject(b)){for(var c={},d=["id","name","tagGroupId","sourceElementId"],e=0;e<d.length&&"undefined"!=typeof arguments[e];e++)c[d[e]]=arguments[e];b=c}this.base(a.extend({},Craft.TagSelectInput.defaults,b)),this.$addTagInput=this.$container.children(".add").children(".text"),this.$spinner=this.$addTagInput.next(),this.addListener(this.$addTagInput,"textchange",a.proxy(function(){this.searchTimeout&&clearTimeout(this.searchTimeout),this.searchTimeout=setTimeout(a.proxy(this,"searchForTags"),500)},this)),this.addListener(this.$addTagInput,"keypress",function(a){a.keyCode==Garnish.RETURN_KEY&&(a.preventDefault(),this.searchMenu&&this.selectTag(this.searchMenu.$options[0]))}),this.addListener(this.$addTagInput,"focus",function(){this.searchMenu&&this.searchMenu.show()}),this.addListener(this.$addTagInput,"blur",function(){return this._ignoreBlur?void(this._ignoreBlur=!1):void setTimeout(a.proxy(function(){this.searchMenu&&this.searchMenu.hide()},this),1)})},
// No "add" button
getAddElementsBtn:a.noop,getElementSortAxis:function(){return null},searchForTags:function(){this.searchMenu&&this.killSearchMenu();var b=this.$addTagInput.val();if(b){this.$spinner.removeClass("hidden");for(var c=[],d=0;d<this.$elements.length;d++){var e=a(this.$elements[d]).data("id");e&&c.push(e)}this.settings.sourceElementId&&c.push(this.settings.sourceElementId);var f={search:this.$addTagInput.val(),tagGroupId:this.settings.tagGroupId,excludeIds:c};Craft.postActionRequest("tags/search-for-tags",f,a.proxy(function(b,c){if(this.$spinner.addClass("hidden"),"success"==c){for(var d=a('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),e=a("<ul/>").appendTo(d),g=0;g<b.tags.length;g++){var h=a("<li/>").appendTo(e);a('<a data-icon="tag"/>').appendTo(h).text(b.tags[g].title).data("id",b.tags[g].id)}if(!b.exactMatch){var h=a("<li/>").appendTo(e);a('<a data-icon="+"/>').appendTo(h).text(f.search)}e.find("> li:first-child > a").addClass("hover"),this.searchMenu=new Garnish.Menu(d,{attachToElement:this.$addTagInput,onOptionSelect:a.proxy(this,"selectTag")}),this.addListener(d,"mousedown",a.proxy(function(){this._ignoreBlur=!0},this)),this.searchMenu.show()}},this))}else this.$spinner.addClass("hidden")},selectTag:function(b){var c=a(b),d=c.data("id"),e=c.text(),f=a("<div/>",{class:"element small removable","data-id":d,"data-site-id":this.settings.targetSiteId,"data-label":e,"data-editable":"1"}).appendTo(this.$elementsContainer),g=a("<input/>",{type:"hidden",name:this.settings.name+"[]",value:d}).appendTo(f);a("<a/>",{class:"delete icon",title:Craft.t("app","Remove")}).appendTo(f);var h=a("<div/>",{class:"label"}).appendTo(f);a("<span/>",{class:"title",text:e}).appendTo(h);var i=-(f.outerWidth()+10);this.$addTagInput.css("margin-"+Craft.left,i+"px");var j={};if(j["margin-"+Craft.left]=0,this.$addTagInput.velocity(j,"fast"),this.$elements=this.$elements.add(f),this.addElements(f),this.killSearchMenu(),this.$addTagInput.val(""),this.$addTagInput.focus(),!d){
// We need to create the tag first
f.addClass("loading disabled");var k={groupId:this.settings.tagGroupId,title:e};Craft.postActionRequest("tags/create-tag",k,a.proxy(function(a,b){"success"==b&&a.success?(f.attr("data-id",a.id),g.val(a.id),f.removeClass("loading disabled")):(this.removeElement(f),"success"==b&&
// Some sort of validation error that still resulted in  a 200 response. Shouldn't be possible though.
Craft.cp.displayError(Craft.t("app","An unknown error occurred.")))},this))}},killSearchMenu:function(){this.searchMenu.hide(),this.searchMenu.destroy(),this.searchMenu=null}},{defaults:{tagGroupId:null}}),/**
* Thumb Element Index View
*/
Craft.ThumbsElementIndexView=Craft.BaseElementIndexView.extend({getElementContainer:function(){return this.$container.children("ul")}}),Craft.ui={createTextInput:function(b){var c=a("<input/>",{class:"text",type:b.type||"text",id:b.id,size:b.size,name:b.name,value:b.value,maxlength:b.maxlength,autofocus:this.getAutofocusValue(b.autofocus),autocomplete:"undefined"!=typeof b.autocomplete&&b.autocomplete?null:"off",disabled:this.getDisabledValue(b.disabled),readonly:b.readonly,title:b.title,placeholder:b.placeholder});return b.class&&c.addClass(b.class),b.placeholder&&c.addClass("nicetext"),"password"==b.type&&c.addClass("password"),b.disabled&&c.addClass("disabled"),b.size||c.addClass("fullwidth"),b.showCharsLeft&&b.maxlength&&c.attr("data-show-chars-left").css("padding-"+("ltr"==Craft.orientation?"right":"left"),7.2*b.maxlength.toString().length+14+"px"),(b.placeholder||b.showCharsLeft)&&new Garnish.NiceText(c),"password"==b.type?a('<div class="passwordwrapper"/>').append(c):c},createTextField:function(a){return this.createField(this.createTextInput(a),a)},createTextarea:function(b){var c=a("<textarea/>",{class:"text",rows:b.rows||2,cols:b.cols||50,id:b.id,name:b.name,maxlength:b.maxlength,autofocus:b.autofocus&&!Garnish.isMobileBrowser(!0),disabled:!!b.disabled,placeholder:b.placeholder,html:b.value});return b.showCharsLeft&&c.attr("data-show-chars-left",""),b.class&&c.addClass(b.class),b.size||c.addClass("fullwidth"),c},createTextareaField:function(a){return this.createField(this.createTextarea(a),a)},createSelect:function(b){var c=a("<div/>",{class:"select"});b.class&&c.addClass(b.class);var d,e=a("<select/>",{id:b.id,name:b.name,autofocus:b.autofocus&&Garnish.isMobileBrowser(!0),disabled:b.disabled,"data-target-prefix":b.targetPrefix}).appendTo(c);for(var f in b.options)if(b.options.hasOwnProperty(f)){var g=b.options[f];
// Starting a new <optgroup>?
if("undefined"!=typeof g.optgroup)d=a("<optgroup/>",{label:g.label}).appendTo(e);else{var h="undefined"!=typeof g.label?g.label:g,i="undefined"!=typeof g.value?g.value:f,j="undefined"!=typeof g.disabled&&g.disabled;a("<option/>",{value:i,selected:i==b.value,disabled:j,html:h}).appendTo(d||e)}}return b.toggle&&(e.addClass("fieldtoggle"),new Craft.FieldToggle(e)),c},createSelectField:function(a){return this.createField(this.createSelect(a),a)},createCheckbox:function(b){var c=b.id||"checkbox"+Math.floor(1e9*Math.random()),d=a("<input/>",{type:"checkbox",value:"undefined"!=typeof b.value?b.value:"1",id:c,class:"checkbox",name:b.name,checked:b.checked?"checked":null,autofocus:this.getAutofocusValue(b.autofocus),disabled:this.getDisabledValue(b.disabled),"data-target":b.toggle,"data-reverse-target":b.reverseToggle});b.class&&d.addClass(b.class),(b.toggle||b.reverseToggle)&&(d.addClass("fieldtoggle"),new Craft.FieldToggle(d));var e=a("<label/>",{for:c,text:b.label});
// Should we include a hidden input first?
// Should we include a hidden input first?
return a(b.name&&(b.name.length<3||"[]"!=b.name.substr(-2))?[a("<input/>",{type:"hidden",name:b.name,value:""})[0],d[0],e[0]]:[d[0],e[0]])},createCheckboxField:function(b){var c=a('<div class="field checkboxfield"/>',{id:b.id?b.id+"-field":null});return b.first&&c.addClass("first"),b.instructions&&c.addClass("has-instructions"),this.createCheckbox(b).appendTo(c),b.instructions&&a('<div class="instructions"/>').text(b.instructions).appendTo(c),c},createCheckboxSelect:function(b){var c=b.allValue||"*",d=!b.values||b.values==b.allValue,e=a('<div class="checkbox-select"/>');b.class&&e.addClass(b.class),
// Create the "All" checkbox
a("<div/>").appendTo(e).append(this.createCheckbox({id:b.id,class:"all",label:"<b>"+(b.allLabel||Craft.t("app","All"))+"</b>",name:b.name,value:c,checked:d,autofocus:b.autofocus}));
// Create the actual options
for(var f=0;f<b.options.length;f++){var g=b.options[f];g.value!=c&&a("<div/>").appendTo(e).append(this.createCheckbox({label:g.label,name:b.name?b.name+"[]":null,value:g.value,checked:d||Craft.inArray(g.value,b.values),disabled:d}))}return new Garnish.CheckboxSelect(e),e},createCheckboxSelectField:function(a){return this.createField(this.createCheckboxSelect(a),a)},createLightswitch:function(b){var c=b.value||"1",d=a("<div/>",{class:"lightswitch",tabindex:"0","data-value":c,id:b.id,"aria-labelledby":b.labelId,"data-target":b.toggle,"data-reverse-target":b.reverseToggle});return b.on&&d.addClass("on"),b.small&&d.addClass("small"),b.disabled&&d.addClass("disabled"),a('<div class="lightswitch-container"><div class="label on"></div><div class="handle"></div><div class="label off"></div></div>').appendTo(d),b.name&&a("<input/>",{type:"hidden",name:b.name,value:b.on?c:"",disabled:b.disabled}).appendTo(d),(b.toggle||b.reverseToggle)&&(d.addClass("fieldtoggle"),new Craft.FieldToggle(d)),d.lightswitch()},createLightswitchField:function(a){return this.createField(this.createLightswitch(a),a)},createField:function(b,c){var d=c.label&&"__blank__"!=c.label?c.label:null,e=Craft.isMultiSite&&c.siteId?c.siteId:null,f=a("<div/>",{class:"field",id:c.fieldId||(c.id?c.id+"-field":null)});if(c.first&&f.addClass("first"),d||c.instructions){var g=a('<div class="heading"/>').appendTo(f);if(d){var h=a("<label/>",{id:c.labelId||(c.id?c.id+"-label":null),class:c.required?"required":null,for:c.id,text:d}).appendTo(g);if(e)for(var i=0;i<Craft.sites.length;i++)if(Craft.sites[i].id==e){a('<span class="site"/>').text(Craft.sites[i].name).appendTo(h);break}}c.instructions&&a('<div class="instructions"/>').text(c.instructions).appendTo(g)}return a('<div class="input"/>').append(b).appendTo(f),c.warning&&a('<p class="warning"/>').text(c.warning).appendTo(f),c.errors&&this.addErrorsToField(f,c.errors),f},createErrorList:function(b){var c=a('<ul class="errors"/>');return b&&this.addErrorsToList(c,b),c},addErrorsToList:function(b,c){for(var d=0;d<c.length;d++)a("<li/>").text(c[d]).appendTo(b)},addErrorsToField:function(a,b){if(b){a.addClass("has-errors"),a.children(".input").addClass("errors");var c=a.children("ul.errors");c.length||(c=this.createErrorList().appendTo(a)),this.addErrorsToList(c,b)}},clearErrorsFromField:function(a){a.removeClass("has-errors"),a.children(".input").removeClass("errors"),a.children("ul.errors").remove()},getAutofocusValue:function(a){return a&&!Garnish.isMobileBrowser(!0)?"autofocus":null},getDisabledValue:function(a){return a?"disabled":null}},/**
 * Craft Upgrade Modal
 */
Craft.UpgradeModal=Garnish.Modal.extend({$container:null,$body:null,$compareScreen:null,$checkoutScreen:null,$successScreen:null,$checkoutForm:null,$checkoutLogo:null,$checkoutSubmitBtn:null,$checkoutSpinner:null,$checkoutFormError:null,$checkoutSecure:null,clearCheckoutFormTimeout:null,$customerNameInput:null,$customerEmailInput:null,$ccField:null,$ccNumInput:null,$ccExpInput:null,$ccCvcInput:null,$businessFieldsToggle:null,$businessNameInput:null,$businessAddress1Input:null,$businessAddress2Input:null,$businessCityInput:null,$businessStateInput:null,$businessCountryInput:null,$businessZipInput:null,$businessTaxIdInput:null,$purchaseNotesInput:null,$couponInput:null,$couponSpinner:null,submittingPurchase:!1,stripePublicKey:null,editions:null,countries:null,states:null,edition:null,initializedCheckoutForm:!1,applyingCouponCode:!1,applyNewCouponCodeAfterDoneLoading:!1,couponPrice:null,formattedCouponPrice:null,init:function(b){this.$container=a('<div id="upgrademodal" class="modal loading"/>').appendTo(Garnish.$bod),this.base(this.$container,a.extend({resizable:!0},b)),Craft.postActionRequest("app/get-upgrade-modal",a.proxy(function(b,c){if(this.$container.removeClass("loading"),"success"==c){if(b.success){this.stripePublicKey=b.stripePublicKey,this.editions=b.editions,this.countries=b.countries,this.states=b.states,this.$container.append(b.modalHtml),this.$container.append('<script type="text/javascript" src="'+Craft.getResourceUrl("lib/jquery.payment"+(Craft.useCompressedJs?".min":"")+".js")+'"></script>'),this.$compareScreen=this.$container.children("#upgrademodal-compare"),this.$checkoutScreen=this.$container.children("#upgrademodal-checkout"),this.$successScreen=this.$container.children("#upgrademodal-success"),this.$checkoutLogo=this.$checkoutScreen.find(".logo:first"),this.$checkoutForm=this.$checkoutScreen.find("form:first"),this.$checkoutSubmitBtn=this.$checkoutForm.find("#pay-button"),this.$checkoutSpinner=this.$checkoutForm.find("#pay-spinner"),this.$customerNameInput=this.$checkoutForm.find("#customer-name"),this.$customerEmailInput=this.$checkoutForm.find("#customer-email"),this.$ccField=this.$checkoutForm.find("#cc-inputs"),this.$ccNumInput=this.$ccField.find("#cc-num"),this.$ccExpInput=this.$ccField.find("#cc-exp"),this.$ccCvcInput=this.$ccField.find("#cc-cvc"),this.$businessFieldsToggle=this.$checkoutForm.find(".fieldtoggle"),this.$businessNameInput=this.$checkoutForm.find("#business-name"),this.$businessAddress1Input=this.$checkoutForm.find("#business-address1"),this.$businessAddress2Input=this.$checkoutForm.find("#business-address2"),this.$businessCityInput=this.$checkoutForm.find("#business-city"),this.$businessStateInput=this.$checkoutForm.find("#business-state"),this.$businessCountryInput=this.$checkoutForm.find("#business-country"),this.$businessZipInput=this.$checkoutForm.find("#business-zip"),this.$businessTaxIdInput=this.$checkoutForm.find("#business-taxid"),this.$purchaseNotesInput=this.$checkoutForm.find("#purchase-notes"),this.$checkoutSecure=this.$checkoutScreen.find(".secure:first"),this.$couponInput=this.$checkoutForm.find("#coupon-input"),this.$couponSpinner=this.$checkoutForm.find("#coupon-spinner");var d=this.$compareScreen.find(".buybtn");this.addListener(d,"click","onBuyBtnClick");var e=this.$compareScreen.find(".btn.test");this.addListener(e,"click","onTestBtnClick");var f=this.$checkoutScreen.find("#upgrademodal-cancelcheckout");this.addListener(f,"click","cancelCheckout")}else{var g;g=b.error?b.error:Craft.t("app","An unknown error occurred."),this.$container.append('<div class="body">'+g+"</div>")}
// Include Stripe.js
a('<script type="text/javascript" src="https://js.stripe.com/v1/"></script>').appendTo(Garnish.$bod)}},this))},initializeCheckoutForm:function(){this.$ccNumInput.payment("formatCardNumber"),this.$ccExpInput.payment("formatCardExpiry"),this.$ccCvcInput.payment("formatCardCVC"),this.$businessFieldsToggle.fieldtoggle(),this.$businessCountryInput.selectize({valueField:"iso",labelField:"name",searchField:["name","iso"],dropdownParent:"body",inputClass:"selectize-input text"}),this.$businessCountryInput[0].selectize.addOption(this.countries),this.$businessCountryInput[0].selectize.refreshOptions(!1),this.$businessStateInput.selectize({valueField:"abbr",labelField:"name",searchField:["name","abbr"],dropdownParent:"body",inputClass:"selectize-input text",create:!0}),this.$businessStateInput[0].selectize.addOption(this.states),this.$businessStateInput[0].selectize.refreshOptions(!1),this.addListener(this.$couponInput,"textchange",{delay:500},"applyCoupon"),this.addListener(this.$checkoutForm,"submit","submitPurchase")},applyCoupon:function(){if(this.applyingCouponCode)return void(this.applyNewCouponCodeAfterDoneLoading=!0);var b=this.$couponInput.val();if(b){var c={edition:this.edition,couponCode:b};this.applyingCouponCode=!0,this.$couponSpinner.removeClass("hidden"),Craft.postActionRequest("app/get-coupon-price",c,a.proxy(function(a,b){this.applyingCouponCode=!1,
// Are we just waiting to apply a new code?
this.applyNewCouponCodeAfterDoneLoading?(this.applyNewCouponCodeAfterDoneLoading=!1,this.applyCoupon()):(this.$couponSpinner.addClass("hidden"),"success"==b&&a.success&&(this.couponPrice=a.couponPrice,this.formattedCouponPrice=a.formattedCouponPrice,this.updateCheckoutUi()))},this))}else
// Clear out the coupon price
this.couponPrice=null,this.updateCheckoutUi()},onHide:function(){this.initializedCheckoutForm&&(this.$businessCountryInput[0].selectize.blur(),this.$businessStateInput[0].selectize.blur()),this.clearCheckoutFormInABit(),this.base()},onBuyBtnClick:function(b){var c=a(b.currentTarget);switch(this.edition=c.data("edition"),this.couponPrice=null,this.formattedCouponPrice=null,this.edition){case 1:this.$checkoutLogo.attr("class","logo craftclient").text("Client");break;case 2:this.$checkoutLogo.attr("class","logo craftpro").text("Pro")}this.updateCheckoutUi(),this.clearCheckoutFormTimeout&&clearTimeout(this.clearCheckoutFormTimeout);
// Slide it in
var d=this.getWidth();this.$compareScreen.velocity("stop").animateLeft(-d,"fast",a.proxy(function(){this.$compareScreen.addClass("hidden"),this.initializedCheckoutForm||(this.initializeCheckoutForm(),this.initializedCheckoutForm=!0)},this)),this.$checkoutScreen.velocity("stop").css(Craft.left,d).removeClass("hidden").animateLeft(0,"fast")},updateCheckoutUi:function(){
// Only show the CC fields if there is a price
0==this.getPrice()?this.$ccField.hide():this.$ccField.show(),
// Update the Pay button
this.$checkoutSubmitBtn.val(Craft.t("app","Pay {price}",{price:this.getFormattedPrice()}))},getPrice:function(){return null!==this.couponPrice?this.couponPrice:this.editions[this.edition].salePrice?this.editions[this.edition].salePrice:this.editions[this.edition].price},getFormattedPrice:function(){return null!==this.couponPrice?this.formattedCouponPrice:this.editions[this.edition].salePrice?this.editions[this.edition].formattedSalePrice:this.editions[this.edition].formattedPrice},onTestBtnClick:function(b){var c={edition:a(b.currentTarget).data("edition")};Craft.postActionRequest("app/test-upgrade",c,a.proxy(function(b,c){if("success"==c){var d=this.getWidth();this.$compareScreen.velocity("stop").animateLeft(-d,"fast",a.proxy(function(){this.$compareScreen.addClass("hidden")},this)),this.onUpgrade()}},this))},cancelCheckout:function(){var b=this.getWidth();this.$compareScreen.velocity("stop").removeClass("hidden").animateLeft(0,"fast"),this.$checkoutScreen.velocity("stop").animateLeft(b,"fast",a.proxy(function(){this.$checkoutScreen.addClass("hidden")},this)),this.clearCheckoutFormInABit()},getExpiryValues:function(){return this.$ccExpInput.payment("cardExpiryVal")},submitPurchase:function(b){if(b.preventDefault(),!this.submittingPurchase){this.cleanupCheckoutForm();
// Get the price
var c=this.getPrice(),d=this.getExpiryValues(),e={name:this.$customerNameInput.val(),number:this.$ccNumInput.val(),exp_month:d.month,exp_year:d.year,cvc:this.$ccCvcInput.val()},f=!0;e.name||(f=!1,this.$customerNameInput.addClass("error")),0!=c&&(Stripe.validateCardNumber(e.number)||(f=!1,this.$ccNumInput.addClass("error")),Stripe.validateExpiry(e.exp_month,e.exp_year)||(f=!1,this.$ccExpInput.addClass("error")),Stripe.validateCVC(e.cvc)||(f=!1,this.$ccCvcInput.addClass("error"))),f?(this.submittingPurchase=!0,
// Get a CC token from Stripe.js
this.$checkoutSubmitBtn.addClass("active"),this.$checkoutSpinner.removeClass("hidden"),0!=c?(Stripe.setPublishableKey(this.stripePublicKey),Stripe.createToken(e,a.proxy(function(a,b){b.error?(this.onPurchaseResponse(),this.showError(b.error.message),Garnish.shake(this.$checkoutForm,"left")):this.sendPurchaseRequest(c,b.id)},this))):this.sendPurchaseRequest(0,null)):Garnish.shake(this.$checkoutForm,"left")}},sendPurchaseRequest:function(b,c){
// Pass the token along to Elliott to charge the card
var d=0!=b?this.getExpiryValues():{month:null,year:null},e={ccTokenId:c,expMonth:d.month,expYear:d.year,edition:this.edition,expectedPrice:b,name:this.$customerNameInput.val(),email:this.$customerEmailInput.val(),businessName:this.$businessNameInput.val(),businessAddress1:this.$businessAddress1Input.val(),businessAddress2:this.$businessAddress2Input.val(),businessCity:this.$businessCityInput.val(),businessState:this.$businessStateInput.val(),businessCountry:this.$businessCountryInput.val(),businessZip:this.$businessZipInput.val(),businessTaxId:this.$businessTaxIdInput.val(),purchaseNotes:this.$purchaseNotesInput.val(),couponCode:this.$couponInput.val()};Craft.postActionRequest("app/purchase-upgrade",e,a.proxy(this,"onPurchaseUpgrade"))},onPurchaseResponse:function(){this.submittingPurchase=!1,this.$checkoutSubmitBtn.removeClass("active"),this.$checkoutSpinner.addClass("hidden")},onPurchaseUpgrade:function(b,c){if(this.onPurchaseResponse(),"success"==c)if(b.success){var d=this.getWidth();this.$checkoutScreen.velocity("stop").animateLeft(-d,"fast",a.proxy(function(){this.$checkoutScreen.addClass("hidden")},this)),this.onUpgrade()}else{if(b.errors){var e="";for(var f in b.errors)b.errors.hasOwnProperty(f)&&(e&&(e+="<br>"),e+=b.errors[f]);this.showError(e)}else var e=Craft.t("app","An unknown error occurred.");Garnish.shake(this.$checkoutForm,"left")}},showError:function(b){this.$checkoutFormError=a('<p class="error centeralign">'+b+"</p>").insertBefore(this.$checkoutSecure)},onUpgrade:function(){this.$successScreen.css(Craft.left,this.getWidth()).removeClass("hidden").animateLeft(0,"fast");var a=this.$successScreen.find(".btn:first");this.addListener(a,"click",function(){location.reload()}),this.trigger("upgrade")},cleanupCheckoutForm:function(){this.$checkoutForm.find(".error").removeClass("error"),this.$checkoutFormError&&(this.$checkoutFormError.remove(),this.$checkoutFormError=null)},clearCheckoutForm:function(){this.$customerNameInput.val(""),this.$customerEmailInput.val(""),this.$ccNumInput.val(""),this.$ccExpInput.val(""),this.$ccCvcInput.val(""),this.$businessNameInput.val(""),this.$businessAddress1Input.val(""),this.$businessAddress2Input.val(""),this.$businessCityInput.val(""),this.$businessStateInput.val(""),this.$businessCountryInput.val(""),this.$businessZipInput.val(""),this.$businessTaxIdInput.val(""),this.$purchaseNotesInput.val(""),this.$couponInput.val("")},clearCheckoutFormInABit:function(){
// Clear the CC info after a period of inactivity
this.clearCheckoutFormTimeout=setTimeout(a.proxy(this,"clearCheckoutForm"),Craft.UpgradeModal.clearCheckoutFormTimeoutDuration)}},{clearCheckoutFormTimeoutDuration:3e4}),/**
 * File Manager.
 */
Craft.Uploader=Garnish.Base.extend({uploader:null,allowedKinds:null,$element:null,settings:null,_rejectedFiles:{},_extensionList:null,_totalFileCounter:0,_validFileCounter:0,init:function(b,c){this._rejectedFiles={size:[],type:[],limit:[]},this.$element=b,this.allowedKinds=null,this._extensionList=null,this._totalFileCounter=0,this._validFileCounter=0,c=a.extend({},Craft.Uploader.defaults,c);var d=c.events;delete c.events,c.allowedKinds&&c.allowedKinds.length&&("string"==typeof c.allowedKinds&&(c.allowedKinds=[c.allowedKinds]),this.allowedKinds=c.allowedKinds,delete c.allowedKinds),c.autoUpload=!1,this.uploader=this.$element.fileupload(c);for(var e in d)d.hasOwnProperty(e)&&this.uploader.on(e,d[e]);this.settings=c,this.uploader.on("fileuploadadd",a.proxy(this,"onFileAdd"))},/**
	 * Set uploader parameters.
	 */
setParams:function(a){
// If CSRF protection isn't enabled, these won't be defined.
"undefined"!=typeof Craft.csrfTokenName&&"undefined"!=typeof Craft.csrfTokenValue&&(
// Add the CSRF token
a[Craft.csrfTokenName]=Craft.csrfTokenValue),this.uploader.fileupload("option",{formData:a})},/**
	 * Get the number of uploads in progress.
	 */
getInProgress:function(){return this.uploader.fileupload("active")},/**
	 * Return true, if this is the last upload.
	 */
isLastUpload:function(){
// Processing the last file or not processing at all.
return this.getInProgress()<2},/**
	 * Called on file add.
	 */
onFileAdd:function(b,c){b.stopPropagation();var d=!1;
// Make sure that file API is there before relying on it
return this.allowedKinds&&(this._extensionList||this._createExtensionList(),d=!0),c.process().done(a.proxy(function(){var b=c.files[0],e=!0;if(d){var f=b.name.match(/\.([a-z0-4_]+)$/i),g=f[1];a.inArray(g.toLowerCase(),this._extensionList)==-1&&(e=!1,this._rejectedFiles.type.push("“"+b.name+"”"))}b.size>this.settings.maxFileSize&&(this._rejectedFiles.size.push("“"+b.name+"”"),e=!1),
// If the validation has passed for this file up to now, check if we're not hitting any limits
e&&"function"==typeof this.settings.canAddMoreFiles&&!this.settings.canAddMoreFiles(this._validFileCounter)&&(this._rejectedFiles.limit.push("“"+b.name+"”"),e=!1),e&&(this._validFileCounter++,c.submit()),++this._totalFileCounter==c.originalFiles.length&&(this._totalFileCounter=0,this._validFileCounter=0,this.processErrorMessages())},this)),!0},/**
	 * Process error messages.
	 */
processErrorMessages:function(){if(this._rejectedFiles.type.length){var a;a=1==this._rejectedFiles.type.length?"The file {files} could not be uploaded. The allowed file kinds are: {kinds}.":"The files {files} could not be uploaded. The allowed file kinds are: {kinds}.",a=Craft.t("app",a,{files:this._rejectedFiles.type.join(", "),kinds:this.allowedKinds.join(", ")}),this._rejectedFiles.type=[],alert(a)}if(this._rejectedFiles.size.length){var a;a=1==this._rejectedFiles.size.length?"The file {files} could not be uploaded, because it exceeds the maximum upload size of {size}.":"The files {files} could not be uploaded, because they exceeded the maximum upload size of {size}.",a=Craft.t("app",a,{files:this._rejectedFiles.size.join(", "),size:this.humanFileSize(Craft.maxUploadSize)}),this._rejectedFiles.size=[],alert(a)}if(this._rejectedFiles.limit.length){var a;a=1==this._rejectedFiles.limit.length?"The file {files} could not be uploaded, because the field limit has been reached.":"The files {files} could not be uploaded, because the field limit has been reached.",a=Craft.t("app",a,{files:this._rejectedFiles.limit.join(", ")}),this._rejectedFiles.limit=[],alert(a)}},humanFileSize:function(a,b){var c=1024;if(a<c)return a+" B";var d=["kB","MB","GB","TB","PB","EB","ZB","YB"],e=-1;do a/=c,++e;while(a>=c);return a.toFixed(1)+" "+d[e]},_createExtensionList:function(){this._extensionList=[];for(var a=0;a<this.allowedKinds.length;a++){var b=this.allowedKinds[a];if("undefined"!=typeof Craft.fileKinds[b])for(var c=0;c<Craft.fileKinds[b].extensions.length;c++){var d=Craft.fileKinds[b].extensions[c];this._extensionList.push(d)}}},destroy:function(){this.$element.fileupload("destroy"),this.base()}},
// Static Properties
// =============================================================================
{defaults:{dropZone:null,pasteZone:null,fileInput:null,sequentialUploads:!0,maxFileSize:Craft.maxUploadSize,allowedKinds:null,events:{},canAddMoreFiles:null}}),/**
 * Handle Generator
 */
Craft.UriFormatGenerator=Craft.BaseInputGenerator.extend({generateTargetValue:function(a){
// Remove HTML tags
a=a.replace("/<(.*?)>/g",""),
// Make it lowercase
a=a.toLowerCase(),
// Convert extended ASCII characters to basic ASCII
a=Craft.asciiString(a),
// Handle must start with a letter and end with a letter/number
a=a.replace(/^[^a-z]+/,""),a=a.replace(/[^a-z0-9]+$/,"");
// Get the "words"
var b=Craft.filterArray(a.split(/[^a-z0-9]+/)),c=b.join("-");return c&&this.settings.suffix&&(c+=this.settings.suffix),c}})}(jQuery);
//# sourceMappingURL=Craft.js.map