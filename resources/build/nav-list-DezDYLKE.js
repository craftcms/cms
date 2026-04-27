import{c as e,f as t,t as n}from"./lit.js";var r=class extends n{render(){return e`
      <ul class="nav-list">
        <slot></slot>
      </ul>
    `}};r.styles=t`
    :host {
      display: block;
    }

    .nav-list {
      display: grid;
      margin: 0;
      padding: 0;
      list-style: none;
    }
  `,customElements.get(`craft-nav-list`)||customElements.define(`craft-nav-list`,r);export{r as t};