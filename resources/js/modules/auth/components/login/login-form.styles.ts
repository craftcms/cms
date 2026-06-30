import {css} from 'lit';

export default css`
  :host {
    display: block;
    width: 100%;
  }

  .spinner-overlay {
    display: grid;
    place-items: center;
  }

  .auth-form__fields {
    display: flex;
    gap: var(--c-spacing-md);
    align-items: end;
  }

  .auth-form__actions {
    margin-block-start: var(--c-spacing-lg);
  }

  .auth-form__heading {
    margin: 0;
    font-size: var(--c-font-size);
    font-weight: var(--c-font-weight-bold);
  }

  .auth-form__error {
    margin-block-start: var(--c-spacing-md);
  }

  .alternative-login-methods {
    margin-block-start: var(--c-spacing-lg);
  }

  hr {
    margin-block: var(--c-spacing-lg);
    border: none;
    border-block-end: 1px solid var(--c-color-border-quiet);
  }
`;
