export interface AuthMethodAction {
  label: string;
  icon: string | null;
  action: string | null;
  requireElevatedSession: boolean;
  download: boolean;
}

export interface AuthMethod {
  /** Fully-qualified PHP class name, used as the `method` param for actions. */
  type: string;
  handle: string;
  name: string;
  description: string;
  isActive: boolean;
  actions: AuthMethodAction[];
}
