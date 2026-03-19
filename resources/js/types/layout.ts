/**
 * Types for the AppLayout component and related layout functionality
 */

export interface Tab {
  id: string;
  label: string;
  url?: string;
  class?: string;
  visible?: boolean;
  hasError?: boolean;
}

export interface FormAction {
  label: string;
  action?: string;
  redirect?: string;
  confirm?: string;
  params?: Record<string, any>;
  retainScroll?: boolean;
  eventData?: any;
  shortcut?: boolean;
  shift?: boolean;
  destructive?: boolean;
}

export interface Crumb {
  url?: string | null;
  label: string;
}

export interface Alert {
  content: string;
  showIcon?: boolean;
}

export type AlertItem = string | Alert;

export interface TrialInfo {
  message: string;
  cartUrl: string;
}

export interface AppLayoutProps {
  title?: string;
  debug?: any;
  fullWidth?: boolean;
  fullPageForm?: boolean;
  showHeader?: boolean;
  saveShortcut?: boolean;
  saveShortcutRedirect?: string | false;
  retainScrollOnSaveShortcut?: boolean;
  formActions?: FormAction[];
  submitButtonLabel?: string;
  mainAttributes?: Record<string, any>;
  mainFormAttributes?: Record<string, any>;
}

export interface AppLayoutPageProps {
  flash: {
    success: string | null;
    error: string | null;
  };
  crumbs?: Crumb[] | null;
  alerts?: AlertItem[];
  tabs?: Tab[];
  trialInfo?: TrialInfo | null;
  canUpgradeEdition?: boolean;
  isTrial?: boolean;
}

export interface SidebarState {
  mode: 'docked' | 'floating';
  visibility: 'hidden' | 'visible';
}

export interface LayoutState {
  sidebar: SidebarState;
  detailsVisible: boolean;
  contentSidebarVisible: boolean;
}
