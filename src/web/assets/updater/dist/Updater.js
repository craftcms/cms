!function(){var t={174:function(){},379:function(t,e,r){var n=r(174);n.__esModule&&(n=n.default),"string"==typeof n&&(n=[[t.id,n,""]]),n.locals&&(t.exports=n.locals),(0,r(673).Z)("1b71594c",n,!0,{})},673:function(t,e,r){"use strict";function n(t,e){for(var r=[],n={},a=0;a<e.length;a++){var s=e[a],o=s[0],i={id:t+":"+a,css:s[1],media:s[2],sourceMap:s[3]};n[o]?n[o].parts.push(i):r.push(n[o]={id:o,parts:[i]})}return r}r.d(e,{Z:function(){return h}});var a="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!a)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var s={},o=a&&(document.head||document.getElementsByTagName("head")[0]),i=null,u=0,l=!1,c=function(){},d=null,p="data-vue-ssr-id",f="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function h(t,e,r,a){l=r,d=a||{};var o=n(t,e);return v(o),function(e){for(var r=[],a=0;a<o.length;a++){var i=o[a];(u=s[i.id]).refs--,r.push(u)}for(e?v(o=n(t,e)):o=[],a=0;a<r.length;a++){var u;if(0===(u=r[a]).refs){for(var l=0;l<u.parts.length;l++)u.parts[l]();delete s[u.id]}}}}function v(t){for(var e=0;e<t.length;e++){var r=t[e],n=s[r.id];if(n){n.refs++;for(var a=0;a<n.parts.length;a++)n.parts[a](r.parts[a]);for(;a<r.parts.length;a++)n.parts.push(g(r.parts[a]));n.parts.length>r.parts.length&&(n.parts.length=r.parts.length)}else{var o=[];for(a=0;a<r.parts.length;a++)o.push(g(r.parts[a]));s[r.id]={id:r.id,refs:1,parts:o}}}}function m(){var t=document.createElement("style");return t.type="text/css",o.appendChild(t),t}function g(t){var e,r,n=document.querySelector("style["+p+'~="'+t.id+'"]');if(n){if(l)return c;n.parentNode.removeChild(n)}if(f){var a=u++;n=i||(i=m()),e=S.bind(null,n,a,!1),r=S.bind(null,n,a,!0)}else n=m(),e=y.bind(null,n),r=function(){n.parentNode.removeChild(n)};return e(t),function(n){if(n){if(n.css===t.css&&n.media===t.media&&n.sourceMap===t.sourceMap)return;e(t=n)}else r()}}var b,C=(b=[],function(t,e){return b[t]=e,b.filter(Boolean).join("\n")});function S(t,e,r,n){var a=r?"":n.css;if(t.styleSheet)t.styleSheet.cssText=C(e,a);else{var s=document.createTextNode(a),o=t.childNodes;o[e]&&t.removeChild(o[e]),o.length?t.insertBefore(s,o[e]):t.appendChild(s)}}function y(t,e){var r=e.css,n=e.media,a=e.sourceMap;if(n&&t.setAttribute("media",n),d.ssrId&&t.setAttribute(p,e.id),a&&(r+="\n/*# sourceURL="+a.sources[0]+" */",r+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(a))))+" */"),t.styleSheet)t.styleSheet.cssText=r;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(r))}}}},e={};function r(n){var a=e[n];if(void 0!==a)return a.exports;var s=e[n]={id:n,exports:{}};return t[n](s,s.exports,r),s.exports}r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,{a:e}),e},r.d=function(t,e){for(var n in e)r.o(e,n)&&!r.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:e[n]})},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},function(){"use strict";var t;r(379),t=jQuery,Craft.Updater=Garnish.Base.extend({$graphic:null,$status:null,error:null,data:null,actionPrefix:null,init:function(e){this.actionPrefix=e,this.$graphic=t("#graphic"),this.$status=t("#status")},parseStatus:function(t){return"<p>"+Craft.escapeHtml(t).replace(/\n{2,}/g,"</p><p>").replace(/\n/g,"<br>").replace(/`(.*?)`/g,"<code>$1</code>")+"</p>"},showStatus:function(t){this.$status.html(this.parseStatus(t))},showError:function(t){this.$graphic.removeClass("spinner").addClass("error"),this.showStatus(t)},showErrorDetails:function(e){t("<div/>",{id:"error",class:"code",tabindex:0,html:this.parseStatus(e)}).appendTo(this.$status)},postActionRequest:function(t){var e=this,r={data:this.data};Craft.sendActionRequest("POST",t,{data:r}).then((function(t){e.setState(t.data)})).catch((function(t){var r=t.response;e.handleFatalError(r.data)}))},setState:function(t){this.$graphic.addClass("spinner").removeClass("error"),t.data&&(this.data=t.data),t.status?this.showStatus(t.status):t.error&&(this.showError(t.error),t.errorDetails&&this.showErrorDetails(t.errorDetails)),t.nextAction?this.postActionRequest(t.nextAction):t.options?this.showOptions(t):t.finished&&this.onFinish(t.returnUrl)},showOptions:function(e){for(var r=t("<div/>",{id:"options",class:"buttons"}).appendTo(this.$status),n=0;n<e.options.length;n++){var a=e.options[n],s=t("<a/>",{class:"btn big",text:a.label}).appendTo(r);a.submit&&s.addClass("submit"),a.email?s.attr("href",this.getEmailLink(e,a)):a.url?(s.attr("href",a.url),s.attr("target","_blank")):(s.attr("role","button"),this.addListener(s,"click",a,"onOptionSelect"))}},getEmailLink:function(t,e){var r="mailto:"+e.email+"?subject="+encodeURIComponent(e.subject||"Craft update failure"),n="Describe what happened here.";return t.errorDetails&&(n+="\n\n-----------------------------------------------------------\n\n"+t.errorDetails),r+"&body="+encodeURIComponent(n)},onOptionSelect:function(t){this.setState(t.data)},onFinish:function(t){this.$graphic.removeClass("spinner").addClass("success"),setTimeout((function(){window.location=t?Craft.getUrl(t):Craft.getUrl("dashboard")}),750)},handleFatalError:function(t){var e=Craft.t("app","Status:")+" "+t.statusText+"\n\n"+Craft.t("app","Response:")+" "+t.responseText+"\n\n";this.setState({error:Craft.t("app","A fatal error has occurred:"),errorDetails:e,options:[{label:Craft.t("app","Troubleshoot"),url:"https://craftcms.com/knowledge-base/failed-updates"},{label:Craft.t("app","Send for help"),email:"support@craftcms.com"}]}),Craft.sendActionRequest("POST",this.actionPrefix+"/finish",{data:this.data})}})}()}();
//# sourceMappingURL=Updater.js.map