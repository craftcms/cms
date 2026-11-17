import {afterEach, expect, it, vi} from 'vite-plus/test';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import {bootLicensingIssues} from './licensing-issues';

vi.mock('@craftcms/ui/utilities/translate', () => ({
  t: (message: string, params: {duration?: number} = {}) =>
    message.startsWith('Continue to the control panel in')
      ? `in ${params.duration}`
      : message,
}));

afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
  document.body.innerHTML = '';
});

it('counts down, then shuns the issues and links on to the control panel', async () => {
  vi.useFakeTimers();
  const post = vi.spyOn(actionClient, 'post').mockResolvedValue({} as never);
  document.body.innerHTML =
    '<p id="continue" role="timer" data-licensing-continue="2" data-licensing-hash="abc"></p>';
  const timer = document.getElementById('continue')!;

  bootLicensingIssues();
  expect(timer.textContent).toBe('in 2');

  await vi.advanceTimersByTimeAsync(2000);

  expect(post).toHaveBeenCalledWith('app/set-license-shun-cookie', {
    hash: 'abc',
  });
  expect(timer.querySelector('a.go')!.textContent).toBe(
    'Continue to the control panel'
  );
  expect(timer.hasAttribute('role')).toBe(false);
});
