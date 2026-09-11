import {effectScope, shallowRef} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useDelayedLoading} from './useDelayedLoading';

afterEach(() => vi.useRealTimers());

it('shows only sustained loading and hides immediately when it finishes', () => {
  vi.useFakeTimers();
  const scope = effectScope();
  const loading = shallowRef(true);
  const visible = scope.run(() => useDelayedLoading(loading))!;

  vi.advanceTimersByTime(199);
  expect(visible.value).toBe(false);
  loading.value = false;
  vi.advanceTimersByTime(200);
  expect(visible.value).toBe(false);

  loading.value = true;
  vi.advanceTimersByTime(200);
  expect(visible.value).toBe(true);
  loading.value = false;
  expect(visible.value).toBe(false);

  loading.value = true;
  scope.stop();
  vi.advanceTimersByTime(200);
  expect(visible.value).toBe(false);
});
