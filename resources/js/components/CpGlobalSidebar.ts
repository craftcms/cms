import {LitElement, type PropertyDeclaration} from 'lit';
import {queryAll, query, customElement, property} from 'lit/decorators.js';

@customElement('cp-global-sidebar')
class CpGlobalSidebar extends LitElement {
  @queryAll('craft-nav-item')
  items: any[];

  @query('#sidebar-trigger')
  trigger: HTMLElement | null;

  @property({reflect: true})
  state: 'expanded' | 'collapsed' = Craft.getCookie('sidebar') ?? 'expanded';

  override connectedCallback() {
    super.connectedCallback();

    if (this.trigger) {
      this.trigger.addEventListener('open', this.expand.bind(this));
      this.trigger.addEventListener('close', this.collapse.bind(this));
    }

    if (this.state === 'expanded') {
      this.expand();
    } else {
      this.collapse();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();

    if (this.trigger) {
      this.trigger.removeEventListener('open', this.expand.bind(this));
      this.trigger.removeEventListener('close', this.collapse.bind(this));
    }

    this.state = 'expanded';
  }

  itemHasTooltip(item) {
    return item.querySelector('craft-tooltip');
  }

  createTooltips() {
    this.items?.forEach((item) => item.setAttribute('icon-only', true));
  }

  destroyTooltips() {
    this.items?.forEach((item) => item.removeAttribute('icon-only'));
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

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}
