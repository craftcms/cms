/** A site an element index can be switched to, as the payload sends it. */
export interface IndexSite {
  id: number;
  handle: string;
  name: string;
  /** The site group's name, or null when it can't be resolved. */
  group: string | null;
}
