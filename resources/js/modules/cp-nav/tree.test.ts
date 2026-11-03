import {describe, expect, it} from 'vite-plus/test';
import {flatten, find, moveNode, type NavMove, type NavNode} from './tree';

function node(
  id: string,
  kind: NavNode['kind'],
  children: NavNode[] = [],
  extra: Partial<NavNode> = {}
): NavNode {
  return {
    id,
    kind,
    source: 'default',
    type: null,
    label: id,
    defaultLabel: id,
    icon: null,
    defaultIcon: null,
    iconSvg: null,
    href: `/admin/${id}`,
    url: null,
    newWindow: false,
    userCondition: null,
    home: kind === 'child' ? (extra.home ?? null) : null,
    hidden: false,
    inherited: false,
    heading: false,
    children,
    ...extra,
  };
}

const tree = (): NavNode[] => [
  node('dashboard', 'item'),
  node('content', 'section', [
    node('entries', 'item', [
      node('blog', 'child', [], {home: 'entries'}),
      node('news', 'child', [], {home: 'entries'}),
      node(
        'channels',
        'child',
        [node('events', 'child', [], {home: 'channels'})],
        {heading: true, home: 'entries'}
      ),
    ]),
    node('assets', 'item'),
  ]),
  node('admin', 'section', [node('users', 'item')]),
];

/** Each row as `id@level`. */
function shape(nodes: NavNode[]): string[] {
  return flatten(nodes).map(({node, level}) => `${node.id}@${level}`);
}

function move(id: string, to: NavMove): NavNode[] | null {
  return moveNode(tree(), id, to);
}

describe('cp nav tree', () => {
  it('lists top-level items before sections, whatever order they came in', () => {
    expect(
      shape([
        node('admin', 'section', [node('users', 'item')]),
        node('dashboard', 'item'),
      ])
    ).toEqual(['dashboard@1', 'admin@1', 'users@2']);
  });

  it('reorders a nested item among its siblings', () => {
    expect(
      shape(move('news', {type: 'before', targetId: 'blog'})!).slice(3, 5)
    ).toEqual(['news@3', 'blog@3']);
    expect(shape(move('news', {type: 'up'})!).slice(3, 5)).toEqual([
      'news@3',
      'blog@3',
    ]);
  });

  it('moves a nested item out to where it’s dropped, and back again', () => {
    const moved = move('events', {type: 'after', targetId: 'users'})!;

    expect(shape(moved)).toEqual([
      'dashboard@1',
      'content@1',
      'entries@2',
      'blog@3',
      'news@3',
      'channels@3',
      'assets@2',
      'admin@1',
      'users@2',
      'events@2',
    ]);
    expect(find(moved, 'events')?.kind).toBe('item');

    const back = moveNode(moved, 'events', {
      type: 'child',
      targetId: 'channels',
    })!;

    expect(shape(back)).toEqual(shape(tree()));
    expect(find(back, 'events')?.kind).toBe('child');
  });

  it('takes a moved item’s children along', () => {
    const moved = moveNode(
      [
        node('a', 'item', [
          node('b', 'child', [node('c', 'child', [], {home: 'b'})], {
            home: 'a',
          }),
        ]),
      ],
      'b',
      {type: 'to', parentId: null}
    )!;

    expect(shape(moved)).toEqual(['a@1', 'b@1', 'c@2']);
  });

  it('keeps sections at the top level, after its items', () => {
    expect(move('admin', {type: 'child', targetId: 'content'})).toBeNull();
    expect(move('admin', {type: 'before', targetId: 'dashboard'})).toBeNull();
    expect(
      shape(move('admin', {type: 'before', targetId: 'content'})!)[1]
    ).toBe('admin@1');
  });

  it('keeps items out of other items, and top-level items above sections', () => {
    expect(move('assets', {type: 'child', targetId: 'entries'})).toBeNull();
    expect(move('assets', {type: 'after', targetId: 'admin'})).toBeNull();
    expect(
      shape(move('assets', {type: 'after', targetId: 'dashboard'})!)[1]
    ).toBe('assets@1');
  });

  it('won’t move a heading out, or put a nested item under another item', () => {
    expect(move('channels', {type: 'to', parentId: null})).toBeNull();
    expect(move('blog', {type: 'child', targetId: 'assets'})).toBeNull();
    expect(move('blog', {type: 'child', targetId: 'channels'})).toBeNull();
  });
});
