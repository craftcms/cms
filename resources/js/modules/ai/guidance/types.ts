/** Guidance for the AI assistant, for every site (`siteId` null) or one site. */
export interface AiGuidanceScope {
  siteId: number | null;
  heading: string;
  body: string;
  /** Whether the user can edit the site, and so its guidance. */
  editable: boolean;
}
