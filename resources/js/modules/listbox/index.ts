import {Listbox, type ListboxSettings} from './listbox';
import CraftListbox from '@/modules/listbox/listbox.ce';
import {defineElement} from '@/common/web-components';
import {jq} from '@/common/utils/jquery';
import {registerCraftGlobals} from '@/common/craft-global';

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
    const $ = jq();
    if ($ && settings && settings.onChange instanceof Function) {
      const original: Function = settings.onChange;
      settings = {
        ...settings,
        onChange: (option, index) => original.call(this, $(option), index),
      };
    }
    super.setSettings(settings, defaults);
  }
}

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.Listbox($container, {…})` keeps working.
registerCraftGlobals({Listbox: CraftListboxGlobal});

defineElement('craft-listbox', CraftListbox);

export {Listbox, CraftListbox};
