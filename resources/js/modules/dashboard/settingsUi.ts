import {actionClient} from '@craftcms/ui';
import {refreshSettings} from '@actions/Dashboard/WidgetsController';
import type {UiPayload, UiValues} from '@/modules/ui/types';

/**
 * Loads a widget's settings UI, which the dashboard leaves out of its page
 * load, as resolving it can be costly: a saved widget's, by its ID, or a new
 * widget's, by its type.
 */
export async function loadSettingsUi(
  widget: {widgetId: number} | {type: string; settings?: UiValues},
  namespace: string
): Promise<UiPayload | null> {
  const {data} = await actionClient.post(refreshSettings.url(), {
    ...widget,
    namespace,
  });

  return data.ui ?? null;
}
