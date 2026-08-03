import {describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from './components';
import type {Component} from 'vue';

const testComponent = {name: 'TestComponent'} as Component;
const alternateComponent = {name: 'AlternateComponent'} as Component;

describe('CP component registry', () => {
    it('installs registered components', () => {
        const registry = createCpComponentRegistry();
        const app = {
            component: vi.fn(),
        };

        registry.register('TestComponent', testComponent);
        registry.install(app as any);

        expect(app.component).toHaveBeenCalledWith(
            'TestComponent',
            testComponent
        );
    });

    it('registers components added after install', () => {
        const registry = createCpComponentRegistry();
        const app = {
            component: vi.fn(),
        };

        registry.install(app as any);
        registry.register('TestComponent', testComponent);

        expect(app.component).toHaveBeenCalledWith(
            'TestComponent',
            testComponent
        );
    });

    it('allows duplicate registrations with the same value', () => {
        const registry = createCpComponentRegistry();

        registry.register('TestComponent', testComponent);

        expect(() => {
            registry.register('TestComponent', testComponent);
        }).not.toThrow();
    });

    it('fails duplicate registrations with a different value', () => {
        const registry = createCpComponentRegistry();

        registry.register('TestComponent', testComponent);

        expect(() => {
            registry.register('TestComponent', alternateComponent);
        }).toThrow('CP component already registered: TestComponent');
    });
});
