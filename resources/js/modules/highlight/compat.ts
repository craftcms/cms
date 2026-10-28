import {highlighter} from '@/common/utils/highlight-code';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * The deprecated Prism and highlight asset bundles point Craft 5 plugins at
 * `Craft.highlighter.highlight(code, {lang: 'json'}).html`.
 */
registerCraftGlobals({highlighter});
