import type {User} from '@/types/user';

export interface LoginResponse {
  returnUrl?: string;
  elevatedSessionExpiresAt?: number | false;
  requiresTwoFactor?: boolean;
  authMethod?: string;
  authMethodHandle?: string;
  encryptedReturnUrl?: string;
  authForm?: string;
  otherMethods?: Array<{name: string; handle: string}>;
  headHtml?: string;
  bodyHtml?: string;
  modelName?: string;
  modelClass?: string;
  user?: User;
  modelId?: number;
}
