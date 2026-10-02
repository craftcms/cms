import boldIcon from '@icons/solid/bold.svg?raw';
import circleQuestionIcon from '@icons/solid/circle-question.svg?raw';
import codeIcon from '@icons/solid/code.svg?raw';
import eyeIcon from '@icons/solid/eye.svg?raw';
import h1Icon from '@icons/solid/h1.svg?raw';
import h2Icon from '@icons/solid/h2.svg?raw';
import h3Icon from '@icons/solid/h3.svg?raw';
import h4Icon from '@icons/solid/h4.svg?raw';
import h5Icon from '@icons/solid/h5.svg?raw';
import h6Icon from '@icons/solid/h6.svg?raw';
import italicIcon from '@icons/solid/italic.svg?raw';
import linkIcon from '@icons/solid/link.svg?raw';
import listCheckIcon from '@icons/solid/list-check.svg?raw';
import listOlIcon from '@icons/solid/list-ol.svg?raw';
import listUlIcon from '@icons/solid/list-ul.svg?raw';
import paperclipIcon from '@icons/solid/paperclip.svg?raw';
import quoteLeftIcon from '@icons/solid/quote-left.svg?raw';
import strikethroughIcon from '@icons/solid/strikethrough.svg?raw';
import uploadIcon from '@icons/solid/upload.svg?raw';

export const customIcons = {
  bold: decorativeIcon(boldIcon),
  'circle-question': decorativeIcon(circleQuestionIcon),
  code: decorativeIcon(codeIcon),
  eye: decorativeIcon(eyeIcon),
  h1: decorativeIcon(h1Icon),
  h2: decorativeIcon(h2Icon),
  h3: decorativeIcon(h3Icon),
  h4: decorativeIcon(h4Icon),
  h5: decorativeIcon(h5Icon),
  h6: decorativeIcon(h6Icon),
  italic: decorativeIcon(italicIcon),
  link: decorativeIcon(linkIcon),
  'list-check': decorativeIcon(listCheckIcon),
  'list-ol': decorativeIcon(listOlIcon),
  'list-ul': decorativeIcon(listUlIcon),
  paperclip: decorativeIcon(paperclipIcon),
  'quotes-left': decorativeIcon(quoteLeftIcon),
  strikethrough: decorativeIcon(strikethroughIcon),
  upload: decorativeIcon(uploadIcon),
};

function decorativeIcon(svg: string): string {
  return svg.replace('<svg ', '<svg aria-hidden="true" focusable="false" ');
}
