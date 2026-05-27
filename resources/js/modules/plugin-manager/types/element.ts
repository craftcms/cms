/**
 * Common shape for Craft elements as serialized over the wire.
 * Mirrors the subset of ElementInterface that shows up on both
 * CraftnetPluginData and Icon.
 */
export interface CraftElementInterface {
  id: number;
  tempId: string | null;
  draftId: number | null;
  revisionId: number | null;
  isProvisionalDraft: boolean;
  hasProvisionalChanges: boolean;
  uid: string;
  siteSettingsId: number;
  fieldLayoutId: number | null;
  archived: boolean;
  siteId: number;
  title: string | null;
  slug: string | null;
  uri: string | null;
  dateCreated: string;
  dateUpdated: string;
  dateLastMerged: string | null;
  dateDeleted: string | null;
  deletedWithOwner: boolean | null;
  trashed: boolean;
  forceSave: boolean;
  canonicalId: number;
  cpEditUrl: string;
  isDraft: boolean;
  isRevision: boolean;
  isUnpublishedDraft: boolean;
  ref: string | null;
  status: string;
  structureId: number | null;
  url: string;
  eagerLoadInfo: unknown;
}

export interface FocalPoint {
  x: number;
  y: number;
}

export interface AssetElement extends CraftElementInterface {
  enabled: boolean;
  fieldLayoutId: number;
  title: string;
  isFolder: boolean;
  sourcePath: string | null;
  folderId: number;
  uploaderId: number;
  folderPath: string | null;
  kind: string;
  alt: string | null;
  size: number;
  keptFile: boolean | null;
  dateModified: string;
  newLocation: string | null;
  locationError: string | null;
  newFilename: string | null;
  newFolderId: number | null;
  tempFilePath: string | null;
  suggestedFilename: string | null;
  conflictingFilename: string | null;
  deletedWithVolume: boolean;
  extension: string;
  filename: string;
  focalPoint: FocalPoint;
  hasFocalPoint: boolean;
  height: number;
  width: number;
  mimeType: string;
  path: string;
  volumeId: number;
}
