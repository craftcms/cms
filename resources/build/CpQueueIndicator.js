import{i as w,E as c,x as h,a as $}from"./lit-element.js";import{t as q}from"./custom-element.js";import{r as E}from"./state.js";import{J as v}from"./legacy.js";import"./progress-CM21Jrvu.js";import"./property.js";import"./index2.js";var Q=Object.defineProperty,x=Object.getOwnPropertyDescriptor,_=e=>{throw TypeError(e)},y=(e,t,s,n)=>{for(var i=n>1?void 0:n?x(t,s):t,d=e.length-1,p;d>=0;d--)(p=e[d])&&(i=(n?p(t,s,i):p(i))||i);return n&&i&&Q(t,s,i),i},g=(e,t,s)=>t.has(e)||_("Cannot "+s),r=(e,t,s)=>(g(e,t,"read from private field"),s?s.call(e):t.get(e)),f=(e,t,s)=>t.has(e)?_("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,s),b=(e,t,s)=>(g(e,t,"access private method"),s),l,a,u,m,J,C;let o=class extends w{constructor(){super(...arguments),f(this,a),this.displayedJob=null,f(this,l,e=>{console.log("handling update"),this.displayedJob=e.detail.displayedJob,b(this,a,u).call(this)})}connectedCallback(){super.connectedCallback(),window.Craft?.$queue?.addEventListener("job-update",r(this,l)),b(this,a,u).call(this)}disconnectedCallback(){super.disconnectedCallback(),window.Craft?.$queue?.removeEventListener("job-update",r(this,l))}render(){if(!this.displayedJob)return c;const e=r(this,a,C);return h`
      <craft-nav-item .url=${e}>
        <craft-progress
          slot="prefix"
          progress=${r(this,a,m)}
          ?failed=${r(this,a,J)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?h`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:c}
        </div>
      </craft-nav-item>
    `}};l=new WeakMap;a=new WeakSet;u=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};m=function(){return this.displayedJob?this.displayedJob.status===v.Failed?100:this.displayedJob.progress??0:0};J=function(){return this.displayedJob?.status===v.Failed};C=function(){return window.Craft?.$queue?.canAccessQueueManager?window.Craft?.getUrl?window.Craft.getUrl("utilities/queue-manager"):"/admin/utilities/queue-manager":null};o.styles=$`
    :host {
      display: contents;
    }

    :host(:not([visible])) {
      display: none;
    }

    .progress-label {
      font-size: 0.85em;
      opacity: 0.7;
    }
  `;y([E()],o.prototype,"displayedJob",2);o=y([q("cp-queue-indicator")],o);const F=o;export{F as default};
