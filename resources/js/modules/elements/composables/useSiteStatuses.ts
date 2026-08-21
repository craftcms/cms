import type {InertiaForm} from '@inertiajs/vue3';
import {watch} from 'vue';

/**
 * Keeps the editor's "Enabled for all sites" switch in sync with the per-site
 * switches beside it, mirroring the legacy element editor.
 *
 * Toggling the global switch applies its value to every site; toggling a site
 * rolls back up into the global switch, which reads `true` when every site is
 * on, `false` when none are, and indeterminate (`'-'`) when they disagree.
 *
 * Both switches are ordinary Form Controls writing into the shared Inertia
 * form, so this only has to reconcile their values — no DOM involved.
 */
export function useSiteStatuses(form: InertiaForm<Record<string, any>>): void {
    // Guards the two watchers below from re-entering each other: whichever
    // direction fires first owns the reconciliation for that tick.
    let syncing = false;

    function siteIds(): string[] {
        return Object.keys(form.enabledForSite ?? {});
    }

    function withSync(apply: () => void): void {
        if (syncing) {
            return;
        }

        syncing = true;
        apply();
        // Release only after the writes above have been observed, so the paired
        // watcher doesn't immediately undo them.
        void Promise.resolve().then(() => (syncing = false));
    }

    watch(
        () => form.enabled,
        (enabled) => {
            // An indeterminate global switch describes the sites; it doesn't set them.
            if (enabled === '-' || enabled === null || enabled === undefined) {
                return;
            }

            withSync(() => {
                const value = normalize(enabled);

                for (const siteId of siteIds()) {
                    form.enabledForSite[siteId] = value;
                }
            });
        }
    );

    watch(
        () => (form.enabledForSite ? {...form.enabledForSite} : null),
        (statuses) => {
            if (!statuses) {
                return;
            }

            const values = Object.values(statuses).map(normalize);

            if (values.length === 0) {
                return;
            }

            withSync(() => {
                const allOn = values.every(Boolean);
                const allOff = values.every((value) => !value);

                form.enabled = allOn ? true : allOff ? false : '-';
            });
        },
        {deep: true}
    );
}

/** Lightswitch values arrive as booleans or their submitted string forms. */
function normalize(value: unknown): boolean {
    return value === true || value === '1' || value === 1;
}
