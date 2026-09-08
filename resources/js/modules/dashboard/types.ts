import type {FormPayload, FormValues} from '@/modules/forms/types';

export type WidgetType = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetTypeData,
  'settingsForm'
> & {
  settingsForm: FormPayload | null;
};

export type DashboardWidget = Omit<
  CraftCms.Cms.Dashboard.Data.WidgetData,
  'settingsForm' | 'settings'
> & {
  settingsForm: FormPayload | null;
  settings: FormValues;
};
