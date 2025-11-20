import {usePage} from '@inertiajs/vue3';

export interface CraftData {
  system: {
    name: string;
    icon: string;
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

/**
 * @TODO move to NPM package
 */
export function useHelpers() {
  const craftData = useCraftData();

  return {
    // @TODO move to NPM package
    getActionUrl(action: string) {
      const url = new URL(craftData.actionUrl);
      const cleanPath = action.startsWith('/') ? action.slice(1) : action;
      url.pathname = `${url.pathname}/${cleanPath}`;
      return url.toString();
    },
    // @TODO move to NPM package
    getCpUrl(action: string) {
      const url = new URL(craftData.cpUrl);
      const cleanPath = action.startsWith('/') ? action.slice(1) : action;
      url.pathname = `${url.pathname}/${cleanPath}`;
      return url.toString();
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
