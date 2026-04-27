import type {AssetElement, CraftElementInterface} from '@/types/element';

export type LicenseIssueKey =
  | 'wrong_edition'
  | 'no_trials'
  | 'mismatched'
  | 'astray'
  | 'required'
  | 'invalid';

/**
 * Fields shared by CMS and plugin licenses.
 */
export interface BaseLicense {
  id: number;
  editionId: number;
  ownerId: number | null;
  cloudProjectId: number | null;
  expirable: boolean;
  expired: boolean;
  autoRenew: boolean;
  reminded: boolean;
  renewalPrice: string;
  email: string | null;
  key: string;
  notes: string | null;
  lastVersion: string;
  lastAllowedVersion: string;
  lastActivityOn: string;
  lastStatus: string;
  lastRenewedOn: string | null;
  expiresOn: string | null;
  dateCreated: string;
  dateUpdated: string;
  uid: string;
}

/**
 * Install + license state shared by PluginInfo and the license response.
 */
export interface PluginLicenseState {
  isInstalled: boolean;
  isEnabled: boolean;
  version: string;
  hasMultipleEditions: boolean;
  edition: string | null;
  licenseKey: string | null;
  licensedEdition: string | null;
  licenseKeyStatus: string;
  licenseIssues: LicenseIssueKey[];
  isTrial: boolean;
  upgradeAvailable: boolean;

  // data included when the plugin is missing
  isComposerInstalled?: boolean;
  name?: string;
  description?: string;
  iconUrl?: string;
  documentationUrl?: string;
  packageName?: string;
  latestVersion?: string;
  expired?: boolean;
  renewalUrl?: string;
  renewalText?: string;
}

// ---------------------------------------------------------------------------
// Plugin info / license responses
// ---------------------------------------------------------------------------

export type PluginLicenseResponseData = PluginLicenseState;

export interface PluginInfo extends PluginLicenseState {
  handle: string;
  name: string;
  packageName: string;
  class: string;
  basePath: string;
  aliases: Record<string, string> | null;
  description: string;
  documentationUrl: string;
  developer: string;
  developerUrl: string;
  developerEmail: string;
  private: boolean;
  moduleId: string;
  isForceDisabled: boolean;
  hasCpSettings: boolean;
  hasReadOnlyCpSettings: boolean;
  licenseId: number | null;
  pluginStoreUrl: string | null;
  links: Record<string, string>[];
  buyUrl: string;
  iconSvg?: string | null;
}

// ---------------------------------------------------------------------------
// License records
// ---------------------------------------------------------------------------

export interface CmsLicenseData extends BaseLicense {
  editionHandle: string;
  domain: string | null;
  lastEdition: string;
  allowCustomDomain: boolean;
  pluginLicenses: PluginLicenseData[];
}

export interface PluginLicenseData extends BaseLicense {
  pluginId: number;
  cmsLicenseId: number;
  pluginHandle: string;
  edition: string | null;
  trial: boolean;
  cmsLicenseKey: string | null;
  cmsLicenseDomain: string | null;
  plugin: CraftnetPluginData;
}

// ---------------------------------------------------------------------------
// Craftnet plugin element + icon asset
// ---------------------------------------------------------------------------

export interface CraftnetPluginData extends CraftElementInterface {
  enabled: boolean;
  published: boolean;
  developerId: number;
  developerName: string;
  packageId: number;
  iconId: number;
  packageName: string;
  repository: string;
  name: string;
  handle: string;
  license: string;
  minCmsEdition: string;
  shortDescription: string;
  longDescription: string;
  documentationUrl: string;
  changelogPath: string;
  latestVersionId: number | null;
  latestVersion: string;
  latestVersionTime: string | null;
  compatibleCmsVersion: string | null;
  pendingApproval: boolean;
  keywords: string;
  dateApproved: string;
  totalPurchases: number | null;
  ratingAvg: number | null;
  ratingAvgBayesian: number | null;
  totalReviews: number | null;
  abandoned: boolean;
  note: string | null;
  replacementId: number | null;
  cloudTested: boolean;
  supportsGql: boolean;
  icon: AssetElement;
}
