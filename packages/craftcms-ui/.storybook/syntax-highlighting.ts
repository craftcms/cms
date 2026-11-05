import {SyntaxHighlighter} from 'storybook/internal/components';
// @ts-expect-error refractor 3 ships no types.
import php from 'refractor/lang/php.js';

/**
 * Registers languages that Storybook's code blocks don't highlight out of the
 * box, such as the PHP examples in docs pages. Storybook's highlighter is
 * built on refractor 3, so its language definitions plug straight in.
 */
SyntaxHighlighter.registerLanguage('php', php);
