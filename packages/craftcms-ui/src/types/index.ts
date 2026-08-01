export interface DateObject {
  date: string;
  timezone_type: string;
  timezone: string;
}

export const AsyncStates = {
  Idle: 'idle',
  Loading: 'loading',
  Success: 'success',
  Error: 'error',
} as const;

export type AsyncState = (typeof AsyncStates)[keyof typeof AsyncStates];

export type FormElementJsonValue =
  | boolean
  | number
  | string
  | null
  | FormElementJsonValue[]
  | {[key: string]: FormElementJsonValue};

export type FormElementAttributes = Record<string, FormElementJsonValue>;

export interface FormElementBinding<TValue = unknown> {
  name: string;
  value: TValue;
  readOnly: boolean;
}

export interface FormElementRendererProps<TConfig, TValue = unknown> {
  config: TConfig;
  attributes: FormElementAttributes;
  binding?: FormElementBinding<TValue>;
}

export type {
  ActionMenuActions,
  ActionMenuItem,
  ActionMenuItemHr,
  ActionMenuItemDisplay,
  ActionMenuItemButton,
  ActionMenuItemLink,
  ActionMenuItemsProvider,
  ActionShortcut,
} from '../components/action-menu/action-menu.types.js';
