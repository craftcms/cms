export interface VolumeResource {
  id: number;
  name: string;
  handle: string;
  titleTranslationMethod: {
    name: string;
    value: string;
  };
  titleTranslationKeyFormat: null | string;
  altTranslationMethod: {
    name: string;
    value: string;
  };
  altTranslationKeyFormat: null | string;
  sortOrder: number;
  fieldLayoutId: number;
  uid: string;
  fsHandle: string;
  transformFsHandle: null | string;
  subpath: string;
  transformSubpath: string;
  idAttribute: string | null;
  fieldLayout?: Record<any, any>;
}
