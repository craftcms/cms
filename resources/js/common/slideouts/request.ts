import axios from 'axios';
import {resolveInertiaPage} from '@/bootstrap/inertia-pages';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';

export interface SlideoutPage {
    component: InertiaPageComponent;
    props: Record<string, unknown>;
    url: string;
}

/**
 * The asset version of the currently loaded page, kept in sync by
 * `SlideoutHost`. Sent so the server can 409 a slideout opened against a stale
 * build, the same way Inertia does for a navigation.
 */
let assetVersion = '';

export function setAssetVersion(version: string): void {
    assetVersion = version;
}

/**
 * Fetch a CP screen as an Inertia page, without navigating.
 *
 * Deliberately not `router.visit()`: that would swap the base page out from
 * under the slideout. This is a plain XHR that happens to speak Inertia —
 * `Accept: application/json` puts `CpScreenResponse` into its slideout branch,
 * and `X-Inertia` selects the Inertia wire format within it.
 */
export async function fetchSlideoutPage(
    href: string,
    containerId: string,
    signal?: AbortSignal
): Promise<SlideoutPage> {
    const response = await axios.get(href, {
        signal,
        headers: {
            'X-Inertia': 'true',
            'X-Inertia-Version': assetVersion,
            'X-Craft-Container-Id': containerId,
            Accept: 'application/json',
        },
        // Handle redirects and version conflicts ourselves rather than letting
        // axios throw on them.
        validateStatus: (status) => status < 400 || status === 409,
    });

    // A deploy landed since this page loaded. Inertia's answer is a hard visit,
    // and there's no way to keep the slideout across it.
    if (response.status === 409) {
        const location = response.headers['x-inertia-location'];

        if (location) {
            window.location.href = location;

            throw new Error('Asset version changed; reloading.');
        }
    }

    const page = response.data;

    if (!page?.component) {
        // Not an Inertia response — most likely a screen that hasn't been migrated
        // off Twig, or a login redirect. Fall back to a full navigation so the user
        // still gets where they were going.
        window.location.href = href;

        throw new Error('Screen did not return an Inertia response.');
    }

    return {
        component: await resolveInertiaPage(page.component),
        props: page.props ?? {},
        url: page.url ?? href,
    };
}
