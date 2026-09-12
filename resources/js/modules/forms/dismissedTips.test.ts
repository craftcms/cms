import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {dismissTip, dismissedTipUids, isTipDismissed} from './dismissedTips';

describe('dismissedTips', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('reports nothing dismissed to begin with', () => {
        expect(dismissedTipUids()).toEqual([]);
        expect(isTipDismissed('abc')).toBe(false);
    });

    it('remembers a dismissed tip', () => {
        dismissTip('abc');

        expect(isTipDismissed('abc')).toBe(true);
        expect(isTipDismissed('def')).toBe(false);
    });

    it('does not record the same tip twice', () => {
        dismissTip('abc');
        dismissTip('abc');

        expect(dismissedTipUids()).toEqual(['abc']);
    });

    it('shares the legacy editor’s storage key', () => {
        window.localStorage.setItem(
            'dismissedTips',
            JSON.stringify(['legacy-uid'])
        );

        expect(isTipDismissed('legacy-uid')).toBe(true);
    });

    it('treats a malformed value as nothing dismissed', () => {
        window.localStorage.setItem('dismissedTips', 'not json');

        expect(dismissedTipUids()).toEqual([]);
    });

    it('survives storage being unavailable', () => {
        const setItem = vi
            .spyOn(Storage.prototype, 'setItem')
            .mockImplementation(() => {
                throw new Error('QuotaExceededError');
            });

        expect(() => dismissTip('abc')).not.toThrow();

        setItem.mockRestore();
    });

    it('ignores a tip with no uid', () => {
        expect(isTipDismissed(null)).toBe(false);
        expect(isTipDismissed(undefined)).toBe(false);
    });
});
