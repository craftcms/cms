class CraftCopyAttribute extends HTMLElement {
  copyValue(event) {
    this.input.select();
    document.execCommand('copy');
    Craft.cp.displayNotice(Craft.t('app', 'Copied to clipboard.'));
    $(this.btn).trigger('copy');
    this.input.setSelectionRange(0, 0);
    this.btn.focus();
  }

  /**
   *
   * @param {KeyboardEvent|PointerEvent} event
   */
  handleTrigger(event) {
    if (
      event instanceof KeyboardEvent &&
      (event.key === ' ' || event.key === 'Enter')
    ) {
      event.preventDefault();
      this.copyValue();
    }

    if (event instanceof PointerEvent) {
      this.copyValue();
    }
  }

  renderInput() {
    const input = document.createElement('input');
    input.value = this.value;
    input.classList.add('visually-hidden');
    input.readOnly = true;
    input.size = this.value.length;
    input.tabIndex = -1;
    input.ariaHidden = 'true';

    this.input = input;
    this.prepend(this.input);
  }

  renderIcon() {
    const a11yText = document.createElement('span');
    a11yText.classList.add('visually-hidden');
    a11yText.innerText = Craft.t('app', 'Copy to clipboard');
    this.btn.appendChild(a11yText);

    const icon = document.createElement('span');
    icon.classList.add('copytextbtn__icon');
    icon.ariaHidden = true;
    icon.setAttribute('data-icon', 'clipboard');
    this.btn.appendChild(icon);
  }

  connectedCallback() {
    this.value = this.querySelector('.copytextbtn__value').innerText;
    this.classList.add('copytextbtn-wrapper');

    this.btn = this.querySelector('.copytextbtn');

    this.renderInput();
    this.renderIcon();

    this.btn.addEventListener('click', this.handleTrigger.bind(this));
    this.btn.addEventListener('keydown', this.handleTrigger.bind(this));
  }
}

customElements.define('craft-copy-attribute', CraftCopyAttribute);
