import {t, type ElementInfo} from '@craftcms/ui';
import {
  createElementSelectorModal,
  type ElementSelectorModalHandle,
} from '@/modules/element-selector-modal/create-element-selector-modal';
import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {escapeMarkdownLabel} from './utilities';

const ASSET_ELEMENT_TYPE = 'CraftCms\\Cms\\Asset\\Elements\\Asset';
const ASSET_REF_HANDLE = 'asset';

/**
 * `kind` and `alt` come from `ModalIndexViewModel::typeSpecificRowData()`.
 *
 * They used to be read off the selected chip's data attributes, which the Vue
 * index has none of — so every insert silently fell back to a link, even for
 * images.
 */
type AssetInfo = ElementInfo & {
  kind?: string | null;
  alt?: string | null;
};

export type AssetController = {
  open: () => void | Promise<void>;
};

export function createAssetController(
  editor: OverTypeInstance,
  assetCriteria: Record<string, unknown>,
  assetSources: string[],
  preview: PreviewController
): AssetController {
  let assetSelectorModal: ElementSelectorModalHandle | null = null;

  async function open(): Promise<void> {
    if (!assetSelectorModal) {
      assetSelectorModal = await createElementSelectorModal(
        ASSET_ELEMENT_TYPE,
        {
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
        }
      );

      return;
    }

    void assetSelectorModal.show();
  }

  function insert(asset: AssetInfo): void {
    const ref = `{${ASSET_REF_HANDLE}:${asset.id}@${asset.siteId}:url}`;
    const label = escapeMarkdownLabel(String(asset.alt || asset.label || ''));
    const markdown =
      asset.kind === 'image'
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
