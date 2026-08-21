/**
 * Flatten the server's `{field: [message, …]}` error bag to one message per
 * field, matching what `withErrors()` hands an Inertia page on the full-page
 * path so error rendering is identical in both contexts.
 *
 * Kept free of other imports: both the Vue-form and server-HTML save paths use
 * it, and the former shouldn't have to pull in `@craftcms/ui` for a helper.
 */
export function firstMessages(
    errors: Record<string, unknown>
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(errors).map(([field, value]) => [
            field,
            String((Array.isArray(value) ? value[0] : value) ?? ''),
        ])
    );
}
