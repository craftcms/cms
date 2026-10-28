import {LitElement} from 'lit';
import {customElement, property, query, queryAll} from 'lit/decorators.js';

@customElement('cp-global-sidebar')
// oxlint-disable-next-line @typescript-eslint/no-unused-vars
class CpGlobalSidebar extends LitElement {
  @queryAll('craft-nav-item')
  items!: any[];

  @query('#sidebar-trigger')
  trigger!: HTMLElement | null;

  @property({reflect: true})
  state: 'expanded' | 'collapsed' | string =
    Craft.getCookie('sidebar') ?? 'expanded';

  override connectedCallback() {
    super.connectedCallback();

    if (this.trigger) {
      this.trigger.addEventListener('craft-show', this.#handleShow);
      this.trigger.addEventListener('craft-hide', this.#handleHide);
    }

    if (this.state === 'expanded') {
      this.expand();
    } else {
      this.collapse();
    }
  }

  override disconnectedCallback() {
    if (this.trigger) {
      this.trigger.removeEventListener('craft-show', this.#handleShow);
      this.trigger.removeEventListener('craft-hide', this.#handleHide);
    }

    this.state = 'expanded';

    super.disconnectedCallback();
  }

  #handleShow = () => this.expand();

  #handleHide = () => this.collapse();

  itemHasTooltip(item: HTMLElement) {
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
