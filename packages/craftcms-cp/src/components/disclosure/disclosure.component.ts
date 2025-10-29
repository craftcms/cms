/**
 * Very simple disclosure trigger.
 *
 * Allows you to wrap a button[type="button"] and target an element to toggle the `data-state` attribute on.
 * Set `aria-expanded` on the button
 */
export default class CraftDisclosure extends HTMLElement {
  static observedAttributes = ['state'];
  private cookieName: string | null = null;
  private state: string = 'collapsed';
  private expanded: boolean = false;

  get trigger() {
    return this.querySelector('button[type="button"]');
  }

  get target() {
    if (!this.trigger) {
      console.warn('No trigger found for disclosure.');
      return null;
    }

    const targetSelector = this.trigger.getAttribute('aria-controls');
    if (!targetSelector) {
      console.warn('No target selector found for disclosure.');
      return null;
    }

    return document.getElementById(targetSelector);
  }

  connectedCallback() {
    if (!this.trigger) {
      console.error(`craft-disclosure elements must include a button`, this);
      return;
    }

    if (!this.target) {
      console.error(
        `No target with id ${this.trigger.getAttribute(
          'aria-controls'
        )} found for disclosure. `,
        this.trigger
      );
      return;
    }

    this.cookieName = this.getAttribute('cookie-name');
    this.state = this.getAttribute('state') ?? 'expanded';

    this.trigger.setAttribute(
      'aria-expanded',
      this.state === 'expanded' ? 'true' : 'false'
    );

    this.trigger.addEventListener('click', this.toggle.bind(this));

    this.state === 'expanded' ? this.open() : this.close();
  }

  disconnectedCallback() {
    this.open();
    this.trigger?.removeEventListener('click', this.toggle.bind(this));
  }

  attributeChangedCallback(name: string, oldValue: string, newValue: string) {
    if (name === 'state') {
      if (newValue === 'expanded') {
        this.handleOpen();
      } else {
        this.handleClose();
      }
    }
  }

  toggle() {
    if (this.expanded) {
      this.close();
    } else {
      this.open();
    }
  }

  handleOpen = () => {
    this.trigger?.setAttribute('aria-expanded', 'true');
    this.expanded = true;
    this.dispatchEvent(new CustomEvent('open'));

    if (this.target) {
      this.target.dataset.state = 'expanded';
    }

    if (this.cookieName) {
      window.Craft?.setCookie(this.cookieName, 'expanded');
    }
  };

  open() {
    this.setAttribute('state', 'expanded');
  }

  handleClose = () => {
    this.trigger?.setAttribute('aria-expanded', 'false');
    this.expanded = false;
    this.dispatchEvent(new CustomEvent('close'));

    if (this.target) {
      this.target.dataset.state = 'collapsed';
    }

    if (this.cookieName) {
      window.Craft?.setCookie(this.cookieName, 'collapsed');
    }
  };

  close() {
    this.setAttribute('state', 'collapsed');
  }
}
