export type LicenseIssueKey =
  | 'wrong_edition'
  | 'no_trials'
  | 'mismatched'
  | 'astray'
  | 'required'
  | 'invalid';


export interface PluginInfo {
  handle: string;
  developer: string;
  developerUrl: string;
  description: string;
  documentationUrl: string;
  class: string;
  basePath: string;
  aliases: Record<string, string> | null;
  name: string;
  version: string;
  developerEmail: string;
  packageName: string;
  isInstalled: boolean;
  isForceDisabled: boolean;
  isEnabled: boolean;
  private: boolean;
  moduleId: string;
  edition: string;
  hasMultipleEditions: boolean;
  hasCpSettings: boolean;
  hasReadOnlyCpSettings: boolean;
  licenseKey: any;
  licenseId: any;
  licensedEdition: any;
  licenseKeyStatus: string;
  licenseIssues: Array<LicenseIssueKey>;
  isTrial: boolean;
  upgradeAvailable: boolean;
  pluginStoreUrl: string | null;
  links: Array<Record<string, string>>;
  buyUrl: string;
  iconSvg?: string | null;
}
