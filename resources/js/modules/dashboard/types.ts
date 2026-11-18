import type {UiPayload, UiValues} from '@/modules/ui/types';

export type WidgetType = CraftCms.Cms.Dashboard.Data.WidgetTypeData;

export type DashboardWidget = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetData,
  'settings'
> & {
  settings: UiValues;
  /**
   * The settings' UI, once it's loaded. The dashboard loads it when the
   * settings open (see `hasSettings`).
   */
  settingsUi?: UiPayload | null;
};

/** An item in an `EntryList`. */
export type EntryListItem = {
  id: number;
  /** The entry's edit URL, which its title links to and its thumbnail opens in a slideout. */
  url: string | null;
  title: string;
  /** The entry's thumbnail markup, such as its `getThumbHtml()`. */
  thumbHtml?: string | null;
};

/** A person in an `AvatarStack`. */
export type AvatarStackPerson = {
  id?: number | string | null;
  /** Shown in the avatar's tooltip, and read by screen readers. */
  name: string;
  /** The avatar's thumbnail markup, such as a user's `getThumbHtml()`. */
  thumbHtml?: string | null;
};
