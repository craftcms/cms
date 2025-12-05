import type {SelectOption} from '@/types/index';

export const Edition = {
  Solo: 0,
  Team: 1,
  Pro: 2,
  Enterprise: 3,
} as const;

export interface SystemData {
  name: string | null;
  live: boolean;
  edition: (typeof Edition)[keyof typeof Edition];
  retryDuration: number;
  timeZone: string;
}

export interface TimezoneOption extends SelectOption {
  data?: {
    hint?: string;
  };
}
