/**
 * Very simple disclosure trigger.
 *
 * Allows you to wrap a button[type="button"] and target an element to toggle the `is-open` class on.
 * Set `aria-expanded` on the button
 */
class CraftDisclosure extends HTMLElement {
  static observedAttributes = ['state'];

  get trigger() {
    return this.querySelector('button[type="button"]');
  }

  get target() {
    return document.getElementById(this.trigger.getAttribute('aria-controls'));
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
    this.state = this.getAttribute('state');

    if (!this.trigger.getAttribute('aria-expanded')) {
      this.trigger.setAttribute('aria-expanded', 'false');
    }

    this.trigger.addEventListener('click', this.toggle.bind(this));

    this.state === 'expanded' ? this.open() : this.close();
  }

  disconnectedCallback() {
    this.open();
    this.trigger.removeEventListener('click', this.toggle.bind(this));
  }

  attributeChangedCallback(name, oldValue, newValue) {
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
    this.trigger.setAttribute('aria-expanded', 'true');
    this.expanded = true;
    this.target.dataset.state = 'expanded';
    this.dispatchEvent(new CustomEvent('open'));

    if (this.cookieName) {
      Craft.setCookie(this.cookieName, 'expanded');
    }
  };

  open() {
    this.setAttribute('state', 'expanded');
  }

  handleClose = () => {
    this.trigger.setAttribute('aria-expanded', 'false');
    this.expanded = false;
    this.target.dataset.state = 'collapsed';
    this.dispatchEvent(new CustomEvent('close'));

    if (this.cookieName) {
      Craft.setCookie(this.cookieName, 'collapsed');
    }
  };

  close() {
    this.setAttribute('state', 'collapsed');
  }
}

customElements.define('craft-disclosure', CraftDisclosure);
