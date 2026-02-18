import{_ as o,r as m}from"./state.js";import{T as u,a as b,i as g,x as a,E as l}from"./lit-element.js";import{n as c}from"./property.js";const p={ATTRIBUTE:1,CHILD:2},h=t=>(...i)=>({_$litDirective$:t,values:i});let f=class{constructor(i){}get _$AU(){return this._$AM._$AU}_$AT(i,n,e){this._$Ct=i,this._$AM=n,this._$Ci=e}_$AS(i,n){return this.update(i,n)}update(i,n){return this.render(...n)}};const y=h(class extends f{constructor(t){if(super(t),t.type!==p.ATTRIBUTE||t.name!=="class"||t.strings?.length>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter((i=>t[i])).join(" ")+" "}update(t,[i]){if(this.st===void 0){this.st=new Set,t.strings!==void 0&&(this.nt=new Set(t.strings.join(" ").split(/\s/).filter((e=>e!==""))));for(const e in i)i[e]&&!this.nt?.has(e)&&this.st.add(e);return this.render(i)}const n=t.element.classList;for(const e of this.st)e in i||(n.remove(e),this.st.delete(e));for(const e in i){const r=!!i[e];r===this.st.has(e)||this.nt?.has(e)||(r?(n.add(e),this.st.add(e)):(n.remove(e),this.st.delete(e)))}return u}});const v="important",$=" !"+v,_=h(class extends f{constructor(t){if(super(t),t.type!==p.ATTRIBUTE||t.name!=="style"||t.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(t){return Object.keys(t).reduce(((i,n)=>{const e=t[n];return e==null?i:i+`${n=n.includes("-")?n:n.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${e};`}),"")}update(t,[i]){const{style:n}=t.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(i)),this.render(i);for(const e of this.ft)i[e]==null&&(this.ft.delete(e),e.includes("-")?n.removeProperty(e):n[e]=null);for(const e in i){const r=i[e];if(r!=null){this.ft.add(e);const d=typeof r=="string"&&r.endsWith($);e.includes("-")||d?n.setProperty(e,d?r.slice(0,-11):r,d?v:""):n[e]=r}}return u}});var x=b`
  :host {
    --_padding-inline: var(--c-spacing-md);
    --_padding-block: var(--c-spacing-sm);
  }

  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    border-radius: var(--c-radius-md);
    position: relative;
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

  .indicator {
    display: inline-block;
    aspect-ratio: 1;
    width: calc(6rem / 16);
    border-radius: var(--c-radius-full);
    background-color: var(--c-color-accent-bg-emphasis);
    border: 1px solid var(--c-color-accent-border-emphasis);
    outline: 2px solid Canvas;
    position: absolute;
    inset-inline-end: 0;
    inset-block-end: 0;
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
`,s=class extends g{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.flush=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(t){t.preventDefault(),t.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(t){const i=`item-${this.id}`;return a`
      <a
        class="nav-item nav-item--icon"
        id="${i}"
        href="${this.href}"
        aria-current="${this.active?"page":!1}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(t)}
      </a>
      <c-tooltip for="${i}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderPrefix(){return a`
      <span class="nav-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon?a` <craft-icon
                  name="${this.icon}"
                  class="nav-icon"
                ></craft-icon>`:l}
          </slot>
          ${this.indicator?a`<span class="indicator"></span>`:l}
        </slot>
      </span>
    `}renderSuffix(t=!1){return a`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${t?a`
                <craft-button
                  @click="${this.toggleSubnav}"
                  icon
                  size="small"
                  aria-controls="${this.id}-subnav"
                  aria-expanded="${this.subnavState==="open"?"true":"false"}"
                >
                  <craft-icon
                    name="${this.subnavState==="closed"?"chevron-down":"chevron-up"}"
                    style="font-size: calc(10rem / 16)"
                  ></craft-icon>
                </craft-button>
              `:l}
        </slot>
      </div>
    `}renderItem(t,i=!1){return a`
      <a
        class="${y({"nav-item":!0,"nav-item--prefixed":i,"nav-item--flush":this.flush})}"
        href="${this.href}"
        aria-current="${this.active?"page":!1}"
      >
        ${i?this.renderPrefix():l}
        <slot></slot>
        ${this.renderSuffix(t)}
      </a>
    `}render(){const t=!!this.querySelector('[slot="subnav"]'),i=!!this.icon||!!this.querySelector('[slot="prefix"]')||!!this.querySelector('[slot="icon"]');return a`
      <li>
        ${this.iconOnly?this.renderIconItem(t):this.renderItem(t,i)}
        ${t?a`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${_({display:this.subnavState==="open"?"block":"none"})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:l}
      </li>
    `}};s.styles=x;o([c()],s.prototype,"icon",void 0);o([c()],s.prototype,"href",void 0);o([c({type:Boolean,reflect:!0})],s.prototype,"active",void 0);o([c({type:Boolean})],s.prototype,"external",void 0);o([c({type:Boolean})],s.prototype,"indicator",void 0);o([c()],s.prototype,"id",void 0);o([c({reflect:!0,type:Boolean,attribute:"icon-only"})],s.prototype,"iconOnly",void 0);o([c()],s.prototype,"flush",void 0);o([m()],s.prototype,"subnavState",void 0);customElements.get("craft-nav-item")||customElements.define("craft-nav-item",s);const T=Object.freeze(Object.defineProperty({__proto__:null,default:s},Symbol.toStringTag,{value:"Module"}));export{h as a,y as e,f as i,T as n,_ as o,p as t};
