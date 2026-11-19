/** Whether updates are available, as far as Craft knows. */
export interface AboutCraftUpdates {
  /** Whether the updates were fetched lately. If not, they're checked. */
  cached: boolean;
  total: number;
  /** When the updates were last fetched, if they're cached. */
  dateFetched?: string | null;
}

/** What the “About Craft CMS” dashboard widget and command palette view show. */
export interface AboutCraftInfo {
  version: string;
  edition: string;
  /** The trials that are installed, if any, as a message and a link to buy them. */
  trial: {message: string; cartUrl: string} | null;
  /** Whether updates are available, for users who can update Craft. */
  updates: AboutCraftUpdates | null;
}
