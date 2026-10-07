import type {UiPayload, UiValues} from '@/modules/ui/types';

export type WidgetType = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetTypeData,
  'settingsForm'
> & {
  settingsForm: UiPayload | null;
};

export type DashboardWidget = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetData,
  'settingsForm' | 'settings'
> & {
  settingsForm: UiPayload | null;
  settings: UiValues;
};
