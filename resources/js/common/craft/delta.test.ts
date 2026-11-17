import $ from 'jquery';
import {describe, expect, it} from 'vite-plus/test';
import {findDeltaData} from './delta';
import {param} from './params';

describe('param', () => {
  // Saved delta values and the server's bracketed param parsing were built
  // against jQuery's encoding, so it's the reference.
  it.each([
    {title: 'A & B', count: 3, empty: null, missing: undefined},
    {ids: [1, 2, 3], tags: ['a b', 'c=d']},
    {fields: {body: 'x', blocks: [{type: 'text', value: 'hi'}, ['nested']]}},
    {'already[]': [1, 2], flag: true, fn: () => 'called'},
    {date: new Date(0), regex: /x/},
  ])('encodes %o the way jQuery does', (value) => {
    expect(param(value)).toBe($.param(value));
  });

  it('matches jQuery for a list of form fields', () => {
    const fields = [
      {name: 'a', value: '1'},
      {name: 'b[]', value: 'x y'},
    ];

    expect(param(fields)).toBe($.param(fields));
  });
});

describe('findDeltaData', () => {
  const deltaNames = ['fields[body]', 'fields[summary]', 'title'];

  it('posts everything outside the delta names and only the groups that changed', () => {
    const before =
      'action=save&title=Old&fields%5Bbody%5D=same&fields%5Bsummary%5D=old';
    const after =
      'action=save&title=New&fields%5Bbody%5D=same&fields%5Bsummary%5D=new';

    expect(
      findDeltaData(before, after, deltaNames, null, {}, [], true)
    ).toEqual([
      'action=save',
      'title=New',
      'modifiedDeltaNames[]=title',
      'fields%5Bsummary%5D=new',
      'modifiedDeltaNames[]=fields[summary]',
    ]);
  });

  it('compares against initial values the form started with', () => {
    const after = 'title=Old&fields%5Bbody%5D=changed';

    expect(
      findDeltaData('', after, deltaNames, null, {
        title: 'Old',
        'fields[body]': 'original',
      })
    ).toBe('fields%5Bbody%5D=changed&modifiedDeltaNames[]=fields[body]');
  });

  it('posts a group the caller forces, even when unchanged', () => {
    const data = 'title=Same';

    expect(findDeltaData(data, data, deltaNames, null, {}, ['title'])).toBe(
      'title=Same&modifiedDeltaNames[]=title'
    );
  });
});
