import{i as w,E as c,x as h,a as $}from"./lit-element.js";import{t as q}from"./property.js";import{r as x}from"./progress-DOMF4PIT.js";import{J as v}from"./legacy.js";import"./index2.js";var E=Object.defineProperty,Q=Object.getOwnPropertyDescriptor,_=e=>{throw TypeError(e)},y=(e,t,s,o)=>{for(var a=o>1?void 0:o?Q(t,s):t,d=e.length-1,p;d>=0;d--)(p=e[d])&&(a=(o?p(t,s,a):p(a))||a);return o&&a&&E(t,s,a),a},g=(e,t,s)=>t.has(e)||_("Cannot "+s),r=(e,t,s)=>(g(e,t,"read from private field"),s?s.call(e):t.get(e)),f=(e,t,s)=>t.has(e)?_("Cannot add the same private member more than once"):t instanceof WeakSet?t.add(e):t.set(e,s),b=(e,t,s)=>(g(e,t,"access private method"),s),l,i,u,J,m,C;let n=class extends w{constructor(){super(...arguments),f(this,i),this.displayedJob=null,f(this,l,e=>{console.log("handling update"),this.displayedJob=e.detail.displayedJob,b(this,i,u).call(this)})}connectedCallback(){super.connectedCallback(),window.Craft?.$queue?.addEventListener("job-update",r(this,l)),b(this,i,u).call(this)}disconnectedCallback(){super.disconnectedCallback(),window.Craft?.$queue?.removeEventListener("job-update",r(this,l))}render(){if(!this.displayedJob)return c;const e=r(this,i,C);return h`
      <craft-nav-item .url=${e}>
        <craft-progress
          slot="prefix"
          progress=${r(this,i,J)}
          ?failed=${r(this,i,m)}
          label=${this.displayedJob.description||"Queue"}
        ></craft-progress>
        <div class="label">
          <span class="title">${this.displayedJob.description}</span>
          ${this.displayedJob.progressLabel?h`<span class="progress-label"
                >${this.displayedJob.progressLabel}</span
              >`:c}
        </div>
      </craft-nav-item>
    `}};l=new WeakMap;i=new WeakSet;u=function(){this.displayedJob?this.setAttribute("visible",""):this.removeAttribute("visible")};J=function(){return this.displayedJob?this.displayedJob.status===v.Failed?100:this.displayedJob.progress??0:0};m=function(){return this.displayedJob?.status===v.Failed};C=function(){return window.Craft?.$queue?.canAccessQueueManager?window.Craft?.getUrl?window.Craft.getUrl("utilities/queue-manager"):"/admin/utilities/queue-manager":null};n.styles=$`
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
  `;y([x()],n.prototype,"displayedJob",2);n=y([q("cp-queue-indicator")],n);const U=n;export{U as default};
