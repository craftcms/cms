import {Uploader} from '@/modules/uploader/uploader';
import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';
import {router} from '@inertiajs/vue3';
import {actionClient, t} from '@craftcms/ui';
import {computed, type ComputedRef} from 'vue';
import {openSlideout, type SlideoutController} from '@/common/slideouts';
import type {ActionItem} from '@/common/types';
import {ElementDeletionManager} from '@/modules/element-deletion-manager';
import type {UiProperties, UiValues} from '@/modules/ui/types';
import {openUiModal} from '@/modules/ui/open-ui-modal';
import {
  openImageEditorDialog,
  type ImageEditorSettings,
} from '@/modules/image-editor/open-image-editor-dialog';

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
      params?: UiValues;
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
      url: string;
      entryTypeFromField?: boolean;
    }
  /** Loads and submits a server-built form, then reloads the page. */
  | {
      type: 'formModal';
      modalUrl: string;
      actionUrl: string;
      params?: UiValues;
    }
  // The asset behaviors below all hand off to a modal or uploader, and reload
  // the page afterwards rather than patching the file's details into it.
  | {
      type: 'previewFile';
      assetId: number;
      settings?: UiProperties;
    }
  | {type: 'download'; actionUrl: string; params?: UiValues}
  | {type: 'replaceFile'; assetId: number}
  | {type: 'editImage'; assetId: number; settings: ImageEditorSettings}
  /**
   * Fetches a single-use URL and offers it for copying. Always behind an
   * elevated session — these URLs grant access to the account.
   */
  | {
      type: 'copyUrl';
      actionUrl: string;
      params?: UiValues;
      prompt: string;
    };

export interface ElementActionMenuButton {
  type?: 'button';
  label: string;
  icon?: string;
  color?: string;
  destructive?: boolean;
  behavior: ElementActionBehavior;
}

/** A rule between groups of items, as the server describes it. */
export interface ElementActionMenuSeparator {
  type: 'hr';
}

export type ElementActionMenuItem =
  | ElementActionMenuButton
  | ElementActionMenuSeparator;

interface Options {
  /**
   * Resolves a live value the behavior depends on — currently the entry type,
   * which the sidebar can change without saving.
   */
  currentEntryTypeId?: () => string | number | null;
  /**
   * The slideout the element is being edited in, if any. Actions that would
   * otherwise navigate or reload the page act on the panel instead: the page
   * behind it may have unsaved changes of its own.
   */
  slideout?: SlideoutController | null;
}

/**
 * Turns the element's action descriptors into menu items the CP action menu can
 * render, dispatching each behavior directly rather than through registered
 * jQuery handlers.
 */
/**
 * Builds the descriptor-to-{@link ActionItem} mapper, dispatcher and all.
 *
 * Separate from {@link useElementActionMenu} so a caller with many menus — a
 * relation field drawing one per chip — can map them all through a single
 * dispatcher instead of standing up a computed per element.
 */
export function createElementActionMenu({
  currentEntryTypeId,
  slideout = null,
}: Options = {}) {
  function dispatch(
    behavior: ElementActionBehavior,
    destructive = false
  ): void {
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

        const params = {...behavior.params};
        if (behavior.redirect && !slideout) params.redirect = behavior.redirect;

        const post = slideout
          ? () =>
              void submitInSlideout(
                slideout,
                behavior.actionUrl,
                params,
                destructive
              )
          : () => router.post(behavior.actionUrl, params);

        if (behavior.requireElevatedSession) {
          void Craft.elevatedSessionManager.requireElevatedSession(post);

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
        new ElementDeletionManager(behavior.elementType, [behavior.elementId], {
          siteId: behavior.siteId,
          confirmationMessage: behavior.confirm,
          onSuccess: () => {
            if (slideout) {
              slideout.saved();
              slideout.close({force: true});
            } else {
              router.visit(behavior.redirect);
            }
          },
        });

        return;

      case 'slideout': {
        const entryTypeId = currentEntryTypeId?.();

        void openSlideout(
          behavior.entryTypeFromField && entryTypeId
            ? Craft.getCpUrl(`settings/entry-types/${entryTypeId}`)
            : behavior.url
        );

        return;
      }

      case 'formModal':
        void openUiModal({
          modalUrl: behavior.modalUrl,
          actionUrl: behavior.actionUrl,
          params: behavior.params,
          onSubmitted: () => router.reload(),
        });

        return;

      case 'previewFile':
        new Craft.PreviewFileModal(behavior.assetId, behavior.settings ?? {});

        return;

      case 'download':
        // A real form post, not an Inertia visit: the response is the file.
        submitNativeForm(behavior.actionUrl, behavior.params ?? {});

        return;

      case 'replaceFile':
        replaceFile(behavior.assetId);

        return;

      case 'copyUrl':
        void Craft.elevatedSessionManager.requireElevatedSession(async () => {
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
              error?.response?.data?.message ?? t('A server error occurred.')
            );
          }
        });

        return;

      case 'editImage':
        void openImageEditorDialog(behavior.settings, (result) => {
          if (!result.newAssetId) {
            reload();
          }
        });
    }
  }

  /** Refreshes what's showing the element: its slideout, or the page. */
  function reload(): void {
    if (slideout) {
      void slideout.reload();
    } else {
      router.reload();
    }
  }

  /**
   * Submits an action from inside a slideout, where an Inertia visit would
   * replace the page behind the panel.
   *
   * A destructive action leaves nothing to show, so the panel closes, and the
   * opener hears of it as a real change. Anything else — validating, say —
   * may not have changed the element at all, so the opener only refreshes, as
   * it would for a draft, rather than marking itself as modified; the panel
   * reloads.
   */
  async function submitInSlideout(
    panel: SlideoutController,
    actionUrl: string,
    params: UiValues,
    destructive: boolean
  ): Promise<void> {
    try {
      const {data} = await actionClient.post(actionUrl, params);

      if (data?.message) {
        Craft.cp?.displayNotice?.(data.message);
      }

      panel.saved(destructive ? {data} : {draft: true, data});

      if (destructive) {
        panel.close({force: true});
      } else {
        await panel.reload();
      }
    } catch (error: any) {
      Craft.cp?.displayError?.(
        error?.response?.data?.message ?? t('A server error occurred.')
      );
    }
  }

  /**
   * Posts to an action the browser should handle itself — a file download,
   * which can't come back through Inertia.
   */
  function submitNativeForm(action: string, params: UiValues): void {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = action;
    form.hidden = true;

    const fields = {...params};
    if (Craft.csrfTokenName) {
      fields[Craft.csrfTokenName] = Craft.csrfTokenValue;
    }

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
   * Swaps the asset's file for a newly uploaded one through the native
   * uploader, then reloads so the filename, size, dimensions, and thumbnail all
   * come back from the server together.
   */
  function replaceFile(assetId: number): void {
    const input = document.createElement('input');
    input.type = 'file';
    input.name = 'replaceFile';
    input.hidden = true;
    document.body.append(input);

    const uploaderSettings = {
      dropZone: null,
      fileInput: input,
      paramName: 'replaceFile',
      replace: true,
    };
    Object.assign(uploaderSettings, {
      on: {
        done: ({result}) => {
          if (result?.error) {
            Craft.cp?.displayError?.(result.error);

            return;
          }

          Craft.cp?.displayNotice?.(t('New file uploaded.'));
          Craft.broadcaster?.postMessage({event: 'saveElement', id: assetId});
          reload();
        },
        fail: ({error, canceled}) => {
          if (!canceled) {
            Craft.cp?.displayError?.(
              error instanceof Error ? error.message : t('Replace file failed.')
            );
          }
        },
        settled: () => input.remove(),
      } satisfies UploaderCallbacks,
    });
    const uploader = new Uploader(input, uploaderSettings);

    uploader.setParams({assetId});
    input.click();
  }

  return (items: Array<ElementActionMenuItem>): Array<ActionItem> =>
    items.map(
      (item): ActionItem =>
        item.type === 'hr'
          ? {type: 'hr'}
          : {
              label: item.label,
              icon: item.icon,
              iconColor: item.color,
              variant: item.destructive ? 'danger' : undefined,
              onClick: () => dispatch(item.behavior, item.destructive),
            }
    );
}

/** One element's action menu, kept in step with the descriptors it's given. */
export function useElementActionMenu(
  items: () => Array<ElementActionMenuItem>,
  options: Options = {}
): ComputedRef<Array<ActionItem>> {
  const toActionItems = createElementActionMenu(options);

  return computed(() => toActionItems(items()));
}
