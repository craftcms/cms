import type {UiPayload, UiValues} from '@/modules/ui/types';

export type WidgetType = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetTypeData,
  'settingsUi'
> & {
  settingsUi: UiPayload | null;
};

export type DashboardWidget = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetData,
  'settingsUi' | 'settings'
> & {
  settingsUi: UiPayload | null;
  settings: UiValues;
};
