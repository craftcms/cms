import {usePage} from '@inertiajs/vue3';

export interface CraftData {
  system: {
    name: string;
    icon: {
      url: string;
      name: string;
    } | null;
  };
  app: {
    version: string;
    edition: {
      name: 'Solo' | 'Team' | 'Pro' | 'Enterprise';
      handle: 'solo' | 'team' | 'pro' | 'enterprise';
      value: 0 | 1 | 2 | 3;
    };
  };
  site: {
    url: string;
  };
  currentUser: any;
  nav: any[];
  [key: string]: any;
}

function getUrl(baseUrl: string, path: string) {
  const url = new URL(baseUrl);
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  url.pathname = `${url.pathname}/${cleanPath}`;
  return url.toString();
}

/**
 * @TODO move to NPM package
 */
export function useHelpers() {
  const craftData = useCraftData();

  return {
    // @TODO move to NPM package
    getActionUrl(action: string) {
      return `${craftData.actionUrl}${action}`;
      // return getUrl(craftData.actionUrl, action);
    },
    // @TODO move to NPM package
    getCpUrl(action: string) {
      return `${craftData.cpUrl}${action}`;
    },
    getApiUrl(path: string) {
      return getUrl(craftData.baseApiUrl, path);
    },
  };
}

export default function useCraftData(): CraftData {
  const page = usePage<{
    craft: CraftData;
  }>();

  // This is what Statamic does, I'm not sure if it's smart or overly complicated
  return new Proxy({} as CraftData, {
    get(target, prop: string) {
      return page.props.craft?.[prop];
    },
  });
}
