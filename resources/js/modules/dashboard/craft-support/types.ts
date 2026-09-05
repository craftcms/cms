export type SupportData = {
  resources: Array<{url: string; label: string}>;
  issueTitlePrefix: string;
  issueParams: Record<string, string>;
  showBackupOption: boolean;
  canContactSupport: boolean;
  email: string;
};
