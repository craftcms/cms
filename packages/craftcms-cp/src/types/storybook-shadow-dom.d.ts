import type {within} from 'shadow-dom-testing-library';

type ShadowQueries = ReturnType<typeof within>;

declare module 'storybook/internal/csf' {
  interface Canvas extends ShadowQueries {}
}
