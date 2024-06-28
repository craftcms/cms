function createElementFromString(html, trim = true) {
  html = trim ? html.trim() : html;
  if (!html) {
    return null;
  }

  const template = document.createElement('template');
  template.innerHTML = html;
  const result = template.content.children;

  if (result.length === 1) {
    return result[0];
  }

  return result;
}

export default class CraftLoginForm extends HTMLElement {
  get form() {
    return this.querySelector('form');
  }

  get submitButton() {
    return this.querySelector('[type=submit]');
  }

  connectedCallback() {
    if (!this.form) {
      console.error('<craft-login-form> must contain a form element.');
      return;
    }

    this.setAttribute('state', 'idle');
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
  }

  disconnectedCallback() {
    this.form.removeEventListener('submit', this.handleSubmit.bind(this));
  }

  async handleSubmit(event) {
    event.preventDefault();

    if (this.getAttribute('state') === 'loading') {
      return;
    }

    const formData = new FormData(this.form);
    this.setAttribute('state', 'loading');
    try {
      const response = await fetch('/actions/users/login', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      });

      const data = await response.json();
      this.setAttribute('state', 'success');

      if (data.authMethod) {
        this.show2faForm(data);
      } else {
        // Set button to success
        // Redirect user
        window.location.href = data.returnUrl;
      }
    } catch (error) {
      this.setAttribute('state', 'error');
      console.error(error);
    }
  }

  show2faForm({authForm = '', authMethod, headHtml, bodyHtml, returnUrl}) {
    this.state = '2fa-form';
    const authFormElement = createElementFromString(authForm);
    this.form.after(authFormElement);

    authFormElement.addEventListener('totp:success', function (event) {
      window.location.href = returnUrl;
    });

    // Execute head and body html snippets

    // Initialize UI elements

    // Create authFormHandler
    // createAuthFormHandler(
    //   authMethod,
    //   authForm,
    //   () => {
    //     console.log('logged in');
    //   },
    //   (error) => {
    //     console.log(error);
    //   }
    // );
  }
}

customElements.define('craft-login-form', CraftLoginForm);
