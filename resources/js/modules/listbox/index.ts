import {Listbox, type ListboxSettings} from './Listbox';
import CraftListbox from '@/modules/listbox/Listbox.wc';
import {defineElement} from '@/common/web-components';

/**
 * Legacy programmatic callers (`CustomizeSourcesModal`, `BaseElementIndex`,
 * `Preview`, `AssetImageEditor`) construct `new Craft.Listbox($container, {…})`
 * and expect their `onChange` to receive a **jQuery-wrapped** option (they call
 * `$selectedOption.data('mode')`, `.data('value')`, `.switchDeviceType($opt)`,
 * etc.). The modern {@link Listbox} hands `onChange` a RAW element, so the
 * global is a thin subclass that re-wraps the option with jQuery at
 * `setSettings` time before delegating to the user's callback.
 *
 * The `<craft-listbox>` custom element uses the bare {@link Listbox} directly
 * and works with raw elements — it does NOT go through this jQuery-wrapping
 * global.
 */
class CraftListboxGlobal extends Listbox {
  // legacy callers expect onChange to receive a jQuery-wrapped option
  override setSettings(
    settings?: Partial<ListboxSettings>,
    defaults?: Partial<ListboxSettings>
  ): void {
    const $ = (window as any).$ ?? (window as any).jQuery;
    if ($ && settings && typeof settings.onChange === 'function') {
      const orig = settings.onChange as (...args: any[]) => void;
      settings = {
        ...settings,
        onChange: ((option: any, ...rest: any[]) =>
          orig.call(this, $(option), ...rest)) as any,
      };
    }
    super.setSettings(settings, defaults);
  }
}

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.Listbox($container, {…})` keeps working.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.Listbox = CraftListboxGlobal;

defineElement('craft-listbox', CraftListbox);

export {Listbox, CraftListbox};
