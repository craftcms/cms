import {t} from '@craftcms/cp';
import {markdownActions, toolbarButtons, type ToolbarButton} from 'overtype';
import {customIcons} from './icons';

type CraftToolbarButton = ToolbarButton & {
  actionId?: string;
  optionName?: string;
};

type ToolbarOptions = {
  assetSources: string[];
  toolbarButtons: string[];
  uploadFolderId: number | null;
};

type ToolbarCallbacks = {
  openLinkPopover: (event?: Event | null) => void;
  openAssetSelector: () => void;
  togglePreview: () => void | Promise<void>;
};

type ToolbarAction = NonNullable<ToolbarButton['action']>;

const strikethroughFormat = {
  prefix: '~~',
  suffix: '~~',
  trimFirst: true,
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
    const groupItems = group.filter((item) => {
      const optionName = item.optionName ?? item.name;

      return selectedButtons.has(optionName) || requiredButtons.has(optionName);
    });

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
          editor.textarea.dispatchEvent(new Event('input', {bubbles: true}));
        }
      ),
      customizeToolbarButton(toolbarButtons.code, t('Code'), 'code'),
    ],
    [
      customizeToolbarButton(
        toolbarButtons.h1,
        t('Heading 1'),
        'h1',
        'heading-1'
      ),
      customizeToolbarButton(
        toolbarButtons.h2,
        t('Heading 2'),
        'h2',
        'heading-2'
      ),
      customizeToolbarButton(
        toolbarButtons.h3,
        t('Heading 3'),
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
      customToolbarButton(
        'link',
        'link',
        t('Link'),
        ({event}) => callbacks.openLinkPopover(event),
        'insertLink'
      ),
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
        () => {}
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
  optionName = button.name
): CraftToolbarButton {
  return {
    ...button,
    icon: customIcons[icon] ?? button.icon,
    optionName,
    title,
  };
}

function customToolbarButton(
  name: string,
  icon: string,
  title: string,
  action: ToolbarAction,
  actionId?: string
): CraftToolbarButton {
  return {
    actionId,
    action,
    icon: customIcons[icon] ?? '',
    name,
    title,
  };
}
