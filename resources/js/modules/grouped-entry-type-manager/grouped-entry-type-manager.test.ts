import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {GroupedEntryTypeManager} from './grouped-entry-type-manager';

describe('GroupedEntryTypeManager', () => {
  let container: HTMLElement;
  let onChange: ReturnType<typeof vi.fn<(event: Event) => void>>;

  beforeEach(() => {
    container = document.createElement('div');
    container.innerHTML = `
      <ul class="entry-type-groups">
        <li class="entry-type-group" data-name="General">
          <div class="entry-type-group--titlebar"><span>General</span></div>
        </li>
        <li class="entry-type-group" data-name="Media">
          <div class="entry-type-group--titlebar"><span>Media</span></div>
        </li>
      </ul>
    `;
    document.body.append(container);
    onChange = vi.fn<(event: Event) => void>();
    container.addEventListener('change', onChange);
  });

  afterEach(() => {
    container.remove();
  });

  it('boots without announcing a change', () => {
    new GroupedEntryTypeManager(container);

    expect(onChange).not.toHaveBeenCalled();
  });

  it('announces a change when a group is moved', () => {
    const manager = new GroupedEntryTypeManager(container);

    manager.groups[0]!.move('down');

    expect(onChange).toHaveBeenCalledTimes(1);
    expect(manager.groups.map((group) => group.name)).toEqual([
      'Media',
      'General',
    ]);
  });
});
