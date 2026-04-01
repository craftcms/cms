import{t as e}from"./decorate-D0rs42HP.js";import{c as t,f as n,i as r,r as i,t as a}from"./lit.js";import{a as o,i as s}from"./decorators.js";var c={ATTRIBUTE:1,CHILD:2,PROPERTY:3,BOOLEAN_ATTRIBUTE:4,EVENT:5,ELEMENT:6},l=e=>(...t)=>({_$litDirective$:e,values:t}),u=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,t,n){this._$Ct=e,this._$AM=t,this._$Ci=n}_$AS(e,t){return this.update(e,t)}update(e,t){return this.render(...t)}},d=l(class extends u{constructor(e){if(super(e),e.type!==c.ATTRIBUTE||e.name!==`class`||e.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(e){return` `+Object.keys(e).filter((t=>e[t])).join(` `)+` `}update(e,[t]){if(this.st===void 0){this.st=new Set,e.strings!==void 0&&(this.nt=new Set(e.strings.join(` `).split(/\s/).filter((e=>e!==``))));for(let e in t)t[e]&&!this.nt?.has(e)&&this.st.add(e);return this.render(t)}let n=e.element.classList;for(let e of this.st)e in t||(n.remove(e),this.st.delete(e));for(let e in t){let r=!!t[e];r===this.st.has(e)||this.nt?.has(e)||(r?(n.add(e),this.st.add(e)):(n.remove(e),this.st.delete(e)))}return r}}),f=n`
  :host(:not(:focus-within)) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    border: none !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
  }
`,p=n`
  :host {
    box-sizing: border-box;
  }

  :host *,
  :host *::before,
  :host *::after {
    box-sizing: inherit;
  }

  [hidden] {
    display: none !important;
  }
`,m=Object.defineProperty,h=Object.getOwnPropertyDescriptor,g=Object.getOwnPropertySymbols,_=Object.prototype.hasOwnProperty,v=Object.prototype.propertyIsEnumerable,y=e=>{throw TypeError(e)},b=(e,t,n)=>t in e?m(e,t,{enumerable:!0,configurable:!0,writable:!0,value:n}):e[t]=n,x=(e,t)=>{for(var n in t||={})_.call(t,n)&&b(e,n,t[n]);if(g)for(var n of g(t))v.call(t,n)&&b(e,n,t[n]);return e},S=(e,t,n,r)=>{for(var i=r>1?void 0:r?h(t,n):t,a=e.length-1,o;a>=0;a--)(o=e[a])&&(i=(r?o(t,n,i):o(i))||i);return r&&i&&m(t,n,i),i},C=(e,t,n)=>t.has(e)||y(`Cannot `+n),w=(e,t,n)=>(C(e,t,`read from private field`),n?n.call(e):t.get(e)),T=(e,t,n)=>t.has(e)?y(`Cannot add the same private member more than once`):t instanceof WeakSet?t.add(e):t.set(e,n),E=(e,t,n,r)=>(C(e,t,`write to private field`),r?r.call(e,n):t.set(e,n),n),D,O=class extends a{constructor(){super(),T(this,D,!1),this.initialReflectedProperties=new Map,Object.entries(this.constructor.dependencies).forEach(([e,t])=>{this.constructor.define(e,t)})}emit(e,t){let n=new CustomEvent(e,x({bubbles:!0,cancelable:!1,composed:!0,detail:{}},t));return this.dispatchEvent(n),n}static define(e,t=this,n={}){let r=customElements.get(e);if(!r){try{customElements.define(e,t,n)}catch{customElements.define(e,class extends t{},n)}return}let i=` (unknown version)`,a=i;`version`in t&&t.version&&(i=` v`+t.version),`version`in r&&r.version&&(a=` v`+r.version),!(i&&a&&i===a)&&console.warn(`Attempted to register <${e}>${i}, but <${e}>${a} has already been registered.`)}attributeChangedCallback(e,t,n){w(this,D)||(this.constructor.elementProperties.forEach((e,t)=>{e.reflect&&this[t]!=null&&this.initialReflectedProperties.set(t,this[t])}),E(this,D,!0)),super.attributeChangedCallback(e,t,n)}willUpdate(e){super.willUpdate(e),this.initialReflectedProperties.forEach((t,n)=>{e.has(n)&&this[n]==null&&(this[n]=t)})}};D=new WeakMap,O.version=`2.20.1`,O.dependencies={},S([o()],O.prototype,`dir`,2),S([o()],O.prototype,`lang`,2);var k=class extends O{render(){return t` <slot></slot> `}};k.styles=[p,f],k.define(`sl-visually-hidden`);var A=n`
  .badge-indicator {
    --badge-color: var(--c-color-accent-fill-loud);
    --text-color: white;
    --badge-size: calc(8rem / 16);
    display: inline-flex;
    min-width: var(--badge-size);
    min-height: var(--badge-size);
    justify-content: center;
    align-items: center;
    background-color: var(--badge-color);
    color: var(--text-color);
    border-radius: var(--c-radius-full);
    border: 2px solid var(--c-text-white);
  }

  .badge-indicator--secondary {
    --badge-color: var(--c-color-brand-fill-loud);
  }

  .badge-indicator--inverse {
    --badge-color: var(--c-color-neutral-fill-normal);
    --text-color: var(--c-text-default);
  }

  .badge-indicator--with-number {
    --badge-size: var(--c-size-icon-md);
    padding: calc(2rem / 16);
  }

  .number {
    display: inline-flex;
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
  }
`,j=class extends a{constructor(){super(),this.altText=null,this.badgeCount=null,this.badgeCountSuffix=null,this.variant=`primary`,this.id=this.id||`badge-${Math.floor(Math.random()*1e9).toString()}`}showCount(){return this.badgeCount!==null&&this.badgeCount>0}truncatedNumber(){if(this.showCount)return this.badgeCount>99?`99+`:this.badgeCount.toString()}getBadgeRole(){return this.altText?`img`:i}getLabelId(){return`${this.id}-label`}renderBadgeContents(){return t`
      ${this.showCount()?t`
            <span class="number">${this.truncatedNumber()}</span>
            <sl-visually-hidden>${this.badgeCountSuffix}</sl-visually-hidden>
          `:i}
      ${this.altText?t`
            <sl-visually-hidden id=${this.getLabelId()}
              >${this.altText}</sl-visually-hidden
            >
          `:i}
    `}render(){return t`
      <div
        part="badge"
        id=${this.id}
        class="${d({"badge-indicator":!0,"badge-indicator--with-number":this.showCount(),"badge-indicator--secondary":this.variant===`secondary`,"badge-indicator--inverse":this.variant===`inverse`})}"
        role="${this.getBadgeRole()}"
        aria-labelledby="${this.altText?this.getLabelId():i}"
      >
        ${this.renderBadgeContents()}
      </div>
    `}};j.styles=[A],e([o()],j.prototype,`altText`,void 0),e([o()],j.prototype,`badgeCount`,void 0),e([o()],j.prototype,`badgeCountSuffix`,void 0),e([o()],j.prototype,`variant`,void 0),e([o()],j.prototype,`id`,void 0),customElements.get(`craft-badge-indicator`)||customElements.define(`craft-badge-indicator`,j);function M(e,t){if(typeof d3<`u`&&typeof d3FormatLocaleDefinition<`u`)return t===void 0&&(t=`,.0f`),d3.formatLocale(d3FormatLocaleDefinition).format(t)(e);let n=typeof e==`string`?parseFloat(e):e;if(isNaN(n))return String(e);if(t){let e=t.includes(`,`),r=t.match(/\.(\d+)/),i=r?parseInt(r[1],10):0;return new Intl.NumberFormat(`en-US`,{useGrouping:e,minimumFractionDigits:i,maximumFractionDigits:i}).format(n)}return new Intl.NumberFormat(`en-US`,{useGrouping:!0,minimumFractionDigits:0,maximumFractionDigits:0}).format(n)}function N(e){let t=1,n,r,i=[...e];if((n=r=i.indexOf(`{`))===-1)return[e];let a=[i.slice(0,r).join(``)];for(;;){let e=i.indexOf(`{`,r+1),o=i.indexOf(`}`,r+1);if(e===-1&&o===-1||(e===-1&&(e=i.length),o!==-1&&o>e?(t++,r=e):o!==-1&&(t--,r=o),t===0&&(a.push(i.slice(n+1,r).join(``).split(`,`,3)),n=r+1,a.push(i.slice(n,e===-1?i.length:e).join(``)),n=e===-1?i.length:e),t!==0&&(e===-1||o===-1)))break}return t===0?a:!1}function P(e,t={}){let n=e[0]?.trim();if(!n||t[n]===void 0)return`{${e.join(`,`)}}`;let r=t[n],i=e[1]===void 0?`none`:e[1].trim();switch(i){case`number`:return(()=>{let t=e[2]===void 0?null:e[2].trim();if(t!==null&&t!==`integer`)throw`Message format 'number' is only supported for integer values.`;let n=M(r),i;return t===null&&(i=`${r}`.indexOf(`.`))!==-1&&(n+=`.${r.substring(i+1)}`),n})();case`none`:return r;case`select`:return(()=>{if(e[2]===void 0)return!1;let n=N(e[2]);if(n===!1)return!1;let i=n.length,a=!1;for(let e=0;e+1<i;e++){if(Array.isArray(n[e])||!Array.isArray(n[e+1]))return!1;let t=n[e++].trim();(a===!1&&t===`other`||t==r)&&(a=n[e].join(`,`))}return a===!1?!1:F(a,t)})();case`plural`:return(()=>{if(e[2]===void 0)return!1;let n=N(e[2]);if(n===!1)return!1;let i=n.length,a=!1,o=0;for(let e=0;e+1<i;e++){if(typeof n[e]==`object`||typeof n[e+1]!=`object`)return!1;let t=n[e++].trim(),i=[...t];if(e===1&&t.substring(0,7)===`offset:`){let e=[...t.replace(/[\n\r\t]/g,` `)].indexOf(` `,7);if(e===-1)throw Error(`Message pattern is invalid.`);o=parseInt(i.slice(7,e).join(``).trim()),t=i.slice(e+1,e+1+i.length).join(``).trim()}if(a===!1&&t===`other`||t[0]===`=`&&parseInt(i.slice(1,1+i.length).join(``))===r||t===`one`&&r-o===1){let t=n[e];a=(typeof t==`string`?[t]:t).map(e=>e.replace(`#`,String(r-o))).join(`,`)}}return a===!1?!1:F(a,t)})();default:throw Error(`Message format '${i}' is not supported.`)}}function F(e,t){let n;if((n=N(e))===!1)throw Error(`Message pattern is invalid.`);for(let e=0;e<n.length;e++){let r=n[e];if(typeof r==`object`){let i=P(r,t);if(i===!1)throw Error(`Message pattern is invalid.`);n[e]=String(i)}}return n.join(``)}function I(e,t,n=`app`,r){return r&&r[n]!==void 0&&r[n][e]!==void 0&&(e=r[n][e]),t?F(e,t):e}var L=`important`,R=` !`+L,z=l(class extends u{constructor(e){if(super(e),e.type!==c.ATTRIBUTE||e.name!==`style`||e.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(e){return Object.keys(e).reduce(((t,n)=>{let r=e[n];return r==null?t:t+`${n=n.includes(`-`)?n:n.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,`-$&`).toLowerCase()}:${r};`}),``)}update(e,[t]){let{style:n}=e.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(t)),this.render(t);for(let e of this.ft)t[e]??(this.ft.delete(e),e.includes(`-`)?n.removeProperty(e):n[e]=null);for(let e in t){let r=t[e];if(r!=null){this.ft.add(e);let t=typeof r==`string`&&r.endsWith(R);e.includes(`-`)||t?n.setProperty(e,t?r.slice(0,-11):r,t?L:``):n[e]=r}}return r}}),B=n`
  :host {
    --_padding-inline: var(--c-spacing-md);
    --_padding-block: var(--c-spacing-sm);
  }

  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: auto 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    border-radius: var(--c-radius-md);
    position: relative;
  }
  
  craft-badge-indicator {
      position: absolute;
      inset-inline-end: 0;
      inset-block-end: 0;
    }
  }

  .nav-item--prefixed {
    padding-inline: var(--c-spacing-sm);
    grid-template-columns: calc(24rem / 16) 1fr auto;
  }

  .nav-item--flush {
    margin-inline-start: calc(var(--_padding-inline) * -1);
  }

  :host([active]) .nav-item {
    &:before {
      content: '';
      position: absolute;
      inset-inline-start: 0;
      inset-block-start: 12%;
      width: calc(3rem / 16);
      height: 76%;
      border-radius: calc(2rem / 16);
      background-color: currentColor;
      transform: translateX(-150%);
    }
  }

  .nav-item:hover:not(:has(craft-button:hover)) {
    background-color: color-mix(in srgb, currentColor, transparent 95%);
  }

  .nav-item__prefix {
    position: relative;
    display: grid;
    justify-content: center;
    align-items: center;
    aspect-ratio: 1;
    width: 100%;
  }

  .active-indicator {
    display: inline-block;
    aspect-ratio: 1;
    width: calc(4rem / 16);
    border-radius: var(--c-radius-full);
    background-color: currentColor;

    :host([active]) & {
      width: calc(6rem / 16);
    }
  }

  .subnav {
    margin-block-start: var(--c-spacing-sm);
    margin-inline-start: calc(
      (var(--c-size-icon-md) / 2) + var(--c-spacing-sm) + 1px
    );
    padding-inline: var(--c-spacing-sm);
    border-left: 2px solid color-mix(in srgb, currentColor, transparent 90%);
  }

  :host([icon-only]) {
    .nav-item {
      gap: 0;
      grid-template-columns: calc(24rem / 16);
    }

    .nav-item__suffix {
      display: grid;
      justify-content: center;
      align-items: center;
    }

    .subnav {
      margin: 0;
      border-left: none;
      padding-inline: 0;
    }
  }
`,V=class extends a{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.flush=!1,this.subnavState=`closed`,this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?`open`:`closed`}toggleSubnav(e){e.preventDefault(),e.stopPropagation(),this.subnavState=this.subnavState===`open`?`closed`:`open`}renderIconItem(e){let n=`item-${this.id}`;return t`
      <a
        class="nav-item nav-item--icon"
        id="${n}"
        href="${this.href}"
        aria-current="${this.active?`page`:!1}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(e)}
      </a>
      <c-tooltip for="${n}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderPrefix(){return t`
      <span class="nav-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?t` <craft-icon
                  name="${this.icon}"
                  class="nav-icon"
                ></craft-icon>`:i}
          </slot>
          ${this.indicator?t`<craft-badge-indicator
                altText="${I(`Has Notifications`)}"
              />`:i}
        </slot>
      </span>
    `}renderSuffix(e=!1){return t`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${e?t`
                  <craft-button
                    @click="${this.toggleSubnav}"
                    appearance="plain"
                  icon
                  size="small"
                  aria-controls="${this.id}-subnav"
                  aria-expanded="${this.subnavState===`open`?`true`:`false`}"
                    aria-labelledby="${this.id}-toggle-icon ${this.id}-label"
                  >
                    <craft-icon
                      id="${this.id}-toggle-icon""
                      name="${this.subnavState===`closed`?`chevron-down`:`chevron-up`}"
                      style="font-size: calc(10rem / 16)"
                      label="${I(`Toggle subnavigation`)}"
                  ></craft-icon>
                </craft-button>
              `:i}
        </slot>
      </div>
    `}renderItem(e,n=!1){return t`
      <a
        class="${d({"nav-item":!0,"nav-item--prefixed":n,"nav-item--flush":this.flush})}"
        href="${this.href}"
        aria-current="${this.active?`page`:!1}"
      >
        ${n?this.renderPrefix():i}
        <slot id="${this.id}-label"></slot>
        ${this.renderSuffix(e)}
      </a>
    `}render(){let e=!!this.querySelector(`[slot="subnav"]`),n=!!this.icon||!!this.querySelector(`[slot="prefix"]`)||!!this.querySelector(`[slot="icon"]`);return t`
      <li>
        ${this.iconOnly?this.renderIconItem(e):this.renderItem(e,n)}
        ${e?t`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${z({display:this.subnavState===`open`?`block`:`none`})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:i}
      </li>
    `}};V.styles=B,e([o()],V.prototype,`icon`,void 0),e([o()],V.prototype,`href`,void 0),e([o({type:Boolean,reflect:!0})],V.prototype,`active`,void 0),e([o({type:Boolean})],V.prototype,`external`,void 0),e([o({type:Boolean})],V.prototype,`indicator`,void 0),e([o()],V.prototype,`id`,void 0),e([o({reflect:!0,type:Boolean,attribute:`icon-only`})],V.prototype,`iconOnly`,void 0),e([o()],V.prototype,`flush`,void 0),e([s()],V.prototype,`subnavState`,void 0),customElements.get(`craft-nav-item`)||customElements.define(`craft-nav-item`,V);export{l as a,d as i,z as n,u as o,I as r,c as s,V as t};