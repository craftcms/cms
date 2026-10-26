import {highlighter} from '@/common/utils/highlight-code';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * Plugins highlight code with `Craft.highlighter`, as in Craft 5:
 * `Craft.highlighter.highlight(code, {lang: 'json'}).html`.
 */
registerCraftGlobals({highlighter});

export {highlighter};
