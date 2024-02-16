class CraftGlobalSidebar extends HTMLElement {
  connectedCallback() {
    this.trigger = this.querySelector('#sidebar-trigger');

    if (this.trigger) {
      this.trigger.addEventListener('open', this.expand.bind(this));
      this.trigger.addEventListener('close', this.collapse.bind(this));
    }
  }

  expand() {
    document.body.setAttribute('data-sidebar', 'expanded');
    Craft.setCookie('sidebar', 'expanded');
  }

  collapse() {
    document.body.setAttribute('data-sidebar', 'collapsed');
    Craft.setCookie('sidebar', 'collapsed');
  }
}

customElements.define('craft-global-sidebar', CraftGlobalSidebar);
