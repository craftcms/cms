import{e as o,r as u}from"./state.js";import{T as v,a as m,i as h,E as l,x as a}from"./lit-element.js";import{n as r}from"./property.js";const f={ATTRIBUTE:1,CHILD:2},b=t=>(...e)=>({_$litDirective$:t,values:e});let g=class{constructor(e){}get _$AU(){return this._$AM._$AU}_$AT(e,i,n){this._$Ct=e,this._$AM=i,this._$Ci=n}_$AS(e,i){return this.update(e,i)}update(e,i){return this.render(...i)}};const p="important",y=" !"+p,$=b(class extends g{constructor(t){if(super(t),t.type!==f.ATTRIBUTE||t.name!=="style"||t.strings?.length>2)throw Error("The `styleMap` directive must be used in the `style` attribute and must be the only part in the attribute.")}render(t){return Object.keys(t).reduce(((e,i)=>{const n=t[i];return n==null?e:e+`${i=i.includes("-")?i:i.replace(/(?:^(webkit|moz|ms|o)|)(?=[A-Z])/g,"-$&").toLowerCase()}:${n};`}),"")}update(t,[e]){const{style:i}=t.element;if(this.ft===void 0)return this.ft=new Set(Object.keys(e)),this.render(e);for(const n of this.ft)e[n]==null&&(this.ft.delete(n),n.includes("-")?i.removeProperty(n):i[n]=null);for(const n in e){const c=e[n];if(c!=null){this.ft.add(n);const d=typeof c=="string"&&c.endsWith(y);n.includes("-")||d?i.setProperty(n,d?c.slice(0,-11):c,d?p:""):i[n]=c}}return v}});var _=m`
  .nav-item {
    display: grid;
    gap: var(--c-spacing-md);
    grid-template-columns: calc(24rem / 16) 1fr auto;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
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
      transform: translateX(-200%);
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
`,s=class extends h{constructor(){super(),this.active=!1,this.external=!1,this.indicator=!1,this.iconOnly=!1,this.subnavState="closed",this.id=this.id||Math.random().toString(36).substring(2,6)}connectedCallback(){super.connectedCallback(),this.subnavState=this.active?"open":"closed"}toggleSubnav(t){t.preventDefault(),t.stopPropagation(),this.subnavState=this.subnavState==="open"?"closed":"open"}renderIconItem(t){let e=`item-${this.id}`;return a`
      <a
        class="nav-item"
        id="${e}"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?a` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:a` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?a`<span class="indicator"></span>`:l}
          </slot>
        </span>

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
      </a>
      <c-tooltip for="${e}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `}renderItem(t){return a`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active?"page":!1}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon?a` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`:a` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator?a`<span class="indicator"></span>`:l}
          </slot>
        </span>
        <slot></slot>

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
      </a>
    `}render(){let t=!!this.querySelector('[slot="subnav"]');return a`
      <li>
        ${this.iconOnly?this.renderIconItem(t):this.renderItem(t)}
        ${t?a`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${$({display:this.subnavState==="open"?"block":"none"})}"
              >
                <slot name="subnav"></slot>
              </div>
            `:l}
      </li>
    `}};s.styles=_,o([r()],s.prototype,"icon",void 0),o([r()],s.prototype,"url",void 0),o([r({type:Boolean,reflect:!0})],s.prototype,"active",void 0),o([r({type:Boolean})],s.prototype,"external",void 0),o([r({type:Boolean})],s.prototype,"indicator",void 0),o([r()],s.prototype,"id",void 0),o([r({reflect:!0,type:Boolean,attribute:"icon-only"})],s.prototype,"iconOnly",void 0),o([u()],s.prototype,"subnavState",void 0),customElements.get("craft-nav-item")||customElements.define("craft-nav-item",s);const C=Object.freeze(Object.defineProperty({__proto__:null,default:s},Symbol.toStringTag,{value:"Module"}));export{b as e,g as i,C as n,f as t};
