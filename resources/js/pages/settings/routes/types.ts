export type MixedInputPart = string | [string, string];

export interface RouteData extends CraftCms.Cms.Route.Data.Route {
  uriParts: Array<MixedInputPart>;
  uriDisplayHtml: string;
}

export interface RouteIndexData extends Omit<RouteData, 'siteUid'> {
  siteName: string;
}

export interface RouteFormData {
  uriParts: Array<MixedInputPart>;
  template: string;
  siteUid: string;
  redirect?: string;
}
