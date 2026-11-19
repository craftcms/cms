import {fetchUiPayload} from '@/modules/ui/fetchUiPayload';
import ElementSourcesController from '@actions/Elements/ElementSourcesController';
import type {UiPayload} from '@/modules/ui/types';
import type {SourceType} from './types';

/** Builds a source's settings UI, seeded from `settings` where given. */
export async function fetchSourceUi(
  elementType: string,
  sourceKey: string,
  type: SourceType,
  settings?: UiPayload['values'],
  scope: string[] = [],
  params: Record<string, unknown> = {}
): Promise<UiPayload> {
  const {ui} = await fetchUiPayload(
    ElementSourcesController.ui().url,
    {elementType, sourceKey, type, settings, scope, ...params},
    'The source did not return a UI payload.'
  );

  return ui;
}
