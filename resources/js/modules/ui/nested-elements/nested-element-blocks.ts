import type {ActionItems} from '@/common/types';
import type {
  NestedElementValue,
  NestedUiPayload,
  UiControlPayload,
  UiValues,
} from '../types';

/** What a block is called, what it looks like, and what can be done to it. */
export type BlockPresentation = {
  label?: string;
  icon?: {name: string; family: string} | null;
  color?: string | null;
  actions?: ActionItems;
  /** The block's identity, as `data-*` attributes. See `Matrix::blockData()`. */
  data?: Record<string, number | string>;
  /** Whether the last save left validation errors on the block. */
  error?: boolean;
};

export type EntryType = {
  value: string;
  label: string;
  icon?: {name: string; family: string} | null;
  color?: string | null;
  group?: string | null;
};

export type NestedElementBlocksProps = {
  entryTypes?: EntryType[];
  createEntryTypes?: string[] | null;
  addLabel: string;
  minEntries?: number | null;
  maxEntries?: number | null;
  /** Per-block presentation, keyed by identity. Server-built; never posted. */
  blocks?: Record<string, BlockPresentation>;
  /** The blocks' element class, for the CP's element clipboard. */
  elementType?: string | null;
  /** Current site name when entries propagate to multiple sites. */
  siteName?: string | null;
  /** The owner's site, which collapsed blocks are remembered for. */
  siteId?: number | null;
  /**
   * What `matrix/create-entry` needs to mint a block, or absent when the server
   * can't — an unsaved owner, or a nested element field that isn't Matrix-backed.
   */
  create?: {
    fieldId: number;
    ownerId: number;
    ownerElementType: string;
    ownerHasDrafts?: boolean;
    siteId: number;
    entryTypeIds: Record<string, number>;
  } | null;
};

/** A block the server minted or rendered, as `matrix/*` hands it back. */
export type CreatedBlock = {
  uid: string;
  type: string;
  ui: NestedUiPayload;
  values: UiValues;
  block: BlockPresentation;
};

/** Whether blocks are enabled, read off the field's current value. */
export type BlockStatus = {
  isDisabled: (uid: string) => boolean;
  isGloballyDisabled: (uid: string) => boolean;
  isDisabledForSite: (uid: string) => boolean;
};

/**
 * Writes the field's whole value back, the only way a block change lands. A
 * new block also hands back the definition that now includes its ui.
 */
export type UpdateBlocksValue = (
  next: NestedElementValue,
  definition?: UiControlPayload<NestedElementBlocksProps>
) => void;
