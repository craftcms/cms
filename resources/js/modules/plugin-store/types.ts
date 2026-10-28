export type CmsEditionHandle = 'solo' | 'team' | 'pro' | 'enterprise';

/** What the server tells the store about this install. */
export interface PluginStoreContext {
  edition: CmsEditionHandle;
  licensedEdition: CmsEditionHandle | null;
  editions: CmsEditionHandle[];
  /** Whether this install may run editions it isn't licensed for. */
  canTestEditions: boolean;
  phpVersion: string;
  composerPhpVersion: string | null;
  allowUpdates: boolean;
  cmsLicenseKey: string | null;
  craftIdEndpoint: string;
}

export interface PluginEditionFeature {
  name: string;
  description: string | null;
}

export interface PluginEdition {
  id: number;
  name: string;
  handle: string;
  /** Null for free editions. */
  price: string | null;
  renewalPrice: string | null;
  features: PluginEditionFeature[] | null;
}

export interface PluginSummary {
  id: number;
  handle: string;
  name: string;
  packageName: string;
  shortDescription: string | null;
  iconUrl: string | null;
  abandoned: boolean;
  editions: PluginEdition[];
  ratingAvg: number | null;
  totalReviews: number | null;
}

export interface PluginReviewStats {
  totalReviews: number;
  ratingAvg: number;
  totalReviewsByRating: Record<number, number>;
}

export interface PluginIssueStats {
  period: number;
  openIssues: number;
  closedIssues: number;
  openPulls: number;
  mergedPulls: number;
}

export interface PluginInstallHistoryPoint {
  date: string;
  activeInstalls: number;
}

export interface Plugin extends PluginSummary {
  developerId: number;
  developerName: string;
  developerPartner: boolean;
  longDescription: string | null;
  documentationUrl: string | null;
  repository: string;
  version: string;
  latestCompatibleVersion: string | null;
  phpVersionCompatible: boolean;
  incompatiblePhpVersion: 'php' | 'composer' | null;
  phpConstraint: string | null;
  minCmsEdition: CmsEditionHandle | null;
  compatibility: string;
  cloudTested: boolean;
  supportsGql: boolean;
  license: string;
  lastUpdate: string;
  totalReleases: number | null;
  activeInstalls: number;
  installHistory: PluginInstallHistoryPoint[] | null;
  issueStats: PluginIssueStats[] | null;
  reviewStats: PluginReviewStats | null;
  categoryIds: Array<number | string>;
  thumbnailUrls: string[];
  screenshotUrls: string[];
  replacementHandle: string | null;
  replacementName: string | null;
}

export interface PluginCategory {
  id: number;
  title: string;
  iconUrl: string | null;
}

export type SortDirection = 'asc' | 'desc';

/** Attribute → its default direction. */
export type SortOptions = Record<string, SortDirection>;

/** How long a license's updates run for, and the date that lands on. */
export type ExpiryDateOption = [value: string, date: string];

export interface CoreData {
  categories: PluginCategory[];
  sortOptions: SortOptions;
  expiryDateOptions: ExpiryDateOption[];
}

export interface FeaturedSection {
  id: number;
  slug: string;
  title: string;
  plugins: PluginSummary[];
}

export interface PartnerInfo {
  isCraftVerified: boolean;
  isCommerceVerified: boolean;
  isEnterpriseVerified: boolean;
  profileUrl: string | null;
}

export interface Developer {
  developerName: string;
  location: string | null;
  photoUrl: string | null;
  developerUrl: string | null;
  partnerInfo: PartnerInfo | null;
}

export interface PluginIndexPage {
  plugins: PluginSummary[];
  currentPage: number;
  /** The number of pages, despite the name. */
  total: number;
}

export interface ChangelogRelease {
  version: string;
  date: string;
  critical: boolean;
  notes: string;
}

export interface PluginReviewComment {
  id: number;
  comment: string;
  dateCreated: string;
  dateUpdated: string;
}

export interface PluginReview {
  id: number;
  rating: number;
  comment: string;
  dateCreated: string;
  dateUpdated: string;
  author: {name: string; photo: string | null};
  comments: PluginReviewComment[];
}

export interface PluginReviewsPage {
  data: PluginReview[];
  current_page: number;
  last_page: number;
  per_page: number;
}

export type LicenseKeyStatus =
  | 'valid'
  | 'trial'
  | 'invalid'
  | 'mismatched'
  | 'astray'
  | 'unknown';

/** How a plugin stands on this install, from `app/get-plugin-license-info`. */
export interface PluginLicenseInfo {
  isInstalled: boolean;
  isEnabled: boolean;
  isTrial: boolean;
  edition: string | null;
  licensedEdition: string | null;
  licenseKey: string | null;
  licenseKeyStatus: LicenseKeyStatus;
  licenseIssues: string[];
}

export interface CmsEdition {
  handle: CmsEditionHandle;
  name: string;
  price: string | null;
  renewalPrice: string | null;
}

export interface CartAdjustment {
  name: string;
  amount: number;
  sourceSnapshot: {type: string; expiryDate?: string};
}

export interface CartLineItem {
  price: number;
  purchasable: {
    type: 'plugin-edition' | 'cms-edition';
    handle: string;
    name: string;
    renewalPrice: string;
    pluginId?: number;
    plugin?: {id: number; handle: string};
  };
  options: {
    expiryDate: string;
    autoRenew: boolean;
    /** `new:…` until a license exists. */
    licenseKey: string;
  };
  adjustments: CartAdjustment[];
}

export interface Cart {
  number: string;
  email: string;
  totalPrice: number;
  totalQty: number;
  lineItems: CartLineItem[];
}

/** What the API takes to describe a cart's contents. */
export interface CartItemData {
  type: 'plugin-edition' | 'cms-edition';
  edition: string;
  plugin?: string;
  licenseKey?: string;
  cmsLicenseKey?: string | null;
  expiryDate: string;
  autoRenew: boolean;
}
