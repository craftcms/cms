export type MixedInputPart = string | [string, string];

export interface RouteData extends CraftCms.Cms.Route.Data.Route {
  uriParts: Array<MixedInputPart>;
  uriDisplayHtml: string;
}

export interface RouteIndexData extends Omit<RouteData, 'siteUid' | 'uid'> {
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
