/** How a column's cells are parsed and formatted. */
export type ChartColumnType =
  | 'date'
  | 'datetime'
  | 'number'
  | 'percent'
  | 'currency'
  | 'time';

export interface ChartColumn {
  type: ChartColumnType;
  label: string;
}

/**
 * A run chart's data, as `CraftCms\Cms\Dashboard\Chart::get()` returns it:
 * a date column and a value column, with one row per interval.
 */
export interface ChartData {
  columns: ChartColumn[];
  rows: Array<Array<string | number>>;
}

/** The interval between rows, which picks the date labels' format. */
export type ChartScale = 'hour' | 'day' | 'month' | 'year';
