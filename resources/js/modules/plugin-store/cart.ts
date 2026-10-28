import {t} from '@craftcms/ui';
import {formatPrice, isEditionFree} from './pricing';
import type {
  Cart,
  CartItemData,
  CartLineItem,
  CmsEdition,
  ExpiryDateOption,
  PluginLicenseInfo,
  PluginStoreContext,
  PluginSummary,
} from './types';

/** Reports a failed cart change, preferring the API's own explanation. */
export function showError(error: unknown, fallback: string): void {
  const message =
    error instanceof Error && error.message ? error.message : fallback;
  window.Craft?.cp?.displayError?.(message);
}

export type NewCartItem =
  | {type: 'plugin-edition'; plugin: string; edition: string}
  | {type: 'cms-edition'; edition: string};

/** License key options hold `new:…` until the license exists, and those can't be sent back. */
function existingLicenseKey(key: string | undefined): string | undefined {
  return key && !key.startsWith('new') ? key : undefined;
}

/** The cart's line items, in the shape the API takes them back. */
export function cartItemsData(
  cart: Cart | null,
  cmsLicenseKey: string | null
): CartItemData[] {
  return (cart?.lineItems ?? []).map((lineItem) => {
    const {purchasable, options} = lineItem;
    const licenseKey = existingLicenseKey(options.licenseKey);

    return {
      type: purchasable.type,
      edition: purchasable.handle,
      ...(purchasable.type === 'plugin-edition'
        ? {plugin: purchasable.plugin?.handle, cmsLicenseKey}
        : {}),
      expiryDate: options.expiryDate,
      autoRenew: options.autoRenew,
      ...(licenseKey ? {licenseKey} : {}),
    };
  });
}

/** License issues that buying the edition would resolve. */
const resolvableIssues = ['wrong_edition', 'astray', 'no_trials'];

/**
 * A new item for the cart. A plugin whose license is in good standing keeps
 * that license, so buying upgrades it rather than issuing another.
 */
export function newCartItemData(
  item: NewCartItem,
  licenseInfo: PluginLicenseInfo | null,
  cmsLicenseKey: string | null
): CartItemData {
  const defaults = {expiryDate: '1y', autoRenew: true};

  if (item.type === 'cms-edition') {
    return {...item, ...defaults, licenseKey: cmsLicenseKey ?? undefined};
  }

  const keepsLicense =
    !!licenseInfo?.licenseKey &&
    ['valid', 'trial'].includes(licenseInfo.licenseKeyStatus) &&
    licenseInfo.licenseIssues.every((issue) =>
      resolvableIssues.includes(issue)
    );

  return {
    ...item,
    ...defaults,
    cmsLicenseKey,
    ...(keepsLicense ? {licenseKey: licenseInfo.licenseKey!} : {}),
  };
}

/**
 * Whether a license notice's "buy" link should add the plugin: it has to cost
 * something, and the install mustn't already hold a working license for it.
 */
export function isPluginBuyable(
  plugin: Pick<PluginSummary, 'editions'>,
  info: PluginLicenseInfo | null
): boolean {
  if (!plugin.editions[0] || isEditionFree(plugin.editions[0])) {
    return false;
  }

  if (!info?.isInstalled) {
    return true;
  }

  return !(
    info.licenseKey &&
    info.licenseKeyStatus !== 'trial' &&
    !info.licenseIssues.includes('mismatched')
  );
}

export function isPluginInCart(
  cart: Cart | null,
  pluginId: number,
  editionHandle?: string
): boolean {
  return !!cart?.lineItems.some(
    (lineItem) =>
      lineItem.purchasable.pluginId === pluginId &&
      (!editionHandle || lineItem.purchasable.handle === editionHandle)
  );
}

/**
 * Whether the buyer can pay for more than the first year of updates up front.
 * Only new licenses and trials can; an existing license renews on its own date.
 */
export function canExtendUpdates(
  lineItem: CartLineItem,
  licenseInfo: PluginLicenseInfo | null
): boolean {
  return (
    lineItem.purchasable.type === 'cms-edition' ||
    lineItem.options.licenseKey.startsWith('new:') ||
    !!licenseInfo?.isTrial
  );
}

/** The years of updates a line item can be bought with, priced against its current choice. */
export function updateOptions(
  lineItem: CartLineItem,
  expiryDateOptions: ExpiryDateOption[]
): Array<{value: string; label: string}> {
  const renewalPrice = Number(lineItem.purchasable.renewalPrice);
  const selected = Math.max(
    0,
    expiryDateOptions.findIndex(
      ([value]) => value === lineItem.options.expiryDate
    )
  );

  return expiryDateOptions.map(([value], index) => {
    const years = index + 1;
    const difference = renewalPrice * (index - selected);
    let label = t(
      '{num, number} {num, plural, =1{year} other{years}} of updates',
      {num: years}
    );

    if (years === 1) {
      label += ` ${t('(included)')}`;
    }

    if (difference !== 0) {
      label += ` (${difference > 0 ? '+' : ''}${formatPrice(difference)})`;
    }

    return {value, label};
  });
}

export interface TrialRow {
  item: NewCartItem;
  name: string;
  iconUrl: string | null;
  editionName: string;
  /** Only worth showing when there's more than one edition to tell apart. */
  showEdition: boolean;
  price: number;
  /** What it costs as an upgrade from the edition already licensed. */
  upgradePrice: number | null;
  licenseMismatched: boolean;
  pluginHandle: string | null;
}

/** The Craft edition being trialed, when it's higher than the licensed one. */
export function cmsTrialRow(
  context: PluginStoreContext,
  editions: CmsEdition[],
  craftIconUrl: string
): TrialRow | null {
  const current = context.editions.indexOf(context.edition);
  const licensed = context.licensedEdition
    ? context.editions.indexOf(context.licensedEdition)
    : -1;

  if (licensed >= current) {
    return null;
  }

  const trialed = editions.find((e) => e.handle === context.edition);

  if (!trialed?.price || Number(trialed.price) === 0) {
    return null;
  }

  return {
    item: {type: 'cms-edition', edition: trialed.handle},
    name: 'Craft CMS',
    iconUrl: craftIconUrl,
    editionName: trialed.name,
    showEdition: true,
    price: Number(trialed.price),
    upgradePrice: null,
    licenseMismatched: false,
    pluginHandle: null,
  };
}

export function pluginTrialRow(
  plugin: PluginSummary,
  info: PluginLicenseInfo | null
): TrialRow | null {
  const edition = plugin.editions.find((e) => e.handle === info?.edition);

  if (!info || !edition || isEditionFree(edition)) {
    return null;
  }

  const licensed = plugin.editions.find(
    (e) => e.handle === info.licensedEdition
  );
  const upgradable =
    licensed &&
    licensed.handle !== edition.handle &&
    Number(licensed.price) > 0 &&
    ['valid', 'astray'].includes(info.licenseKeyStatus);

  return {
    item: {
      type: 'plugin-edition',
      plugin: plugin.handle,
      edition: edition.handle,
    },
    name: plugin.name,
    iconUrl: plugin.iconUrl,
    editionName: edition.name,
    showEdition: plugin.editions.length > 1,
    price: Number(edition.price),
    upgradePrice: upgradable
      ? Number(edition.price) - Number(licensed.price)
      : null,
    licenseMismatched: info.licenseKeyStatus === 'mismatched',
    pluginHandle: plugin.handle,
  };
}

/** Trials that aren't in the cart yet. */
export function pendingTrialRows(
  rows: TrialRow[],
  cart: Cart | null
): TrialRow[] {
  return rows.filter(
    ({item}) =>
      !cart?.lineItems.some(
        (lineItem) =>
          lineItem.purchasable.type === item.type &&
          (item.type === 'cms-edition'
            ? lineItem.purchasable.handle === item.edition
            : lineItem.purchasable.plugin?.handle === item.plugin)
      )
  );
}
