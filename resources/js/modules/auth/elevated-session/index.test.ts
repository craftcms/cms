import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {elevatedSessionManager} from './manager';
import './index';

describe('legacy elevated-session adapter', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('maps successful confirmation to the legacy callback', async () => {
    const requireSpy = vi
      .spyOn(elevatedSessionManager, 'require')
      .mockResolvedValue(true);
    const onSuccess = vi.fn();
    const onCancel = vi.fn();

    await window.Craft.elevatedSessionManager.requireElevatedSession(
      onSuccess,
      onCancel,
      20
    );

    expect(requireSpy).toHaveBeenCalledWith({
      minimumRemainingSeconds: 20,
    });
    expect(onSuccess).toHaveBeenCalledOnce();
    expect(onCancel).not.toHaveBeenCalled();
  });

  it('maps canceled confirmation to the legacy callback', async () => {
    vi.spyOn(elevatedSessionManager, 'require').mockResolvedValue(false);
    const onSuccess = vi.fn();
    const onCancel = vi.fn();

    await window.Craft.elevatedSessionManager.requireElevatedSession(
      onSuccess,
      onCancel
    );

    expect(onSuccess).not.toHaveBeenCalled();
    expect(onCancel).toHaveBeenCalledOnce();
  });
});
