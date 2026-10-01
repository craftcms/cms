import {afterEach, expect, it, vi} from 'vitest';
import './cp-messages';

vi.mock('@craftcms/ui/utilities/translate', () => ({
  t: (message: string) => message,
}));

afterEach(() => {
  document.body.innerHTML = '';
  delete (window as {Craft?: unknown}).Craft;
});

it('builds the heading and both live regions when connected', () => {
  document.body.innerHTML = '<cp-messages></cp-messages>';
  const element = document.querySelector('cp-messages')!;

  expect(element.id).toBe('messages');
  expect(element.classList.contains('cp-messages')).toBe(true);
  expect(element.querySelector('h2')?.textContent).toBe('Messages');
  expect(
    [...element.querySelectorAll('.messages-stack > .message-region')].map(
      (region) => region.getAttribute('role')
    )
  ).toEqual(['alert', 'status']);
});

it('moves messages added before it was upgraded into their regions', () => {
  document.body.innerHTML = '<cp-messages></cp-messages>';
  const element = document.querySelector('cp-messages')!;
  element.remove();
  element.innerHTML =
    '<div class="notification" data-type="error"></div><div class="notification" data-type="success"></div>';
  element.querySelector('.messages-stack')?.remove();
  document.body.append(element);

  expect(
    element.querySelector('[role="alert"] > .notification')
  ).not.toBeNull();
  expect(
    element.querySelector('[role="status"] > .notification')
  ).not.toBeNull();
});

it('takes its position from the user’s preference unless one is given', () => {
  (window as {Craft?: unknown}).Craft = {notificationPosition: 'start-end'};
  document.body.innerHTML =
    '<cp-messages></cp-messages><cp-messages id="other" position="end-end"></cp-messages>';
  const [preferred, given] = document.querySelectorAll('cp-messages');

  expect(preferred!.position).toBe('start-end');
  expect(given!.position).toBe('end-end');
});
