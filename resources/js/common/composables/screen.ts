import {
    inject,
    provide,
    reactive,
    type Component,
    type InjectionKey,
} from 'vue';
import {usePage} from '@inertiajs/vue3';

/**
 * A CP screen renders in one of two contexts. The page component is the same
 * in both — only the shell around it differs, which `AppLayout` selects by
 * injecting {@link ScreenShellKey}.
 */
export type ScreenMode = 'page' | 'slideout';

export interface ScreenContext {
    mode: ScreenMode;
}

export const ScreenContextKey: InjectionKey<ScreenContext> =
    Symbol('screenContext');

/**
 * The component `AppLayout` renders. Defaults to the full-page shell; a
 * slideout panel provides its own, and each shell re-provides a passthrough so
 * a page that renders `<AppLayout>` inline inside a slideout doesn't stack a
 * second shell inside the first.
 */
export const ScreenShellKey: InjectionKey<Component> = Symbol('screenShell');

/**
 * Collects the options pages push through `useAppLayout()`.
 *
 * Full-page screens don't use this — they go through Inertia's own
 * `setLayoutProps`, which only addresses the one layout Inertia rendered and
 * would clobber the base page if a slideout wrote to it.
 */
export type ScreenSaveHandler = (options?: unknown) => void;

export interface ScreenPropsStore {
    props: Record<string, unknown>;
    set(props: Record<string, unknown>): void;
    /**
     * Register a save handler, returning an unregister function.
     *
     * The shell's save button sits *above* the page in the tree, so a `save`
     * event can't reach a page listening on an inline `<AppLayout>` below it.
     * Handlers registered here bridge that gap.
     */
    onSave(handler: ScreenSaveHandler): () => void;
    save(options?: unknown): void;
}

export const ScreenPropsStoreKey: InjectionKey<ScreenPropsStore> =
    Symbol('screenPropsStore');

export function createScreenPropsStore(): ScreenPropsStore {
    const props = reactive<Record<string, unknown>>({});
    const handlers = new Set<ScreenSaveHandler>();

    return {
        props,

        set(next) {
            Object.assign(props, next);
        },

        onSave(handler) {
            handlers.add(handler);

            return () => handlers.delete(handler);
        },

        save(options) {
            // `useAppLayout({onSave})` arrives as a prop, since Vue treats `onX` as
            // a listener for `x`.
            (props.onSave as ScreenSaveHandler | undefined)?.(options);

            handlers.forEach((handler) => handler(options));
        },
    };
}

/**
 * The page props a shell should read its chrome from.
 *
 * `usePage()` always returns the *base* page — correct for shared props (flash,
 * CSRF, the craft bag), but wrong for anything screen-specific: a slideout
 * reading `usePage().props.title` gets the title of the page behind it. A
 * slideout panel provides its own props here; a full page has none and falls
 * back to `usePage()`.
 */
export const ScreenPagePropsKey: InjectionKey<() => Record<string, unknown>> =
    Symbol('screenPageProps');

/**
 * Called by a page once its server-rendered content is actually in the
 * document.
 *
 * Fragments are appended asynchronously — `appendHeadHtml()` resolves only
 * when the screen's assets have loaded — so a shell that has to hand that
 * markup to legacy JS (`Craft.ElementEditor`, say) can't just wait for
 * `onMounted`, which fires while the content is still empty.
 */
export const ScreenContentReadyKey: InjectionKey<() => void> =
    Symbol('screenContentReady');

/** No-ops outside a shell that cares. See {@link ScreenContentReadyKey}. */
export function useScreenContentReady(): () => void {
    return inject(ScreenContentReadyKey, () => {});
}

export function provideScreenContext(mode: ScreenMode): void {
    provide(ScreenContextKey, reactive({mode}));
}

export function useScreenContext(): ScreenContext {
    return inject(ScreenContextKey, {mode: 'page'});
}

export function useScreenPropsStore(): ScreenPropsStore | null {
    return inject(ScreenPropsStoreKey, null);
}

/** True when the calling component is rendering inside a slideout. */
export function useIsSlideout(): boolean {
    return useScreenContext().mode === 'slideout';
}

/**
 * Screen-specific page props for the current context — the slideout's own when
 * inside one, the base page's otherwise. See {@link ScreenPagePropsKey}.
 */
export function useScreenPageProps(): () => Record<string, unknown> {
    const scoped = inject(ScreenPagePropsKey, null);

    if (scoped) {
        return scoped;
    }

    const page = usePage();

    return () => page.props as Record<string, unknown>;
}
