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
      console.error(`No target found for disclosure`, this.trigger);
      return;
    }

    if (!this.trigger.getAttribute('aria-expanded')) {
      this.trigger.setAttribute('aria-expanded', 'false');
    }

    this.trigger.addEventListener('click', this.toggle.bind(this));

    this.expanded = this.trigger.getAttribute('aria-expanded') === 'true';
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
    this.target.classList.add('is-open');
  }

  close() {
    this.trigger.setAttribute('aria-expanded', 'false');
    this.expanded = false;
    this.target.classList.remove('is-open');
  }
}

customElements.define('craft-disclosure', CraftDisclosure);
