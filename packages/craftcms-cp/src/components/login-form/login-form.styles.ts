import {css} from 'lit';

export default css`
  :host {
    display: block;
  }

  .login-container {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-lg);
  }

  .login-form-container {
    padding: var(--c-spacing-xl);
    background: var(--c-surface-raised);
    border: 1px solid var(--c-border-default);
    border-radius: var(--c-border-radius-lg);
  }

  .login-form,
  .login-reset-password {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
  }

  .field label {
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--c-text-default);
  }

  .field input[type='text'],
  .field input[type='email'],
  .field input[type='password'] {
    width: 100%;
    box-sizing: border-box;
    padding: var(--c-input-spacing-block) var(--c-input-spacing-inline);
    border: 1px solid var(--c-border-form);
    border-radius: var(--c-border-radius-md);
    background: var(--c-surface-form);
    color: var(--c-text-default);
    font-size: 1rem;
    line-height: 1.5;
  }

  .field input:focus {
    outline: 2px solid var(--c-color-accent-border-loud);
    outline-offset: -1px;
  }

  .password-field-wrapper {
    position: relative;
  }

  .forgot-password-row {
    display: flex;
    justify-content: flex-end;
    margin-top: calc(var(--c-spacing-xs) * -1);
  }

  .login-forgot-password {
    background: none;
    border: none;
    padding: 0;
    color: var(--c-text-link);
    cursor: pointer;
    font-size: 0.875rem;
  }

  .login-forgot-password:hover {
    text-decoration: underline;
  }

  .remember-me-row {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .login-errors {
    color: var(--c-color-danger-text-loud);
  }

  .login-errors p {
    margin: 0;
  }

  .alternative-login-methods {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .spinner-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--c-spacing-xl);
  }

  .login-alt-container {
    margin-top: var(--c-spacing-md);
  }

  .login-alt-menu {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-xs);
  }

  .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
`;
