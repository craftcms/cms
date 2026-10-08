import {
  applyOverrideSettings,
  renderOverrideSettings,
} from '@actions/Settings/EntryTypesController';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';

// `Craft` and `$` (jQuery) remain page globals. The entry-type override editor
// is a still-jQuery `Craft.Slideout` orchestrating server-rendered settings
// HTML, so the same legacy seams the rest of the manager leans on survive here
// (`Craft.sendActionRequest`, `Craft.Slideout`, `Craft.ui`,
// `Craft.initUiElements`, `Craft.cp.displayError`).
declare const Craft: any;
declare const $: any;

/**
 * Open the per-field entry-type override editor for a chip — the port of
 * `Craft.EntryTypeSelectInput.createSettings`/`applySettings` onto the
 * web-component `<craft-component-select>` path. POSTs the chip's current
 * `{id, group, name?, handle?, description?}` JSON to
 * the override-settings action route, opens a slideout with the returned
 * settings form — a Vue panel when the page has the Vue slideout stack, a
 * `Craft.Slideout` otherwise — and on submit applies the overrides back onto the
 * chip (label + indicators + hidden-input JSON) via {@link writeOverrides}.
 *
 * Self-contained and chip-local: overriding an entry type's name/handle/
 * description changes neither its id nor its group, so no manager-level
 * refresh is needed. `<craft-entry-type-manager>` wires the "Settings" chip
 * action that calls this (see `handleDefineChipActions`).
 */
export async function editEntryTypeOverrides(chip: HTMLElement): Promise<void> {
  const input = chip.querySelector('input');
  if (!input) {
    return;
  }

  let config: unknown;
  try {
    config = JSON.parse(input.value);
  } catch {
    config = null;
  }
  // Chips added without an input value hook carry a bare id.
  if (typeof config !== 'object' || config === null) {
    config = {id: Number(chip.dataset.id)};
  }

  let data;
  try {
    const response = await Craft.sendActionRequest(
      'POST',
      renderOverrideSettings().url,
      {data: config}
    );
    data = response.data;
  } catch (e: any) {
    Craft.cp.displayError(e?.response?.data?.message);
    throw e;
  }

  if (Craft.openSlideout instanceof Function) {
    await openVueSlideout(chip, data);
    return;
  }

  const settingsNamespace = data.namespace;
  const slideout = await createSlideout(data);

  slideout.$container.on('submit', (ev: Event) => {
    ev.preventDefault();
    void applyOverrides(chip, slideout, settingsNamespace);
  });
  slideout.on('close', () => {
    slideout.destroy();
  });
}

interface OverrideSettingsContext {
  fragment: {html: string; headHtml: string; bodyHtml: string};
  /** Rejects with the request error when the overrides don't validate. */
  apply: (settings: string) => Promise<void>;
}

const contexts = new Map<string, OverrideSettingsContext>();
let nextContextId = 0;

export function takeOverrideSettingsContext(
  id: string
): OverrideSettingsContext {
  const context = contexts.get(id);
  contexts.delete(id);
  if (!context) {
    throw new Error('Entry type override settings context was not found.');
  }
  return context;
}

/**
 * Opens the overrides in a Vue slideout. The Vue side is imported on demand:
 * the manager also loads on legacy pages, which have no Vue slideout stack.
 */
async function openVueSlideout(chip: HTMLElement, data: any): Promise<void> {
  const [{openSlideoutWith}, {default: EntryTypeOverrideSettings}] =
    await Promise.all([
      import('@/common/slideouts'),
      import('./EntryTypeOverrideSettings.vue'),
    ]);

  const contextId = `entry-type-overrides-${++nextContextId}`;
  contexts.set(contextId, {
    fragment: {
      html: data.settingsHtml,
      headHtml: data.headHtml ?? '',
      bodyHtml: data.bodyHtml ?? '',
    },
    apply: async (settings) => {
      writeOverrides(
        chip,
        await requestOverrides(chip, data.namespace, settings)
      );
    },
  });

  const name = chip.querySelector(
    ':scope > [id$="-label"] > :first-child'
  )?.textContent;

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its
  // Inertia page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    EntryTypeOverrideSettings as InertiaPageComponent,
    {
      contextId,
      title: Craft.t('app', '{label} Settings', {
        label: name?.trim() ?? chip.dataset.label,
      }),
    },
    {opener: chip}
  );

  if (!panel) {
    contexts.delete(contextId);
  }
}

/** Build the override-settings `Craft.Slideout` (legacy `createSlideout`). */
async function createSlideout(data: any): Promise<any> {
  const $body = $('<div/>', {class: 'entry-type-override-settings-body'});
  $('<div/>', {class: 'fields', html: data.settingsHtml}).appendTo($body);

  const $footer = $('<div/>', {class: 'entry-type-override-settings-footer'});
  $('<div/>', {class: 'flex-grow'}).appendTo($footer);
  const $cancelBtn = Craft.ui
    .createButton({label: Craft.t('app', 'Close'), spinner: true})
    .appendTo($footer);
  Craft.ui
    .createSubmitButton({
      class: 'secondary',
      label: Craft.t('app', 'Apply'),
      spinner: true,
    })
    .appendTo($footer);

  const slideout = new Craft.Slideout($body.add($footer), {
    containerElement: 'form',
    containerAttributes: {
      action: '',
      method: 'post',
      novalidate: '',
      class: 'entry-type-override-settings',
    },
  });

  slideout.on('open', () => {
    // Hold off a frame until it's positioned, then focus the first text input.
    requestAnimationFrame(() => {
      slideout.$container.find('.text:first').focus();
    });
  });

  $cancelBtn.on('click', () => {
    slideout.close();
  });

  if (data.headHtml) {
    await Craft.appendHeadHtml(data.headHtml);
  }
  if (data.bodyHtml) {
    await Craft.appendBodyHtml(data.bodyHtml);
  }

  Craft.initUiElements(slideout.$container);

  return slideout;
}

/** Submit the legacy panel's override form and apply the result to the chip. */
async function applyOverrides(
  chip: HTMLElement,
  slideout: any,
  settingsNamespace: string
): Promise<void> {
  const $submitBtn = slideout.$container
    .find('button[type=submit]')
    .addClass('loading');

  // Clear any errors from a previous submit.
  slideout.$container
    .find('.field.has-errors')
    .each((_i: number, field: HTMLElement) => {
      const $field = $(field);
      $field.removeClass('has-errors');
      $field.children('.input').removeClass('errors prevalidate');
      $field.children('ul.errors').remove();
    });

  try {
    let data;

    try {
      data = await requestOverrides(
        chip,
        settingsNamespace,
        slideout.$container.serialize()
      );
    } catch (e: any) {
      const errors = e?.response?.data?.errors;
      if (errors) {
        Object.entries(errors).forEach(([name, fieldErrors]) => {
          const $field = slideout.$container.find(`[data-error-key="${name}"]`);
          if ($field.length) {
            Craft.ui.addErrorsToField($field, fieldErrors);
          }
        });
      }

      Craft.cp.displayError(e?.response?.data?.message);
      throw e;
    }

    writeOverrides(chip, data);

    slideout.close();
    slideout.destroy();
  } finally {
    $submitBtn.removeClass('loading');
  }
}

async function requestOverrides(
  chip: HTMLElement,
  settingsNamespace: string,
  settings: string
): Promise<any> {
  const response = await Craft.sendActionRequest(
    'POST',
    applyOverrideSettings().url,
    {data: {id: chip.dataset.id, settingsNamespace, settings}}
  );
  return response.data;
}

/**
 * Write applied overrides back onto the chip (legacy `applySettings`): swap
 * the label's contents, then rewrite the hidden-input JSON with the new
 * `{id, name, handle, description}` config while preserving the group the
 * manager stamped.
 */
function writeOverrides(chip: HTMLElement, data: any): void {
  // Swap the label's contents (name/handle/description/indicators), keeping
  // the existing label node so its `id` + aria wiring stay intact — the
  // modern `craft-chip` markup labels by an `id$="-label"` element, not the
  // legacy `.chip-label` class the old input replaced wholesale.
  const label = chip.querySelector<HTMLElement>(':scope > [id$="-label"]');
  const template = document.createElement('template');
  template.innerHTML = (data.chipHtml ?? '').trim();
  const newLabel = template.content.querySelector<HTMLElement>(
    'craft-chip > [id$="-label"]'
  );
  if (label && newLabel) {
    label.innerHTML = newLabel.innerHTML;
  }

  // Write the new override config into the hidden input, preserving the
  // group the manager stamped (legacy `applySettings`).
  const input = chip.querySelector('input');
  if (input) {
    const config = {...data.config};
    try {
      const group = JSON.parse(input.value).group;
      if (group) {
        config.group = group;
      }
    } catch {
      // Not a JSON value — nothing to preserve.
    }
    input.value = JSON.stringify(config);
    // Programmatic value changes don't fire `change`; announce it so
    // wrapping form controls pick up the new override config.
    input.dispatchEvent(new Event('change', {bubbles: true}));
  }

  // Re-init any UI within the chip (description info icon, indicator tooltips).
  Craft.initUiElements($(chip));
}
