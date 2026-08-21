import {hasAttr} from '@craftcms/garnish';
import {BaseElementSelectorModal} from './base-element-selector-modal';

declare const $: any;

const DEFAULTS = {
    disabledFolderIds: [] as number[],
    indexSettings: {} as Record<string, any>,
};

/**
 * VolumeFolderSelectorModal — a port of `Craft.VolumeFolderSelectorModal` onto
 * {@link BaseElementSelectorModal}. Forces folder-only browsing of asset volumes
 * and allows selecting the currently open folder even when no element is
 * highlighted.
 */
export class VolumeFolderSelectorModal extends BaseElementSelectorModal {
    static override defaults =
        DEFAULTS as typeof BaseElementSelectorModal.defaults & typeof DEFAULTS;

    constructor(settings?: any) {
        super('CraftCms\\Cms\\Asset\\Elements\\Asset');
        if (new.target === VolumeFolderSelectorModal) {
            this.init(settings);
        }
    }

    override init(settings?: any): void {
        const merged = Object.assign(
            {},
            VolumeFolderSelectorModal.defaults,
            settings,
            {
                // showSiteMenu is always false for folder selection
                showSiteMenu: false,
            }
        );
        merged.indexSettings = Object.assign({}, merged.indexSettings, {
            disabledFolderIds: merged.disabledFolderIds,
        });
        super.init('CraftCms\\Cms\\Asset\\Elements\\Asset', merged);
    }

    override getElementIndexParams(): Record<string, any> {
        return Object.assign({}, super.getElementIndexParams(), {
            foldersOnly: true,
        });
    }

    override shouldEnableSelectBtn(): boolean {
        if (super.shouldEnableSelectBtn()) return true;

        // Allow selecting the current folder when nothing is highlighted, unless
        // it is in the disabled list.
        const sourcePath = this.elementIndex?.sourcePath;
        if (!sourcePath?.length) return false;

        const last = sourcePath[sourcePath.length - 1];
        return (
            typeof last.folderId !== 'undefined' &&
            !this.settings.disabledFolderIds.includes(last.folderId)
        );
    }

    override selectElements(ev?: MouseEvent): void {
        if (this.hasSelection()) {
            super.selectElements();
            return;
        }

        if (
            this.$selectBtn &&
            ev?.currentTarget === this.$selectBtn[0] &&
            this.shouldEnableSelectBtn()
        ) {
            const sourcePath = this.elementIndex.sourcePath;
            const {folderId} = sourcePath[sourcePath.length - 1];
            this.onSelect([{folderId}]);

            if (this.settings.hideOnSelect) {
                this.hide();
            }
        }
    }

    override getElementInfo($selectedElements: any): Array<{folderId: number}> {
        const info: Array<{folderId: number}> = [];
        for (let i = 0; i < $selectedElements.length; i++) {
            const $element = $($selectedElements.eq(i).find('.element:first'));
            const folderId = parseInt($element.data('folder-id'));
            info.push({folderId});
        }
        return info;
    }

    override getIndexSettings(): Record<string, any> {
        return Object.assign(super.getIndexSettings(), {
            foldersOnly: true,
            viewSettings: () => ({
                canSelectElement: ($element: any) => {
                    const inner = $element.find('.element:first');
                    return hasAttr(inner[0], 'data-folder-id');
                },
            }),
        });
    }
}
