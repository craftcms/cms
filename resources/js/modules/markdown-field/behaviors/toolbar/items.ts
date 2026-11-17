import {t} from '@craftcms/ui';
import {toolbarButtons, type ToolbarButton} from 'overtype';
import {customIcons} from './icons';

type CraftToolbarButton = ToolbarButton & {
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
type ToolbarActiveState = NonNullable<ToolbarButton['isActive']>;

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
      customizeToolbarButton(
        toolbarButtons.strikethrough,
        t('Strikethrough'),
        'strikethrough'
      ),
      customizeToolbarButton(
        toolbarButtons.code,
        t('Code'),
        'code',
        undefined,
        ({activeFormats}) => activeFormats.includes('code')
      ),
    ],
    [
      customizeToolbarButton(toolbarButtons.h1, t('Heading 1'), 'h1'),
      customizeToolbarButton(toolbarButtons.h2, t('Heading 2'), 'h2'),
      customizeToolbarButton(toolbarButtons.h3, t('Heading 3'), 'h3'),
      customizeToolbarButton(toolbarButtons.h4, t('Heading 4'), 'h4'),
      customizeToolbarButton(toolbarButtons.h5, t('Heading 5'), 'h5'),
      customizeToolbarButton(toolbarButtons.h6, t('Heading 6'), 'h6'),
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
      {
        ...customToolbarButton(
          'link',
          'link',
          t('Link'),
          ({event}) => callbacks.openLinkPopover(event),
          'insertLink'
        ),
        shortcut: toolbarButtons.link.shortcut,
      },
      customToolbarButton(
        'asset',
        'paperclip',
        t('Asset'),
        callbacks.openAssetSelector
      ),
      customizeToolbarButton(toolbarButtons.upload, t('Upload File'), 'upload'),
    ],
    [
      {
        ...customToolbarButton(
          'preview',
          'eye',
          t('Preview'),
          callbacks.togglePreview
        ),
        shortcut: 'Mod+Shift+P',
      },
      customToolbarButton(
        'guide',
        'circle-question',
        t('Markdown Guide'),
        () => {}
      ),
    ],
  ];
}

function customizeToolbarButton(
  button: ToolbarButton,
  title: string,
  icon: keyof typeof customIcons,
  optionName = button.name,
  isActive = button.isActive
): CraftToolbarButton {
  return {
    ...button,
    icon: customIcons[icon] ?? button.icon,
    isActive,
    optionName,
    title,
  };
}

function customToolbarButton(
  name: string,
  icon: keyof typeof customIcons,
  title: string,
  action: ToolbarAction,
  actionId?: string,
  isActive?: ToolbarActiveState
): CraftToolbarButton {
  return {
    actionId,
    action,
    icon: customIcons[icon] ?? '',
    isActive,
    name,
    title,
  };
}
