/**
 *
 * Spinner
 *
 *
 @property {string} message - The loading message associated with the spinner
 @property {boolean} messageVisible - Whether the loading message is visible on-screen
 */
class CraftSpinner extends HTMLElement {
  connectedCallback() {
    this.message = this.getAttribute('message') || Craft.t('app', 'Loading');
    this.messageVisible = this.getAttribute('messageVisible') || false;

    this.wrapper = document.createElement('div');
    this.spinner = document.createElement('div');
    this.messageContainer = document.createElement('span');

    if (!this.messageVisible) {
      this.messageContainer.classList.add('visually-hidden');
    }

    this.wrapper.setAttribute('tabindex', '-1');
    this.wrapper.classList.add('hidden', 'wrapper');
    this.spinner.classList.add('spinner');
    this.messageContainer.innerText = this.message;

    // Add spinner and message to wrapper
    this.wrapper.append(this.spinner);
    this.wrapper.append(this.messageContainer);
    this.append(this.wrapper);

    //
    // if (!this.trigger.getAttribute('aria-expanded')) {
    //   this.trigger.setAttribute('aria-expanded', 'false');
    // }
    //
    // this.trigger.addEventListener('click', this.toggle.bind(this));
    //
    // this.expanded = this.trigger.getAttribute('aria-expanded') === 'true';
    // this.expanded ? this.open() : this.close();
  }

  disconnectedCallback() {
    // this.open();
    // this.trigger.removeEventListener('click', this.toggle.bind(this));
  }

  toggle() {
    // if (this.expanded) {
    //   this.close();
    // } else {
    //   this.open();
    // }
  }

  show(focus = false) {
    this.wrapper.classList.remove('hidden');
    // this.trigger.setAttribute('aria-expanded', 'true');
    // this.expanded = true;
    // this.target.dataset.state = 'expanded';
    // this.dispatchEvent(new CustomEvent('open'));

    if (focus) {
      this.wrapper.focus();
    }
  }

  hide() {
    this.wrapper.classList.add('hidden');
    // this.trigger.setAttribute('aria-expanded', 'false');
    // this.expanded = false;
    // this.target.dataset.state = 'collapsed';
    // this.dispatchEvent(new CustomEvent('close'));
  }
}

customElements.define('craft-spinner', CraftSpinner);
