import {afterEach, expect, it, vi} from 'vite-plus/test';
import {commandShortcut} from './keyShortcuts';

afterEach(() => {
  vi.restoreAllMocks();
});

it('uses Meta on Apple platforms', () => {
  vi.spyOn(navigator, 'platform', 'get').mockReturnValue('MacIntel');

  expect(commandShortcut('S')).toBe('Meta+S');
  expect(commandShortcut('Z', {shift: true})).toBe('Meta+Shift+Z');
});

it('uses Control elsewhere', () => {
  vi.spyOn(navigator, 'platform', 'get').mockReturnValue('Win32');

  expect(commandShortcut('Enter')).toBe('Control+Enter');
  expect(commandShortcut('Z', {shift: true})).toBe('Control+Shift+Z');
});
