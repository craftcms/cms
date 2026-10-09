import {effectScope, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {useBodyScrollLock} from './useBodyScrollLock';

describe('useBodyScrollLock', () => {
  const locked = () => document.body.classList.contains('no-scroll');

  afterEach(() => {
    document.body.classList.remove('no-scroll');
  });

  it('locks the body while a caller wants it', async () => {
    const open = ref(false);
    const scope = effectScope();
    scope.run(() => useBodyScrollLock(open));

    expect(locked()).toBe(false);

    open.value = true;
    await Promise.resolve();
    expect(locked()).toBe(true);

    open.value = false;
    await Promise.resolve();
    expect(locked()).toBe(false);

    scope.stop();
  });

  it('stays locked until the last of several callers lets go', async () => {
    const outer = ref(true);
    const inner = ref(false);
    const outerScope = effectScope();
    const innerScope = effectScope();
    outerScope.run(() => useBodyScrollLock(outer));
    innerScope.run(() => useBodyScrollLock(inner));

    expect(locked()).toBe(true);

    // A nested overlay opens and closes; the outer one still wants the lock.
    inner.value = true;
    await Promise.resolve();
    inner.value = false;
    await Promise.resolve();
    expect(locked()).toBe(true);

    outer.value = false;
    await Promise.resolve();
    expect(locked()).toBe(false);

    outerScope.stop();
    innerScope.stop();
  });

  it('releases a lock held by a scope that goes away', async () => {
    const scope = effectScope();
    scope.run(() => useBodyScrollLock(ref(true)));

    expect(locked()).toBe(true);

    // A modal unmounted while still open must not strand the lock.
    scope.stop();
    expect(locked()).toBe(false);
  });
});
