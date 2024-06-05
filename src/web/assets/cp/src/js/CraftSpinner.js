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
  <div class="wrapper hidden" tabindex="-1">
    <div class="spinner"></div>
    <slot name="message">
      <span class="message visually-hidden">${Craft.t('app', 'Loading')}</span>
    </slot>
  </div>
`;

class CraftSpinner extends HTMLElement {
  connectedCallback() {
    this.root = this;
    let clone = template.content.cloneNode(true);
    this.root.append(clone);

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

  get focusWhenVisible() {
    return this.getAttribute('focusWhenVisible');
  }

  get visible() {
    return this.getAttribute('visible');
  }

  set visible(value) {
    let boolValue;
    if (typeof value === 'boolean') {
      boolValue = value;
    } else if (typeof value === 'string') {
      boolValue = value.toLowerCase() === 'true';
    } else {
      console.error('Property "visible" must be a string or boolean');
    }

    this.setAttribute('visible', boolValue);
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
      if (newVal === 'true') {
        this.show();
      } else {
        this.hide();
      }
    }
  }
  disconnectedCallback() {}

  show() {
    this.wrapper.classList.remove('hidden');
    this.dispatchEvent(new CustomEvent('show'));

    if (this.focusWhenVisible === 'true') {
      this.wrapper.focus();
    }
  }

  hide() {
    this.wrapper.classList.add('hidden');
    this.dispatchEvent(new CustomEvent('hide'));
  }
}

customElements.define('craft-spinner', CraftSpinner);
