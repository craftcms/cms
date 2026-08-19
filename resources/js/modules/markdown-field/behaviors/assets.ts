import {t} from '@craftcms/ui';
import {createElementSelectorModal} from '@/modules/element-selector-modal/registry';
import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {escapeMarkdownLabel} from './utilities';

const ASSET_ELEMENT_TYPE = 'CraftCms\\Cms\\Asset\\Elements\\Asset';
const ASSET_REF_HANDLE = 'asset';

type AssetInfo = {
  id: number | string;
  label?: string;
  siteId?: number | string;
  $element?: {
    data?: (key: string) => unknown;
  };
};

type AssetSelectorModal = {
  show: () => void;
};

export type AssetController = {
  open: () => void;
};

export function createAssetController(
  editor: OverTypeInstance,
  assetCriteria: Record<string, unknown>,
  assetSources: string[],
  preview: PreviewController
): AssetController {
  let assetSelectorModal: AssetSelectorModal | null = null;

  function open(): void {
    if (!assetSelectorModal) {
      assetSelectorModal = createElementSelectorModal(ASSET_ELEMENT_TYPE, {
        closeOtherModals: false,
        criteria: assetCriteria,
        hideOnSelect: true,
        modalTitle: t('Choose an asset'),
        multiSelect: false,
        onSelect: (assets: AssetInfo[]) => {
          const [asset] = assets;

          if (asset) {
            insert(asset);
          }
        },
        sources: assetSources,
      });

      return;
    }

    assetSelectorModal.show();
  }

  function insert(asset: AssetInfo): void {
    const siteId = asset.siteId || asset.$element?.data?.('site-id');
    const ref = `{${ASSET_REF_HANDLE}:${asset.id}@${siteId}:url}`;
    const label = escapeMarkdownLabel(
      String(asset.$element?.data?.('alt') || asset.label || '')
    );
    const markdown =
      asset.$element?.data?.('kind') === 'image'
        ? `![${label}](${ref})`
        : `[${label || ref}](${ref})`;

    editor.insertAtCursor(markdown);
    editor.focus();

    if (preview.isActive()) {
      void preview.render(editor.getValue());
    }
  }

  return {open};
}
