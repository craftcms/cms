import type {User} from '@/types/user';

export interface LoginResponse {
  returnUrl: string;
  modelName: string;
  modelClass: string;
  user: User;
  modelId: number;
}
