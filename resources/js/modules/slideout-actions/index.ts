import {isCtrlKeyPressed} from '@craftcms/garnish';
import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
import {openSlideout} from '@/common/slideouts';

/**
 * Window listeners for the declarative slideout actions that server-rendered
 * menus and cards carry (`{type: 'event'}` descriptors run by `runAction()`,
 * which merges the invoking element in as `detail.trigger`).
 */

function opener(trigger: unknown): HTMLElement | null {
  return trigger instanceof HTMLElement ? trigger : null;
}

// `craft:open-slideout` — open a CP screen by URL, e.g. a section's or
// volume's settings from an element's action menu.
// SAFETY: craft:open-slideout is a registered CustomEvent with a {url} payload.
window.addEventListener('craft:open-slideout', ((ev: CustomEvent) => {
  const {url, trigger} = ev.detail ?? {};

  if (typeof url !== 'string' || !url) {
    return;
  }

  void openSlideout(url, {opener: opener(trigger)});
}) as EventListener);

// `craft:edit-element` — element edit buttons and "Edit {type}" menu items
// (see `ElementHtml::cardEditButtonConfig()`). Opens the element editor, or
// the element's edit page in a new window on ctrl-click.
// SAFETY: craft:edit-element is a registered CustomEvent with the editor detail payload.
window.addEventListener('craft:edit-element', ((ev: CustomEvent) => {
  const {elementType, settings, cpEditUrl, trigger, sourceEvent} =
    ev.detail ?? {};

  if (cpEditUrl && sourceEvent && isCtrlKeyPressed(sourceEvent)) {
    window.open(cpEditUrl);
    return;
  }

  const params: Record<string, unknown> = {elementType, ...settings};

  // A card that's since been replaced by its canonical element no longer
  // carries `data-draft-id`, so its stale draft ID is dropped.
  if (
    params.draftId &&
    trigger instanceof Element &&
    !trigger.closest('[data-draft-id]')
  ) {
    delete params.draftId;
  }

  const url = EditElementController[
    '/{cpTrigger?}/{actionTrigger?}/elements/edit'
  ].url(undefined, {
    query: Object.fromEntries(
      Object.entries(params).filter(
        ([, value]) => value !== null && value !== undefined
      )
    ) as Record<string, string | number>,
  });

  void openSlideout(url, {
    opener: opener(trigger),
    // Re-announced so hosts that render element data themselves (e.g.
    // Inertia pages) can refresh it.
    onSaved: ({data, draft}) => {
      if (draft) {
        return;
      }

      window.dispatchEvent(
        new CustomEvent('craft:element-saved', {
          detail: {element: data?.element ?? null},
        })
      );
    },
  });
}) as EventListener);
