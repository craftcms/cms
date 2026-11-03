import {t} from '@craftcms/ui';

/**
 * Wraps each table in prose HTML in a scrolling region, so a wide table scrolls
 * sideways while keeping its table semantics. The region is focusable, so it
 * can be scrolled from the keyboard, and named by the table's caption.
 */
export function withScrollableTables(html: string): string {
  const template = document.createElement('template');
  template.innerHTML = html;

  for (const table of template.content.querySelectorAll('table')) {
    const region = document.createElement('div');
    region.className = 'cp-prose-table';
    region.tabIndex = 0;
    region.setAttribute('role', 'region');
    region.setAttribute(
      'aria-label',
      table.caption?.textContent?.trim() || t('Table')
    );
    table.replaceWith(region);
    region.append(table);
  }

  return template.innerHTML;
}
