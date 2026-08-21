import type {InertiaForm} from '@inertiajs/vue3';
import {
    computed,
    shallowRef,
    toRaw,
    toValue,
    watch,
    type MaybeRefOrGetter,
} from 'vue';
import type {FormChangeKind, FormPayload} from './types';

interface FormRendererInstance {
    advanceBaseline(): void;
    currentValues(): FormPayload['values'];
    resetValues(): void;
    setValue(path: string[], value: unknown, kind?: FormChangeKind): void;
}

interface Options<
    T extends Record<string, any>,
    Key extends Extract<keyof T, string>,
> {
    mutationKey?: Key;
    mapErrorPath?: (path: string) => string[] | null;
}

/**
 * Bridges a FormRenderer island into an Inertia form. The Inertia field always
 * contains the editable mutation; `values` contains the full current values.
 */
export function useInertiaFormRenderer<
    T extends Record<string, any>,
    Key extends Extract<keyof T, string> = Extract<keyof T, string>,
>(
    form: InertiaForm<T>,
    payload: MaybeRefOrGetter<FormPayload | null>,
    {mutationKey, mapErrorPath}: Options<T, Key> = {}
) {
    const renderer = shallowRef<FormRendererInstance | null>(null);
    const values = shallowRef(clone(toValue(payload)?.values ?? {}));
    const formData = form as T;
    const rootKeys = new Set(Object.keys(form.data()));

    replaceMutation({});
    form.defaults();

    watch(
        () => toValue(payload),
        (currentPayload) => (values.value = clone(currentPayload?.values ?? {}))
    );

    const errors = computed(() =>
        Object.entries(form.errors).flatMap(([path, message]) => {
            const mappedPath = mapErrorPath
                ? mapErrorPath(path)
                : defaultErrorPath(path, toValue(payload)?.scope ?? []);

            return mappedPath
                ? [{path: mappedPath, messages: [String(message)]}]
                : [];
        })
    );

    /**
     * Applies the renderer's mutation to the Inertia form, and reports whether it
     * carried anything.
     *
     * The mutation *is* the difference against the values the server sent, so an
     * empty one means the screen matches the server — a control announcing itself
     * as it's populated, say, rather than an edit. Callers that act on changes
     * (autosave) read that from here rather than deciding for themselves, and
     * `form.isDirty` says the same thing a tick later for callers that can wait.
     */
    function onMutation(mutation: FormPayload['values']): boolean {
        replaceMutation(mutation);
        values.value = renderer.value?.currentValues() ?? values.value;

        return Object.keys(mutation).length > 0;
    }

    function replaceMutation(mutation: FormPayload['values']): void {
        if (mutationKey !== undefined) {
            formData[mutationKey] = clone(
                mutation[mutationKey] ?? {}
            ) as T[Key];

            return;
        }

        for (const key of rootKeys) {
            Reflect.deleteProperty(formData, key);
        }

        for (const [key, value] of Object.entries(mutation)) {
            if (!rootKeys.has(key)) {
                form.defaults({[key]: undefined} as unknown as Partial<T>);
                rootKeys.add(key);
            }

            formData[key as Extract<keyof T, string>] = clone(
                value
            ) as T[Extract<keyof T, string>];
        }
    }

    function advanceBaseline(): void {
        renderer.value?.advanceBaseline();
        form.defaults();
    }

    /**
     * Throws away the unsaved values and takes the currently loaded payload as
     * the new baseline, leaving the Inertia form clean.
     *
     * Only for the case where the user has explicitly abandoned their edits —
     * discarding a provisional draft. An ordinary refresh must keep them.
     */
    function resetValues(): void {
        renderer.value?.resetValues();
        // The renderer's reset emits an empty mutation of its own, but the bridge
        // has to hold up on its own when nothing is mounted to emit one.
        replaceMutation({});
        values.value = clone(toValue(payload)?.values ?? {});
        form.defaults();
    }

    return {advanceBaseline, errors, onMutation, renderer, resetValues, values};
}

function defaultErrorPath(path: string, scope: string[]): string[] | null {
    const segments = path.split('.');

    return scope.every((segment, index) => segments[index] === segment)
        ? segments
        : null;
}

function clone<T>(value: T): T {
    return structuredClone(toRaw(value));
}
