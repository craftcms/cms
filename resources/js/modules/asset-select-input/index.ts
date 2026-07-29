import {AssetSelectInput} from './asset-select-input';

export {AssetSelectInput};

declare const window: any;

export function registerCraftGlobals(): void {
  window.Craft.AssetSelectInput = AssetSelectInput;
}
