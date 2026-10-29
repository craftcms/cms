import {t} from '@craftcms/ui';
import type {PluginEdition, PluginSummary} from './types';

/** Whole amounts drop their cents, as the store has always shown them. */
export function formatPrice(value: number | string | null): string {
  const amount = Number(value ?? 0);
  const fractionDigits = Number.isInteger(amount) ? 0 : 2;

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  }).format(amount);
}

export function isEditionFree(edition: PluginEdition): boolean {
  return edition.price === null || Number(edition.price) === 0;
}

export function isPluginFree(plugin: Pick<PluginSummary, 'editions'>): boolean {
  return plugin.editions.every(isEditionFree);
}

/** Nothing to choose or pay for, so its page leads with how to install it. */
export function hasSingleFreeEdition(
  plugin: Pick<PluginSummary, 'editions'>
): boolean {
  return plugin.editions.length === 1 && isPluginFree(plugin);
}

export function priceRangeLabel(
  plugin: Pick<PluginSummary, 'editions'>
): string {
  const prices = plugin.editions.map((edition) => Number(edition.price ?? 0));
  const label = (price: number) => (price > 0 ? formatPrice(price) : t('Free'));
  const min = Math.min(...prices);
  const max = Math.max(...prices);

  return min === max ? label(min) : `${label(min)}–${label(max)}`;
}
