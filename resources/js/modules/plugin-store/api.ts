import {sendApiRequest} from '@craftcms/ui/utilities/api/apiClient';
import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
import PluginsController from '@actions/App/PluginsController';
import type {
  Cart,
  CartItemData,
  ChangelogRelease,
  CmsEdition,
  CoreData,
  Developer,
  FeaturedSection,
  Plugin,
  PluginIndexPage,
  PluginLicenseInfo,
  PluginReviewsPage,
  PluginSummary,
  SortDirection,
} from './types';

async function get<T>(
  uri: string,
  params?: Record<string, unknown>,
  signal?: AbortSignal
): Promise<T> {
  const {data} = await sendApiRequest('GET', uri, {params, signal});

  // Some endpoints report a failure with a 200 and an `error` key.
  if (data && typeof data === 'object' && 'error' in data) {
    throw new Error(String(data.error));
  }

  return data as T;
}

export interface PluginIndexQuery {
  page: number;
  orderBy?: string;
  direction?: SortDirection;
}

/** The ways a plugin listing can be narrowed. */
export type PluginIndexFilter =
  | {categoryId: number}
  | {developerId: number}
  | {searchQuery: string}
  | {featuredSectionHandle: string};

const perPage = 96;

export function getCoreData(signal?: AbortSignal) {
  return get<CoreData>('plugin-store/core-data', undefined, signal);
}

export function getFeaturedSections(signal?: AbortSignal) {
  return get<FeaturedSection[]>(
    'plugin-store/featured-sections',
    undefined,
    signal
  );
}

export function getFeaturedSection(handle: string, signal?: AbortSignal) {
  return get<FeaturedSection>(
    `plugin-store/featured-section/${handle}`,
    undefined,
    signal
  );
}

export function getPlugins(
  filter: PluginIndexFilter,
  query: PluginIndexQuery,
  signal?: AbortSignal
) {
  const params = {perPage, ...query};

  if ('featuredSectionHandle' in filter) {
    return get<PluginIndexPage>(
      `plugin-store/plugins-by-featured-section/${filter.featuredSectionHandle}`,
      params,
      signal
    );
  }

  return get<PluginIndexPage>(
    'plugin-store/plugins',
    {...params, ...filter},
    signal
  );
}

export function getPluginsByHandles(handles: string[], signal?: AbortSignal) {
  return get<Array<PluginSummary | null>>(
    'plugin-store/plugins-by-handles',
    {pluginHandles: handles.join(',')},
    signal
  );
}

export function getPlugin(handle: string, signal?: AbortSignal) {
  return get<Plugin>(
    `plugin-store/plugin/${handle}`,
    {withInstallHistory: true, withIssueStats: true, withReviewStats: true},
    signal
  );
}

export function getPluginChangelog(pluginId: number, signal?: AbortSignal) {
  return get<ChangelogRelease[]>(
    `plugin/${pluginId}/changelog`,
    undefined,
    signal
  );
}

export interface PluginReviewsQuery {
  page: number;
  orderBy: 'dateCreated' | 'rating';
  direction: SortDirection;
}

export function getPluginReviews(
  handle: string,
  query: PluginReviewsQuery,
  signal?: AbortSignal
) {
  return get<PluginReviewsPage>(`plugin/${handle}/reviews`, {...query}, signal);
}

export function getPluginsByIds(ids: number[], signal?: AbortSignal) {
  return get<PluginSummary[]>('plugins', {ids: ids.join(',')}, signal);
}

export function getCmsEditions(signal?: AbortSignal) {
  return get<{editions: CmsEdition[]}>('cms-editions', undefined, signal);
}

export async function createCart(email: string | null): Promise<Cart> {
  const {data} = await sendApiRequest('POST', 'carts', {data: {email}});
  return data.cart;
}

export async function getCart(number: string): Promise<Cart> {
  return (await get<{cart: Cart}>(`carts/${number}`)).cart;
}

/** Replaces the cart's contents. Rejects with the API's errors when it won't take them. */
export async function updateCart(
  number: string,
  items: CartItemData[]
): Promise<Cart> {
  const {data} = await sendApiRequest('POST', `carts/${number}`, {
    data: {items},
  });

  if (data?.errors) {
    throw new CartError(data.errors);
  }

  return data.cart;
}

export class CartError extends Error {
  constructor(readonly errors: Array<{message?: string}>) {
    super(errors[0]?.message ?? 'Couldn’t update the cart.');
  }
}

export function getDeveloper(developerId: number, signal?: AbortSignal) {
  return get<Developer>(`developer/${developerId}`, undefined, signal);
}

/**
 * Asks the API which plugin licenses belong to this install, then asks Craft
 * how each of those plugins stands locally.
 */
export async function getPluginLicenseInfo(): Promise<
  Record<string, PluginLicenseInfo>
> {
  const {license} = await get<{license: {pluginLicenses?: unknown[]}}>(
    'cms-licenses',
    {include: 'plugins'}
  );

  const response = await actionClient.post<Record<string, PluginLicenseInfo>>(
    PluginsController.getLicenseInfo().url,
    {pluginLicenses: license.pluginLicenses ?? []}
  );

  return response.data;
}
