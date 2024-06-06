/**
 *
 * Spinner
 *
 *
 @property {string} message - The loading message associated with the spinner
 @property {boolean} messageVisible - Whether the loading message is visible on-screen
 */
const template = document.createElement('template');
template.innerHTML = `
  <div class="wrapper hidden" style="" tabindex="-1">
    <div class="spinner"></div>
    <span class="message visually-hidden">${Craft.t('app', 'Loading')}</span>
  </div>
`;

class CraftSpinner extends HTMLElement {
  connectedCallback() {
    this.root = this;
    let clone = template.content.cloneNode(true);
    this.root.append(clone);

    this.root.style.display = 'flex';
    this.root.style.justifyContent = 'center';

    if (this.visible === 'true') {
      this.wrapper.classList.remove('hidden');
    }

    if (this.messageVisible === 'true') {
      this.messageWrapper.classList.remove('visually-hidden');
    }

    this.initialized = true;
  }

  static get observedAttributes() {
    return ['visible'];
  }

  get visible() {
    return this.getAttribute('visible');
  }

  set visible(value) {
    this.setAttribute('visible', value);
  }

  get messageWrapper() {
    return this.querySelector('.message');
  }

  get messageVisible() {
    return this.getAttribute('messageVisible');
  }

  get wrapper() {
    return this.querySelector('.wrapper');
  }

  attributeChangedCallback(attrName, oldVal, newVal) {
    if (!this.initialized) return;

    if (attrName.toLowerCase() === 'visible') {
      return newVal === 'true' ? this.show() : this.hide();
    }
  }
  disconnectedCallback() {}

  show() {
    this.wrapper.classList.remove('hidden');
    this.dispatchEvent(new CustomEvent('show'));
  }

  hide() {
    this.wrapper.classList.add('hidden');
    this.dispatchEvent(new CustomEvent('hide'));
  }

  focus() {
    this.wrapper.focus();
  }
}

customElements.define('craft-spinner', CraftSpinner);
