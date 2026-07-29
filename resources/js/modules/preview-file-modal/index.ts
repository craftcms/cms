import {PreviewFileModal} from './preview-file-modal';

export {PreviewFileModal};

declare const window: any;

export function registerCraftGlobals(): void {
  window.Craft.PreviewFileModal = PreviewFileModal;
}
