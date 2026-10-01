export type QueryValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | QueryValue[]
  | QueryParams;

export interface QueryParams {
  [key: string]: QueryValue;
}
