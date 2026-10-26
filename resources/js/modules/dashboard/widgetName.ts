import type {DashboardWidget} from './types';

/** The name a widget goes by: its own title, or its type's name. */
export function widgetName(widget: DashboardWidget): string {
  return widget.title || widget.name;
}
