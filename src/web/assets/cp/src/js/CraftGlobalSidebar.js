class CraftGlobalSidebar extends HTMLElement {
  connectedCallback() {
    this.trigger = this.querySelector('#sidebar-trigger');

    if (this.trigger) {
      this.trigger.addEventListener('click', this.toggle.bind(this));
    }
  }

  toggle() {
    if (document.body.getAttribute('data-sidebar') === 'expanded') {
      this.collapse();
    } else {
      this.expand();
    }
  }

  expand() {
    document.body.setAttribute('data-sidebar', 'expanded');
    Craft.setCookie('sidebar', 'expanded');
    this.trigger.setAttribute('aria-expanded', 'true');
  }

  collapse() {
    document.body.setAttribute('data-sidebar', 'collapsed');
    Craft.setCookie('sidebar', 'collapsed');
    this.trigger.setAttribute('aria-expanded', 'false');
  }
}

customElements.define('craft-global-sidebar', CraftGlobalSidebar);
