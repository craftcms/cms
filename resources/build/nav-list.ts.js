import{a as t,i as a,x as l}from"./lit-element.js";var s=class extends a{render(){return l`
      <ul class="nav-list">
        <slot></slot>
      </ul>
    `}};s.styles=t`
    :host {
      display: block;
    }

    .nav-list {
      display: grid;
      margin: 0;
      padding: 0;
      list-style: none;
    }
  `;customElements.get("craft-nav-list")||customElements.define("craft-nav-list",s);export{s as default};
