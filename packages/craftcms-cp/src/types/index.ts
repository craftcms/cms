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

export * from './queue.js';
