import {usePage} from '@inertiajs/vue3';

export interface CpUser {
  username: string | null;
  email: string | null;
  id: number | null;
  thumbHtml: string | null;
  name: string | null;
}

export interface CraftData {
  csrfTokenValue?: string | null;
  csrfTokenName?: string | null;
  system: {
    name: string;
    icon: string | null;
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
    id: number;
    name: string;
    handle: string;
    url: string;
  } | null;
  readOnly: boolean;
  maintenanceMode: boolean;
  allowAdminChanges: boolean;
  currentUser: CpUser | null;
  general: {
    cpTrigger: string | null;
    actionTrigger: string | null;
    csrfTokenName: string | null;
    cpLogoUrl: string | null;
    useEmailAsUsername: boolean;
    rememberedUserSessionDuration: number;
    defaultCpLocale: string;
    notifications: CraftCms.Cms.Cp.Data.NotificationData[];
  };
  nav: CraftCms.Cms.Cp.Data.NavItem[];
  actionUrl: string;
  cpUrl: string;
  baseApiUrl: string;
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
      //return `${craftData.actionUrl}${action}`;
      return getUrl(craftData.actionUrl, action);
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

  return page.props.craft;
}
