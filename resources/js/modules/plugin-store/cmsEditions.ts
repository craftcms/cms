import {t} from '@craftcms/ui';
import type {CmsEditionHandle, PluginStoreContext} from './types';

export interface CmsEditionFeature {
  name: string;
  description: string;
}

/** What each edition adds over the one before it. */
export const cmsEditionFeatures: Partial<
  Record<CmsEditionHandle, CmsEditionFeature[]>
> = {
  solo: [
    {
      name: 'One user account',
      description: 'The Solo edition is limited to a single admin account.',
    },
    {
      name: 'Flexible content modeling',
      description:
        'Define custom content types, fields, and relations needed to perfectly contain your unique content requirements.',
    },
    {
      name: 'Multi-site + localization',
      description:
        'Serve multiple related/localized sites from a single Craft installation.',
    },
    {
      name: 'Advanced previewing',
      description:
        'Preview your content from multiple targets, including single-page applications.',
    },
    {
      name: 'Twig + GraphQL',
      description:
        'Define custom front-end templates, or use Craft headlessly with the auto-generated GraphQL API.',
    },
  ],
  team: [
    {
      name: 'Up to five user accounts',
      description:
        'Create up to five user accounts (including admin accounts).',
    },
    {
      name: 'One user group',
      description:
        'All accounts belong to a “Team” user group with customizable permissions for non-admins.',
    },
    {
      name: 'Developer support',
      description:
        'Get developer-to-developer support right from the Craft core development team.',
    },
  ],
  pro: [
    {
      name: 'Unlimited user accounts',
      description:
        'Create unlimited user accounts with per-user permissions and user group assignments.',
    },
    {
      name: 'Unlimited user groups',
      description: 'Create multiple user groups with custom permissions.',
    },
    {
      name: 'Branded control panel',
      description: 'Personalize the control panel for your brand.',
    },
    {
      name: 'Branded communication',
      description:
        'Customize system email messages and provide a custom email template.',
    },
  ],
};

export function cmsEditionDescription(handle: CmsEditionHandle): string | null {
  switch (handle) {
    case 'solo':
      return t('For personal sites built for yourself or a friend.');
    case 'team':
      return t('For marketing sites managed by small teams.');
    case 'pro':
      return t('For everything else.');
    default:
      return null;
  }
}

export interface CmsEditionActions {
  status: 'installed' | 'installed-trial' | 'licensed' | null;
  /** `in-cart` once it's been added, so it isn't bought twice. */
  purchase: 'buy' | 'in-cart' | null;
  /** Switching to it: a trial of a higher edition, or back to the licensed one. */
  switchTo: 'try' | 'reactivate' | null;
}

/** Decides what the upgrade screen offers for one Craft edition. */
export function resolveCmsEditionActions(
  handle: CmsEditionHandle,
  price: number,
  context: Pick<
    PluginStoreContext,
    'edition' | 'licensedEdition' | 'editions' | 'canTestEditions'
  >,
  inCart: boolean
): CmsEditionActions {
  const index = context.editions.indexOf(handle);
  const current = context.editions.indexOf(context.edition);
  const licensed = context.licensedEdition
    ? context.editions.indexOf(context.licensedEdition)
    : -1;

  let status: CmsEditionActions['status'] = null;

  if (index === current) {
    status = licensed >= index ? 'installed' : 'installed-trial';
  } else if (index === licensed) {
    status = 'licensed';
  }

  const upgrade = price > 0 && index > licensed;

  let switchTo: CmsEditionActions['switchTo'] = null;

  if (index !== current) {
    if (index === licensed) {
      switchTo = 'reactivate';
    } else if (context.canTestEditions && upgrade) {
      switchTo = 'try';
    }
  }

  return {
    status,
    purchase: upgrade ? (inCart ? 'in-cart' : 'buy') : null,
    switchTo,
  };
}
