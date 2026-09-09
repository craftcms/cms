import {computed, type Ref} from 'vue';
import {toRefs} from '@vueuse/core';
import {usePage} from '@inertiajs/vue3';

export interface CpUser {
  username: string | null;
  email: string | null;
  id: number | null;
  thumbHtml: string | null;
  name: string | null;
}

export interface CraftData {
  csrfTokenValue: string | null;
  csrfTokenName: string | null;
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
  const {actionUrl, cpUrl, baseApiUrl} = useCraftData();

  return {
    // @TODO move to NPM package
    getActionUrl(action: string) {
      return getUrl(actionUrl.value, action);
    },
    // @TODO move to NPM package
    getCpUrl(action: string) {
      return `${cpUrl.value}${action}`;
    },
    getApiUrl(path: string) {
      return getUrl(baseApiUrl.value, path);
    },
  };
}

export default function useCraftData(): {
  [Key in keyof CraftData]-?: Readonly<Ref<CraftData[Key]>>;
} {
  const page = usePage<{
    craft: CraftData;
  }>();

  return toRefs(computed(() => page.props.craft));
}
