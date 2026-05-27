import {t} from '@craftcms/cp';
import {markdownActions, toolbarButtons, type ToolbarButton} from 'overtype';
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

type CraftToolbarButton = ToolbarButton & {
  actionId?: string;
};

type ToolbarOptions = {
  assetSources: string[];
  toolbarButtons: string[];
  uploadFolderId: number | null;
};

type ToolbarCallbacks = {
  openAssetSelector: () => void;
  togglePreview: () => void | Promise<void>;
};

type ToolbarAction = NonNullable<ToolbarButton['action']>;

const strikethroughFormat = {
  prefix: '~~',
  suffix: '~~',
  trimFirst: true,
};

const customIcons: Record<string, string> = {
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

export function toolbarItems(
  options: ToolbarOptions,
  callbacks: ToolbarCallbacks
): CraftToolbarButton[] {
  const selectedButtons = new Set(options.toolbarButtons);
  const requiredButtons = new Set(options.uploadFolderId ? ['upload'] : []);

  if (!options.assetSources.length) {
    selectedButtons.delete('asset');
  }

  const items: CraftToolbarButton[] = [];

  for (const group of toolbarButtonGroups(callbacks)) {
    const groupItems = group.filter(
      (item) => selectedButtons.has(item.name) || requiredButtons.has(item.name)
    );

    if (!groupItems.length) {
      continue;
    }

    if (items.length) {
      items.push(toolbarButtons.separator);
    }

    items.push(...groupItems);
  }

  return items;
}

function toolbarButtonGroups(
  callbacks: ToolbarCallbacks
): CraftToolbarButton[][] {
  return [
    [
      customizeToolbarButton(toolbarButtons.bold, t('Bold'), 'bold'),
      customizeToolbarButton(toolbarButtons.italic, t('Italic'), 'italic'),
      customToolbarButton(
        'strikethrough',
        'strikethrough',
        t('Strikethrough'),
        ({editor}) => {
          markdownActions.applyCustomFormat(
            editor.textarea,
            strikethroughFormat
          );
        }
      ),
      customizeToolbarButton(toolbarButtons.code, t('Code'), 'code'),
    ],
    [
      customizeToolbarButton(
        toolbarButtons.h1,
        t('Big Heading'),
        'h1',
        'heading-1'
      ),
      customizeToolbarButton(
        toolbarButtons.h2,
        t('Medium Heading'),
        'h2',
        'heading-2'
      ),
      customizeToolbarButton(
        toolbarButtons.h3,
        t('Small Heading'),
        'h3',
        'heading-3'
      ),
      headingButton(4, t('Heading 4')),
      headingButton(5, t('Heading 5')),
      headingButton(6, t('Heading 6')),
      customizeToolbarButton(toolbarButtons.quote, t('Quote'), 'quotes-left'),
    ],
    [
      customizeToolbarButton(
        toolbarButtons.bulletList,
        t('Bulleted List'),
        'list-ul',
        'unordered-list'
      ),
      customizeToolbarButton(
        toolbarButtons.orderedList,
        t('Numbered List'),
        'list-ol',
        'ordered-list'
      ),
      customizeToolbarButton(
        toolbarButtons.taskList,
        t('Check List'),
        'list-check',
        'check-list'
      ),
    ],
    [
      customizeToolbarButton(toolbarButtons.link, t('Link'), 'link'),
      customToolbarButton(
        'asset',
        'paperclip',
        t('Asset'),
        callbacks.openAssetSelector
      ),
      customizeToolbarButton(toolbarButtons.upload, t('Upload File'), 'upload'),
    ],
    [
      customToolbarButton(
        'preview',
        'eye',
        t('Preview'),
        callbacks.togglePreview
      ),
      customToolbarButton(
        'guide',
        'circle-question',
        t('Markdown Guide'),
        () => {
          window.open(
            'https://www.markdownguide.org/basic-syntax/',
            '_blank',
            'noopener'
          );
        }
      ),
    ],
  ];
}

function headingButton(level: 4 | 5 | 6, title: string): CraftToolbarButton {
  return customToolbarButton(
    `heading-${level}`,
    `h${level}`,
    title,
    ({editor}) => {
      markdownActions.insertHeader(editor.textarea, level, true);
      editor.textarea.dispatchEvent(new Event('input', {bubbles: true}));
    }
  );
}

function customizeToolbarButton(
  button: ToolbarButton,
  title: string,
  icon: string,
  name = button.name
): CraftToolbarButton {
  return {
    ...button,
    icon: customIcons[icon] ?? button.icon,
    name,
    title,
  };
}

function customToolbarButton(
  name: string,
  icon: string,
  title: string,
  action: ToolbarAction
): CraftToolbarButton {
  return {
    action,
    icon: customIcons[icon] ?? '',
    name,
    title,
  };
}

function decorativeIcon(svg: string): string {
  return svg.replace('<svg ', '<svg aria-hidden="true" focusable="false" ');
}
