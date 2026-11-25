import {isPlainObject, param} from './params';
import {rtrim, trim} from './strings';
import {ConfigService} from '@craftcms/ui/services/Config';

type UrlParams = Record<string, unknown> | string;

interface UrlConfig {
  baseUrl?: string;
  baseCpUrl?: string;
  baseSiteUrl?: string;
  actionUrl?: string;
}

function config(): UrlConfig {
  return (window as {Craft?: UrlConfig}).Craft ?? {};
}

function queryParams(query: string): Record<string, unknown> {
  const params: Record<string, unknown> = {};
  new URLSearchParams(query).forEach((value, key) => {
    params[key] = value;
  });

  return params;
}

function isEmpty(obj: Record<string, unknown>): boolean {
  for (const _ in obj) {
    return false;
  }

  return true;
}

/**
 * Builds a URL from a path, query params and a base URL (the site's base URL
 * by default). Absolute paths and URLs are returned with just the params and
 * anchor applied. A `#` key in object params sets the anchor.
 */
export function getUrl(
  path?: string,
  params?: UrlParams,
  baseUrl?: string
): string {
  if (typeof path !== 'string') {
    path = '';
  }

  // Normalize the params
  let anchor: string | null = null;
  let normalized: Record<string, unknown>;
  if (isPlainObject(params)) {
    if (params['#'] !== undefined) {
      anchor = String(params['#']);
      delete params['#'];
    }
    normalized = params;
  } else if (typeof params === 'string') {
    const anchorPos = params.indexOf('#');
    if (anchorPos !== -1) {
      anchor = params.substring(anchorPos + 1);
      params = params.substring(0, anchorPos);
    }
    normalized = queryParams(params);
  } else {
    normalized = {};
  }

  // An anchor on the path is kept unless the params set a new one
  const anchorPos = path.indexOf('#');
  if (anchorPos !== -1) {
    anchor ||= path.substring(anchorPos + 1);
    path = path.substring(0, anchorPos);
  }

  // Merge any query string already on the path
  let qsPos = path.indexOf('?');
  if (qsPos !== -1) {
    const pathParams = queryParams(path.substring(qsPos + 1));
    for (const [key, value] of Object.entries(normalized)) {
      if (value !== undefined) {
        pathParams[key] = value;
      }
    }
    normalized = pathParams;
    path = path.substring(0, qsPos);
  }

  const suffix = () =>
    (!isEmpty(normalized) ? `?${param(normalized)}` : '') +
    (anchor ? `#${anchor}` : '');

  // Absolute URLs and paths only get the params and anchor
  if (path.search('://') !== -1 || path[0] === '/') {
    return path + suffix();
  }

  path = trim(path, '/');
  let url = baseUrl || config().baseUrl || '';

  // Merge any query string already on the base URL
  qsPos = url.indexOf('?');
  if (qsPos !== -1) {
    normalized = Object.assign(
      {},
      queryParams(url.substring(qsPos + 1)),
      normalized
    );
    url = url.substring(0, qsPos);
  }

  if (path) {
    url = `${rtrim(url, '/')}/${path}`;
  }

  return url + suffix();
}

/** A control panel URL. */
export function getCpUrl(path?: string, params?: UrlParams): string {
  const {baseCpUrl} = config();
  if (!baseCpUrl) {
    throw new Error('Craft.baseCpUrl is undefined.');
  }

  return getUrl(path, params, baseCpUrl);
}

/** A front-end site URL. */
export function getSiteUrl(path?: string, params?: UrlParams): string {
  return getUrl(path, params, config().baseSiteUrl);
}

/** A controller action URL, such as `getActionUrl('elements/edit')`. */
export function getActionUrl(action: string, params?: UrlParams): string {
  return getUrl(action, params, config().actionUrl);
}

/** The query param the server reads the page number from. */
export function pageParam(): string {
  return ConfigService.getInstance().get('pageTrigger', 'page');
}
