import {isEditionFree} from './pricing';
import type {
  Plugin,
  PluginEdition,
  PluginLicenseInfo,
  PluginStoreContext,
} from './types';

export type PurchaseAction =
  | {kind: 'in-cart'}
  | {kind: 'add-to-cart'; disabled: boolean}
  | {kind: 'licensed'};

export type InstallAction =
  | {
      kind: 'install' | 'try' | 'reactivate';
      /** An installed plugin changes edition in place rather than going through the installer. */
      method: 'install' | 'switch';
      primary: boolean;
      disabled: boolean;
    }
  | {kind: 'installed' | 'installed-trial'};

export type CompatibilityNotice =
  | {kind: 'partially-compatible'; version: string}
  | {kind: 'incompatible'}
  | {kind: 'php'; required: string | null; current: string | null}
  | {kind: 'composer-php'; required: string | null; current: string | null}
  | {kind: 'cms-edition'; edition: string}
  | {kind: 'abandoned'};

export interface EditionActions {
  purchase: PurchaseAction | null;
  install: InstallAction | null;
  notice: CompatibilityNotice | null;
}

export interface EditionActionsInput {
  plugin: Plugin;
  edition: PluginEdition;
  licenseInfo: PluginLicenseInfo | null;
  context: PluginStoreContext;
  inCart: boolean;
}

export function isCmsEditionCompatible(
  plugin: Pick<Plugin, 'minCmsEdition'>,
  context: Pick<PluginStoreContext, 'edition' | 'editions'>
): boolean {
  if (!plugin.minCmsEdition || plugin.minCmsEdition === 'solo') {
    return true;
  }

  const installed = context.editions.indexOf(context.edition);
  const required = context.editions.indexOf(plugin.minCmsEdition);

  return installed !== -1 && required !== -1 && installed >= required;
}

/**
 * An edition can be bought when it costs more than the one already licensed,
 * so a license can only ever be upgraded.
 */
function isUpgrade(
  plugin: Plugin,
  edition: PluginEdition,
  licenseInfo: PluginLicenseInfo | null
): boolean {
  const licensed = plugin.editions.find(
    (e) => e.handle === licenseInfo?.licensedEdition
  );

  return !(
    licensed &&
    edition.price &&
    Number(edition.price) <= Number(licensed.price)
  );
}

/**
 * What the edition costs as an upgrade from the one already licensed, or null
 * when it's bought outright.
 */
export function upgradePrice(
  plugin: Pick<Plugin, 'editions'>,
  edition: PluginEdition,
  licenseInfo: PluginLicenseInfo | null
): number | null {
  const licensed = plugin.editions.find(
    (e) => e.handle === licenseInfo?.licensedEdition
  );

  if (
    !licensed ||
    licensed.handle === edition.handle ||
    !Number(licensed.price) ||
    !['valid', 'astray'].includes(licenseInfo?.licenseKeyStatus ?? '')
  ) {
    return null;
  }

  return Number(edition.price) - Number(licensed.price);
}

/** Decides which buttons an edition offers, and why any are unavailable. */
export function resolveEditionActions({
  plugin,
  edition,
  licenseInfo,
  context,
  inCart,
}: EditionActionsInput): EditionActions {
  const free = isEditionFree(edition);
  const installed = licenseInfo?.isInstalled ?? false;
  const currentEdition = licenseInfo?.edition ?? null;
  const licensedEdition = licenseInfo?.licensedEdition ?? null;
  const upgrade = isUpgrade(plugin, edition, licenseInfo);
  const cmsCompatible = isCmsEditionCompatible(plugin, context);
  const compatible =
    !!plugin.latestCompatibleVersion &&
    plugin.phpVersionCompatible &&
    cmsCompatible;

  let purchase: PurchaseAction | null = null;

  if (!free) {
    if (inCart) {
      purchase = context.allowUpdates ? {kind: 'in-cart'} : null;
    } else if (context.allowUpdates && upgrade) {
      purchase = {
        kind: 'add-to-cart',
        disabled:
          !compatible ||
          licenseInfo?.licenseKeyStatus === 'mismatched' ||
          plugin.abandoned,
      };
    } else if (licensedEdition === edition.handle) {
      purchase = {kind: 'licensed'};
    }
  }

  let install: InstallAction | null = null;

  if (installed && currentEdition === edition.handle) {
    install = {
      kind:
        currentEdition !== licensedEdition && !free
          ? 'installed-trial'
          : 'installed',
    };
  } else if (context.allowUpdates || installed) {
    const method = installed ? 'switch' : 'install';

    if (free) {
      install = {kind: 'install', method, primary: true, disabled: !compatible};
    } else if (
      (upgrade && currentEdition === edition.handle) ||
      (licensedEdition === edition.handle && !currentEdition)
    ) {
      install = {
        kind: 'install',
        method,
        primary: false,
        disabled: !compatible,
      };
    } else if (upgrade) {
      const usable = !licenseInfo || (installed && licenseInfo.isEnabled);
      install = {
        kind: 'try',
        method,
        primary: false,
        disabled: !usable || !compatible,
      };
    } else if (currentEdition && licensedEdition === edition.handle) {
      install = {kind: 'reactivate', method, primary: false, disabled: false};
    }
  }

  return {
    purchase,
    install,
    notice: compatibilityNotice(plugin, context, free),
  };
}

function compatibilityNotice(
  plugin: Plugin,
  context: PluginStoreContext,
  free: boolean
): CompatibilityNotice | null {
  if (!plugin.latestCompatibleVersion) {
    return {kind: 'incompatible'};
  }

  if (plugin.latestCompatibleVersion !== plugin.version) {
    return {
      kind: 'partially-compatible',
      version: plugin.latestCompatibleVersion,
    };
  }

  if (!plugin.phpVersionCompatible) {
    return plugin.incompatiblePhpVersion === 'php'
      ? {
          kind: 'php',
          required: plugin.phpConstraint,
          current: context.phpVersion,
        }
      : {
          kind: 'composer-php',
          required: plugin.phpConstraint,
          current: context.composerPhpVersion,
        };
  }

  if (!isCmsEditionCompatible(plugin, context)) {
    return {kind: 'cms-edition', edition: plugin.minCmsEdition ?? ''};
  }

  if (!free && plugin.abandoned) {
    return {kind: 'abandoned'};
  }

  return null;
}
