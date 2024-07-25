class CraftGlobalSidebar extends HTMLElement {
  connectedCallback() {
    this.trigger = this.querySelector('#sidebar-trigger');

    if (this.trigger) {
      this.trigger.addEventListener('open', this.expand.bind(this));
      this.trigger.addEventListener('close', this.collapse.bind(this));
    }

    this.items = this.querySelectorAll('.sidebar-action');
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
        tooltip.setAttribute('self-managed', 'true');
        tooltip.setAttribute(
          'aria-label',
          item.querySelector('.label')?.innerText
        );
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
