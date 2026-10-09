/**
 * Renders `Craft.VueAdminTable` with the current `AdminTable` component.
 *
 * Craft 5 plugins build their tables by calling
 * `new Craft.VueAdminTable({columns, container, tableData, …})` from a
 * `{% js %}` block. This stands in for that constructor so those tables come
 * out looking and behaving like every other table in the control panel,
 * without the plugin changing a line.
 *
 * Installed from `cp.ts` for every control panel page, but only ever reached
 * when the Yii2 adapter is present: `Craft.VueAdminTable` is a Craft 5 API, so
 * the only callers are legacy templates, and the bundle that defines it is
 * registered exclusively from the adapter
 * (`CraftCms\Cms\View\LegacyAssets\AdminTableAsset`). Without the adapter
 * nothing registers it and nothing calls it, so the accessor sits unused.
 *
 * Not every table can come across: the options in `DELEGATED_OPTIONS` describe
 * whole features (server-backed data, search, reordering, selection, bulk
 * actions) rather than cosmetic differences, and a table configured with any of
 * them is handed to the legacy implementation untouched. So the shim is an
 * upgrade where it applies and a no-op where it doesn't — nothing regresses.
 *
 * Removal: delete this file, its import in `cp.ts`, `LegacyAdminTable.vue`,
 * and `modules/admin-table/types/legacy.ts`.
 */
import {createApp, type App} from 'vue';
import LegacyAdminTable from '@/modules/admin-table/components/LegacyAdminTable.vue';
import {
  DELEGATED_OPTIONS,
  type LegacyAdminTableSettings,
} from '@/modules/admin-table/types/legacy';

/** What the legacy constructor hands back, as far as callers rely on it. */
interface LegacyAdminTableInstance {
  settings: LegacyAdminTableSettings;
  reload(): void;
  destroy(): void;
}

type LegacyAdminTableConstructor = {
  new (settings: LegacyAdminTableSettings): unknown;
  defaults?: Record<string, unknown>;
};

/**
 * Whether the shim can render this table, or the legacy implementation has to.
 *
 * Presence is the test rather than value: the constructor merges its defaults
 * after this point, so anything here was set by the caller deliberately.
 */
function isRenderable(settings: LegacyAdminTableSettings): boolean {
  return !DELEGATED_OPTIONS.some(
    (option) => settings[option] !== undefined && settings[option] !== null
  );
}

function resolveContainer(
  container: LegacyAdminTableSettings['container']
): HTMLElement | null {
  if (container instanceof HTMLElement) {
    return container;
  }

  if (typeof container !== 'string' || container === '') {
    return null;
  }

  return document.querySelector<HTMLElement>(container);
}

/**
 * Mounts the table and returns the handle the legacy constructor returned.
 *
 * Vue 3 mounts *into* the container rather than replacing it, which is what
 * makes this safe on a page whose markup the control panel already owns: the
 * container element itself is left alone, so whoever rendered it can still take
 * it away again.
 */
function render(
  settings: LegacyAdminTableSettings,
  element: HTMLElement
): LegacyAdminTableInstance {
  const app: App = createApp(LegacyAdminTable, {
    columns: settings.columns ?? [],
    tableData: settings.tableData ?? [],
    emptyMessage: settings.emptyMessage,
    deleteAction: settings.deleteAction ?? null,
    deleteConfirmationMessage: settings.deleteConfirmationMessage ?? null,
    deleteSuccessMessage: settings.deleteSuccessMessage ?? null,
    deleteFailMessage: settings.deleteFailMessage ?? null,
  });

  const instance = app.mount(element) as {reset?: () => void};

  return {
    settings,
    // The legacy `reload()` re-fetched from `tableDataEndpoint`. A table that
    // reaches the shim has no endpoint to re-fetch from, so the nearest honest
    // meaning is to put back the rows it was given.
    reload: () => instance.reset?.(),
    destroy: () => app.unmount(),
  };
}

/**
 * Installs the shim over `Craft.VueAdminTable`.
 *
 * Done as an accessor because the load order isn't fixed: on a full page load
 * the legacy bundle is a classic script and runs before this module, while on a
 * control panel visit the assets are appended in order and this runs second. A
 * plain assignment would win in one case and be overwritten in the other. The
 * getter always hands out the shim; the setter keeps whatever the legacy bundle
 * assigns so it stays available to delegate to.
 *
 * `defaults` is forwarded with it because the legacy constructor reads
 * `Craft.VueAdminTable.defaults` off the global — through this getter — when it
 * merges its settings.
 */
export function installLegacyAdminTableShim(): void {
  const craft = window.Craft;

  if (!craft) {
    return;
  }

  let legacy = (craft as {VueAdminTable?: LegacyAdminTableConstructor})
    .VueAdminTable;

  function Shim(
    this: Partial<LegacyAdminTableInstance>,
    settings: LegacyAdminTableSettings = {}
  ): unknown {
    const element = resolveContainer(settings.container);

    if (!isRenderable(settings) || !element) {
      if (!legacy) {
        throw new Error(
          'Craft.VueAdminTable: this table needs the legacy admin table bundle, which is not loaded.'
        );
      }

      return new legacy(settings);
    }

    return Object.assign(this, render(settings, element));
  }

  Shim.defaults = legacy?.defaults;

  Object.defineProperty(craft, 'VueAdminTable', {
    configurable: true,
    get: () => Shim,
    set: (value: LegacyAdminTableConstructor) => {
      legacy = value;
      Shim.defaults = value?.defaults;
    },
  });
}
