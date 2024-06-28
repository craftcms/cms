export default class TotpForm extends HTMLElement {
  get form() {
    return this.querySelector('form');
  }

  get codeInput() {
    return this.querySelector('input[name=code]');
  }

  connectedCallback() {
    this.setAttribute('state', 'idle');
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
    this.codeInput.addEventListener('input', this.handleInput.bind(this));
  }

  disconnectedCallback() {
    this.form.removeEventListener('submit', this.handleSubmit.bind(this));
    this.codeInput.removeEventListener('input', this.handleInput.bind(this));
  }

  handleInput(ev) {
    if (ev.target.value.length === 6) {
      this.form.requestSubmit();
    }
  }

  async handleSubmit(event) {
    event.preventDefault();

    if (this.getAttribute('state') === 'loading') {
      return;
    }

    this.setAttribute('state', 'loading');

    const formData = new FormData(this.form);

    try {
      const response = await fetch('/actions/auth/verify-totp', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
        body: formData,
      });

      const data = await response.json();

      console.log(data);
      this.setAttribute('state', 'success');
      this.dispatchEvent(new CustomEvent('totp:success'));
    } catch (error) {
      this.setAttribute('state', 'error');
      this.dispatchEvent(new CustomEvent('totp:error'));
    }
  }
}

customElements.define('craft-totp-form', TotpForm);
