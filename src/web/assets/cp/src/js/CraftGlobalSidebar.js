class CraftGlobalSidebar extends HTMLElement {
  get items() {
    return this.querySelectorAll('.sidebar-action');
  }

  connectedCallback() {
    this.trigger = this.querySelector('#sidebar-trigger');

    if (this.trigger) {
      this.trigger.addEventListener('open', this.expand.bind(this));
      this.trigger.addEventListener('close', this.collapse.bind(this));
    }
  }

  disconnectedCallback() {
    if (this.trigger) {
      this.trigger.removeEventListener('open', this.expand.bind(this));
      this.trigger.removeEventListener('close', this.collapse.bind(this));
    }

    this.expand();
  }

  createTooltips() {
    if (this.items) {
      this.items.forEach((item) => {
        const tooltip = document.createElement('craft-tooltip');
        tooltip.setAttribute('placement', 'right');
        tooltip.setAttribute('trigger', `.sidebar-action`);
        tooltip.setAttribute('text', item.querySelector('.label')?.innerText);
        item.append(tooltip);
      });
    }
  }

  destroyTooltips() {
    if (this.items) {
      this.items.forEach((item) => {
        const tooltip = item.querySelector('craft-tooltip');
        tooltip?.remove();
      });
    }
  }

  expand() {
    document.body.setAttribute('data-sidebar', 'expanded');
    Craft.setCookie('sidebar', 'expanded');
    this.destroyTooltips();
  }

  collapse() {
    document.body.setAttribute('data-sidebar', 'collapsed');
    Craft.setCookie('sidebar', 'collapsed');
    this.createTooltips();
  }
}

customElements.define('craft-global-sidebar', CraftGlobalSidebar);
