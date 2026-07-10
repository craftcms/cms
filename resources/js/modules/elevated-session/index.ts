import {ElevatedSessionForm} from '@/modules/elevated-session/elevated-session-form';
import CraftElevatedSessionForm from '@/modules/elevated-session/elevated-session-form.ce';
import {defineElement} from '@/common/web-components';

/**
 * Assign onto the legacy `Craft` global so the Twig/PHP-emitted
 * `new Craft.ElevatedSessionForm(form, [selectors])` keeps working. Nothing
 * subclasses it via legacy `.extend()`, so — like `SortableCheckboxSelect` — it
 * needs no compat shim and is assigned as the modern ES class directly.
 *
 * The elevated-session *manager* (the timeout check + login modal) remains the
 * legacy `Craft.elevatedSessionManager`; this class delegates to it.
 */
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.ElevatedSessionForm = ElevatedSessionForm;

defineElement('craft-elevated-session-form', CraftElevatedSessionForm);

export {ElevatedSessionForm, CraftElevatedSessionForm};
export {
  requireElevatedSession,
  ElevatedSessionCancelled,
} from '@/modules/elevated-session/elevated-session';
