import {router} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {computed, type ComputedRef} from 'vue';
import type {ActionItem} from '@/common/types';
import {ElementDeletionManager} from '@/modules/element-deletion-manager';

/** Identifies an element for the CP clipboard. */
interface ElementCopyRef {
    type: string;
    id: string | number;
    siteId?: number | null;
    draftId?: number | null;
    revisionId?: number | null;
}

/**
 * What an action menu item does, as described by the server. The element
 * supplies behavior rather than markup plus an inline script, so the client
 * dispatches these itself.
 */
export type ElementActionBehavior =
    | {type: 'link'; href: string; newTab?: boolean}
    | {
          type: 'submit';
          actionUrl: string;
          params?: Record<string, unknown>;
          /** Pre-encrypted by the server. */
          redirect?: string;
          confirm?: string;
          /** Re-authenticate before submitting, e.g. signing in as another user. */
          requireElevatedSession?: boolean;
      }
    | {type: 'copy'; elements: Array<ElementCopyRef>}
    | {
          type: 'delete';
          elementType: string;
          elementId: number;
          siteId: number | null;
          confirm: string;
          redirect: string;
      }
    | {
          type: 'slideout';
          url?: string;
          action?: string;
          params?: Record<string, unknown>;
          entryTypeFromField?: boolean;
      }
    // The asset behaviors below all hand off to a legacy modal or uploader, and
    // reload the page afterwards rather than patching the file's details into it.
    | {
          type: 'previewFile';
          assetId: number;
          settings?: Record<string, unknown>;
      }
    | {type: 'download'; actionUrl: string; params?: Record<string, unknown>}
    | {type: 'replaceFile'; assetId: number; fsType: string}
    | {type: 'editImage'; assetId: number}
    /**
     * Fetches a single-use URL and offers it for copying. Always behind an
     * elevated session — these URLs grant access to the account.
     */
    | {
          type: 'copyUrl';
          actionUrl: string;
          params?: Record<string, unknown>;
          prompt: string;
      };

export interface ElementActionMenuItem {
    label: string;
    icon?: string;
    color?: string;
    destructive?: boolean;
    behavior: ElementActionBehavior;
}

interface Options {
    /**
     * Resolves a live value the behavior depends on — currently the entry type,
     * which the sidebar can change without saving.
     */
    currentEntryTypeId?: () => unknown;
}

/**
 * Turns the element's action descriptors into menu items the CP action menu can
 * render, dispatching each behavior directly rather than through registered
 * jQuery handlers.
 */
export function useElementActionMenu(
    items: () => Array<ElementActionMenuItem>,
    {currentEntryTypeId}: Options = {}
): ComputedRef<Array<ActionItem>> {
    function dispatch(behavior: ElementActionBehavior): void {
        switch (behavior.type) {
            case 'link':
                window.open(
                    behavior.href,
                    behavior.newTab ? '_blank' : '_self',
                    behavior.newTab ? 'noopener' : undefined
                );

                return;

            case 'submit': {
                if (behavior.confirm && !window.confirm(behavior.confirm)) {
                    return;
                }

                const post = () =>
                    router.post(behavior.actionUrl, {
                        ...behavior.params,
                        ...(behavior.redirect
                            ? {redirect: behavior.redirect}
                            : {}),
                    });

                if (behavior.requireElevatedSession) {
                    void Craft.elevatedSessionManager.requireElevatedSession(
                        post
                    );

                    return;
                }

                post();

                return;
            }

            case 'copy':
                // `Craft.cp` owns the clipboard, including its confirmation toast.
                Craft.cp?.copyElements?.(behavior.elements);

                return;

            case 'delete':
                // Routed through the deletion manager so blocking relations and
                // references can be reassigned before the element goes.
                new ElementDeletionManager(
                    behavior.elementType,
                    [behavior.elementId],
                    {
                        siteId: behavior.siteId,
                        confirmationMessage: behavior.confirm,
                        onSuccess: () => router.visit(behavior.redirect),
                    }
                );

                return;

            case 'slideout': {
                const entryTypeId = currentEntryTypeId?.();
                const url =
                    behavior.entryTypeFromField && entryTypeId
                        ? Craft.getCpUrl(`settings/entry-types/${entryTypeId}`)
                        : behavior.url;

                if (url) {
                    new Craft.CpScreenSlideout(url);

                    return;
                }

                if (behavior.action) {
                    new Craft.CpScreenSlideout(behavior.action, {
                        params: behavior.params,
                    });
                }

                return;
            }

            case 'previewFile':
                new Craft.PreviewFileModal(
                    behavior.assetId,
                    behavior.settings ?? {}
                );

                return;

            case 'download':
                // A real form post, not an Inertia visit: the response is the file.
                submitNativeForm(behavior.actionUrl, behavior.params ?? {});

                return;

            case 'replaceFile':
                replaceFile(behavior.assetId, behavior.fsType);

                return;

            case 'copyUrl':
                void Craft.elevatedSessionManager.requireElevatedSession(
                    async () => {
                        try {
                            const {data} = await actionClient.post(
                                behavior.actionUrl,
                                behavior.params ?? {}
                            );

                            Craft.ui.createCopyTextPrompt({
                                label: behavior.prompt,
                                value: data.url,
                            });
                        } catch (error: any) {
                            Craft.cp?.displayError?.(
                                error?.response?.data?.message ??
                                    t('A server error occurred.')
                            );
                        }
                    }
                );

                return;

            case 'editImage':
                new Craft.AssetImageEditor(behavior.assetId, {
                    allowDegreeFractions: Craft.isImagick,
                    // A new asset means a different element entirely, so only an in-place
                    // edit is worth refreshing this screen for.
                    onSave: (data: {newAssetId?: number}) => {
                        if (!data.newAssetId) {
                            router.reload();
                        }
                    },
                });
        }
    }

    /**
     * Posts to an action the browser should handle itself — a file download,
     * which can't come back through Inertia.
     */
    function submitNativeForm(
        action: string,
        params: Record<string, unknown>
    ): void {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = action;
        form.hidden = true;

        const fields: Record<string, unknown> = {
            ...(Craft.csrfTokenName
                ? {[Craft.csrfTokenName]: Craft.csrfTokenValue}
                : {}),
            ...params,
        };

        for (const [name, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(value);
            form.append(input);
        }

        document.body.append(form);
        form.submit();
        form.remove();
    }

    /**
     * Swaps the asset's file for a newly uploaded one through the legacy
     * uploader, then reloads so the filename, size, dimensions, and thumbnail all
     * come back from the server together.
     */
    function replaceFile(assetId: number, fsType: string): void {
        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'replaceFile';
        input.hidden = true;
        document.body.append(input);

        const uploader = Craft.createUploader(fsType, $(input), {
            dropZone: null,
            fileInput: $(input),
            paramName: 'replaceFile',
            replace: true,
            events: {
                fileuploaddone: (event: any, data: any) => {
                    const result =
                        event instanceof CustomEvent
                            ? event.detail
                            : data.result;

                    if (result?.error) {
                        Craft.cp?.displayError?.(result.error);

                        return;
                    }

                    Craft.cp?.displayNotice?.(t('New file uploaded.'));
                    Craft.broadcaster?.postMessage({
                        event: 'saveElement',
                        id: assetId,
                    });
                    router.reload();
                },
                fileuploadfail: (event: any, data: any) => {
                    const response =
                        event instanceof CustomEvent
                            ? event.detail
                            : data?.jqXHR?.responseJSON;

                    Craft.cp?.displayError?.(
                        response?.message ?? t('Replace file failed.')
                    );
                },
                fileuploadalways: () => input.remove(),
            },
        });

        uploader.setParams({assetId});
        input.click();
    }

    return computed(() =>
        items().map(
            (item): ActionItem => ({
                label: item.label,
                icon: item.icon,
                iconColor: item.color,
                destructive: item.destructive,
                onClick: () => dispatch(item.behavior),
            })
        )
    );
}
