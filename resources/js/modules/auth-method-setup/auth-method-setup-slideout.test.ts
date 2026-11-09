import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import {AuthMethodSetupSlideout} from './auth-method-setup-slideout';

beforeEach(() => {
  vi.stubGlobal('Craft', {slideoutPosition: 'end'});
  // happy-dom doesn't implement it. Hidden, or inside something hidden,
  // stands in for not visible.
  const proto: object = HTMLElement.prototype;
  if (!('checkVisibility' in proto)) {
    Object.defineProperty(proto, 'checkVisibility', {
      configurable: true,
      value(this: HTMLElement) {
        return !this.closest('[hidden]');
      },
    });
  }
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.innerHTML = '';
});

it('confirms the setup in place of the setup screen, and focuses the confirmation', () => {
  const slideout = new AuthMethodSetupSlideout({
    methodName: 'Authenticator App',
    html: '<h1>Authenticator App Setup</h1><a href="#duo">Duo Mobile</a>',
    containerId: 'auth-setup',
  });

  slideout.showSuccess();

  const confirmation = 'Authenticator App added successfully.';
  expect(document.activeElement?.tagName).toBe('H1');
  expect(computeAccessibleName(document.activeElement!)).toBe(confirmation);
  // The heading the dialog was named after has gone, so its name follows
  // the confirmation.
  expect(
    computeAccessibleName(document.querySelector('[role="dialog"]')!)
  ).toBe(confirmation);
  expect(document.body.textContent).not.toContain('Duo Mobile');
  expect(
    document.querySelector('.auth-method-close-btn')?.textContent?.trim()
  ).toBe('Close');

  slideout.close();
  slideout.destroy();
});
