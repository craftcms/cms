import '../../../../css/cp.css';
import '@craftcms/ui';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';
import {contrast} from '../../../../../packages/craftcms-ui/test/contrast';
import ResultSection from './ResultSection.vue';

let app: App | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  document.body.innerHTML = '';
});

describe.each(['light', 'dark'] as const)('in the %s theme', (theme) => {
  it('keeps a shortcut’s keys legible on the selected row', () => {
    const host = document.createElement('div');
    host.dataset.theme = theme;
    document.body.append(host);
    app = createApp(() =>
      h(ResultSection, {
        groupId: 'actions',
        heading: 'Actions',
        rows: [
          {
            item: {
              id: 'toggle-sidebar',
              label: 'Collapse the sidebar',
              shortcut: 'Mod+Shift+[Backslash]',
            },
            index: 0,
            optionId: 'option-0',
          },
        ],
        selectedIndex: 0,
      })
    );
    app.mount(host);

    const row = host.querySelector<HTMLElement>('.command-palette-row')!;
    const rowStyle = getComputedStyle(row);
    // The selected row's loud fill is painted, with text to match.
    expect(rowStyle.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');
    expect(
      contrast(rowStyle.color, rowStyle.backgroundColor)
    ).toBeGreaterThanOrEqual(4.5);

    const keys = [...row.querySelectorAll('kbd')];
    expect(keys).toHaveLength(3);

    for (const key of keys) {
      const style = getComputedStyle(key);

      expect(
        contrast(style.color, style.backgroundColor, rowStyle.backgroundColor)
      ).toBeGreaterThanOrEqual(4.5);
    }
  });
});
