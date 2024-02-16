/**
 * Very simple disclosure trigger.
 *
 * Allows you to wrap a button[type="button"] and target an element to toggle the `is-open` class on.
 * Set `aria-expanded` on the button
 */
class CraftDisclosure extends HTMLElement {
  connectedCallback() {
    this.trigger = this.querySelector('button[type="button"]');
    if (!this.trigger) {
      console.error(`craft-disclosure elements must include a button`, this);
      return;
    }

    this.target = document.getElementById(
      this.trigger.getAttribute('aria-controls')
    );
    if (!this.target) {
      console.error(
        `No target with id ${this.trigger.getAttribute(
          'aria-controls'
        )} found for disclosure. `,
        this.trigger
      );
      return;
    }

    if (!this.trigger.getAttribute('aria-expanded')) {
      this.trigger.setAttribute('aria-expanded', 'false');
    }

    this.trigger.addEventListener('click', this.toggle.bind(this));

    this.expanded = this.trigger.getAttribute('aria-expanded') === 'true';
    this.expanded ? this.open() : this.close();
  }

  disconnectedCallback() {
    this.open();
    this.trigger.removeEventListener('click', this.toggle.bind(this));
  }

  toggle() {
    if (this.expanded) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    this.trigger.setAttribute('aria-expanded', 'true');
    this.expanded = true;
    this.target.dataset.state = 'expanded';
    this.dispatchEvent(new CustomEvent('open'));
  }

  close() {
    this.trigger.setAttribute('aria-expanded', 'false');
    this.expanded = false;
    this.target.dataset.state = 'collapsed';
    this.dispatchEvent(new CustomEvent('close'));
  }
}

customElements.define('craft-disclosure', CraftDisclosure);
