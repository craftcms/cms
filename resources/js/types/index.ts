export interface Widget {
  id: number;
  type: string;
  colspan: number;
  title: string | null;
  subtitle: string | null;
  name: string | null;
  bodyHtml: string;
  settingsHtml: string;
  settingsJs: string;
  settings: {
    [key: string]: any;
  };
}

export interface WidgetType {
  iconSvg: string;
  name: string;
  maxColspan: null | number;
  settingsHtml: string;
  settingsJs: string;
  selectable: boolean;
}

export type CompleteWidget = Widget & {
  view?: 'settings' | 'default';
  mode?: 'edit' | 'view';
  new?: boolean;
  settingsNamespace?: string;
};
