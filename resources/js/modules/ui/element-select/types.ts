import type {ElementActionMenuItem} from '@/modules/elements/composables/useElementActionMenu';
import type {UiProperties} from '../types';

export type ElementPresentation = {
  id: number;
  label: string;
  siteId?: number | string | null;
  /**
   * What the element itself permits, decided server-side. Absent for elements
   * learned from the selector modal, which only reports the common six —
   * those chips get the field's own actions until the next server render
   * fills the rest in.
   */
  url?: string | null;
  /** The element's control panel edit screen, if it has one. */
  cpEditUrl?: string | null;
  canEdit?: boolean;
  canCopy?: boolean;
  draftId?: number | null;
  revisionId?: number | null;
  /**
   * The chip's status dot, already resolved to a fill and a label — which
   * status definitions exist, and what they're called, is an element-type
   * concern. `null` for types that don't show one.
   */
  status?: {fill: string; label: string; draft: boolean} | null;
  /**
   * The element's own action menu, as the server describes it — including the
   * extras an element type adds (an asset's Preview file, Download, Show in
   * folder, Open in Image Editor).
   */
  actions?: ElementActionMenuItem[];
  /**
   * View-mode extras, rendered server-side for the active mode only — the
   * control asks for card parts in card modes and a thumbnail in thumbs mode,
   * so a list field pays for neither.
   */
  cardAttributes?: Record<string, string | number | boolean | null | undefined>;
  cardHeaderHtml?: string;
  cardContentHtml?: string;
  cardFooterHtml?: string;
  thumbHtml?: string;
};

/** Mirrors `ElementSelect::viewModes()`, itself a mirror of the field setting. */
export type ElementSelectViewMode =
  | 'list'
  | 'list-inline'
  | 'thumbs'
  | 'cards'
  | 'cards-grid';

type ElementSelectElement =
  | 'craft-element-select-input'
  | 'craft-entry-select-input'
  | 'craft-asset-select-input';

export type ElementSelectProps = {
  elementType: string;
  customElement: ElementSelectElement;
  elements: ElementPresentation[];
  context: string;
  sources: string[] | null;
  criteria: UiProperties;
  selectionCondition?: UiProperties;
  selectionLabel: string;
  limit: number | null;
  single?: boolean;
  showSiteMenu: boolean;
  viewMode: ElementSelectViewMode;
  /** Lower-cased, for "Edit entry" / "Copy entry". */
  elementDisplayName: string;
  /** `AssetSelect` only; absent for every other element type. */
  canUpload?: boolean;
  uploadFolderId?: number | null;
  showFolders?: boolean;
};
