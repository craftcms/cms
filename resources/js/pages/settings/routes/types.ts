import type {MixedInputPart} from '@/components/form/MixedInput.vue';

export interface RouteData {
  uid: string | null;
  siteUid: string | null;
  uriParts: Array<MixedInputPart>;
  uriDisplayHtml: string;
  template: string;
  sortOrder: number | null;
}

export interface RouteIndexData extends Omit<RouteData, 'siteUid' | 'uid'> {
  uid: string;
  siteName: string;
}

export interface RouteFormData {
  uriParts: Array<MixedInputPart>;
  template: string;
  siteUid: string;
  redirect?: string;
}

export interface RouteActionMenuItem {
  type?: 'button' | 'link' | 'hr' | 'group';
  label?: string;
  url?: string;
  icon?: string;
  destructive?: boolean;
  attributes?: {
    data?: Record<string, unknown>;
  };
  items?: Array<RouteActionMenuItem>;
}
