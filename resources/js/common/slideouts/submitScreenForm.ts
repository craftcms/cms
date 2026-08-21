import {actionClient} from '@craftcms/ui';
import {firstMessages} from './errors';

export interface ScreenFormResult {
    ok: boolean;
    /** Flattened per-field errors, when the save failed validation. */
    errors?: Record<string, string>;
    /** The server's message, shown when there are no per-field errors. */
    message?: string;
    /** Set when the failure wasn't something the panel can present inline. */
    fatal?: unknown;
    /** The controller's response body, on success. */
    data?: Record<string, unknown>;
}

/**
 * Submit a screen that renders server HTML rather than a Vue form.
 *
 * Screens without an `inertiaPage()` come through `cp/Screen` as markup, so
 * there's no `useForm` to submit — but the inputs are real, and already
 * namespaced per panel. Posting them with `X-Craft-Namespace` lets
 * `ExtractNamespace` un-prefix them back to the shape the controller expects.
 *
 * Failures come back as **400** (`asJsonFailure()`), not Laravel's usual 422.
 */
export async function submitScreenForm(
    formEl: HTMLFormElement,
    {
        action,
        namespace,
        containerId,
    }: {action: string; namespace?: string | null; containerId?: string | null}
): Promise<ScreenFormResult> {
    const headers: Record<string, string> = {};

    if (namespace) {
        headers['X-Craft-Namespace'] = namespace;
    }

    if (containerId) {
        headers['X-Craft-Container-Id'] = containerId;
    }

    try {
        // `actionClient` resolves a bare action path against the CP action URL and
        // adds CSRF + registered-asset headers.
        const response = await actionClient.post(action, new FormData(formEl), {
            headers,
        });

        return {ok: true, data: response.data};
    } catch (error: any) {
        const data = error?.response?.data;

        if (data?.errors) {
            return {ok: false, errors: firstMessages(data.errors)};
        }

        if (data?.message) {
            return {ok: false, message: String(data.message)};
        }

        return {ok: false, fatal: error};
    }
}
