import {describe, expect, it} from 'vitest';
import {ignoreModelValueInitialization} from './runtime';

describe('ignoreModelValueInitialization', () => {
    it('only forwards changes after initialization', () => {
        const changes: Event[] = [];
        const listener = ignoreModelValueInitialization((event) => {
            changes.push(event);
        });
        const change = new CustomEvent('model-value-changed');

        listener(
            new CustomEvent('model-value-changed', {detail: {initialize: true}})
        );
        listener(change);

        expect(changes).toEqual([change]);
    });
});
