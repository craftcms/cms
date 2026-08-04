import {describe, test, expect} from 'vite-plus/test';
import {t, tokenizePattern, parseToken, formatMessage} from './translate.js';

const translations = {
  app: {
    'Are you sure you want to delete this {type}?':
      'This is a translated string {type}?',
    '<span class="visually-hidden">Characters left:</span> {chars, number}':
      '<span class="visually-hidden">Characters left:</span> {chars, number}',
    'Delete {num, plural, =1{user} other{users}} and content':
      'Delete {num, plural, =1{user} other{users}} and content',
  },
  site: {
    Welcome: 'Bienvenue',
  },
};

describe('Translate', () => {
  describe('t', () => {
    test.for([
      ['Welcome', {}, 'site', translations, 'Bienvenue'],
      ['Not translated', {}, 'app', translations, 'Not translated'],
      ['Hello', {}, 'unknown', translations, 'Hello'],
      ['Simple message', null, 'app', translations, 'Simple message'],
      [
        'Are you sure you want to delete this {type}?',
        {type: 'item'},
        'app',
        translations,
        'This is a translated string item?',
      ],
      ['Hello {name}', {name: 'World'}, 'app', {}, 'Hello World'],
      [
        'Delete {num, plural, =1{user} other{users}} and content',
        {num: 1},
        'app',
        translations,
        'Delete user and content',
      ],
      [
        'Delete {num, plural, =1{user} other{users}} and content',
        {num: 5},
        'app',
        translations,
        'Delete users and content',
      ],
      [
        'Are you sure you want to delete this {type}?',
        {type: 'item'},
        'app',
        translations,
        'This is a translated string item?',
      ],
      [
        '<span class="visually-hidden">Characters left:</span> {chars, number}',
        {chars: 100},
        'app',
        translations,
        '<span class="visually-hidden">Characters left:</span> 100',
      ],
    ])(
      't(%s, %s, %s) -> %s',
      ([message, params, category, store, expected]) => {
        expect(
          t(
            message as string,
            params as any,
            category as string,
            store as Record<string, any>
          )
        ).toBe(expected);
      }
    );

    test('works without store parameter', () => {
      expect(t('Hello {name}', {name: 'World'}, 'app')).toBe('Hello World');
    });
  });

  describe('tokenizePattern', () => {
    test.for([
      // Simple string without any tokens
      ['Hello world', ['Hello world']],

      // Single simple token
      ['Hello {name}', ['Hello ', ['name'], '']],

      // Multiple simple tokens
      ['Hello {first} {last}', ['Hello ', ['first'], ' ', ['last'], '']],

      // Token with type
      ['{count, number}', ['', ['count', ' number'], '']],

      // Token with type and format
      ['{count, number, integer}', ['', ['count', ' number', ' integer'], '']],

      // Complex plural pattern
      [
        '{num, plural, =1{user} other{users}}',
        ['', ['num', ' plural', ' =1{user} other{users}'], ''],
      ],

      // Pattern with text before and after token
      [
        'Delete {num, plural, =1{user} other{users}} and content',
        [
          'Delete ',
          ['num', ' plural', ' =1{user} other{users}'],
          ' and content',
        ],
      ],

      // HTML with token
      [
        '<span class="visually-hidden">Characters left:</span> {chars, number}',
        [
          '<span class="visually-hidden">Characters left:</span> ',
          ['chars', ' number'],
          '',
        ],
      ],

      // Nested braces in plural
      [
        '{count, plural, =0{no items} =1{one item} other{# items}}',
        [
          '',
          ['count', ' plural', ' =0{no items} =1{one item} other{# items}'],
          '',
        ],
      ],

      // Select pattern
      [
        '{gender, select, male{He} female{She} other{They}}',
        ['', ['gender', ' select', ' male{He} female{She} other{They}'], ''],
      ],

      // Empty string
      ['', ['']],

      // No braces at all
      ['Just plain text', ['Just plain text']],

      // Unmatched opening brace (should return false)
      ['Hello {name', false],

      // Unmatched closing brace (should return false)
      ['Hello name}', ['Hello name}']],

      // Multiple tokens with different types
      [
        'User {name} has {count, number} items',
        ['User ', ['name'], ' has ', ['count', ' number'], ' items'],
      ],
    ])('tokenize(%s)', ([pattern, expected]) => {
      expect(tokenizePattern(pattern as string)).toEqual(expected);
    });
  });

  describe('parseToken', () => {
    test.for([
      // Returns original token syntax when parameter is undefined
      [['name'], {}, '{name}'],
      [['count', ' number'], {}, '{count, number}'],
      [['count', ' number', ' integer'], {}, '{count, number, integer}'],

      // Simple token substitution (type "none")
      [['name'], {name: 'Alice'}, 'Alice'],
      [['greeting'], {greeting: 'Hello World'}, 'Hello World'],

      // Different value types
      [['count'], {count: 42}, 42],
      [['flag'], {flag: true}, true],
      [['data'], {data: null}, null],
      [['value'], {value: 'test'}, 'test'],
    ])('parseToken(%s, %s) -> %s', ([token, args, expected]) => {
      expect(parseToken(token as string[], args as object)).toBe(expected);
    });

    test('parseToken throws error for unsupported message format type', () => {
      const token = ['date', ' datetime'];
      const args = {date: '2024-01-01'};
      expect(() => parseToken(token, args)).toThrow(
        "Message format 'datetime' is not supported."
      );
    });

    describe('select', () => {
      test.for([
        [
          ['gender', ' select', ' male{He} female{She} other{They}'],
          {gender: 'male'},
          'He',
        ],
        [
          ['gender', ' select', ' male{He} female{She} other{They}'],
          {gender: 'female'},
          'She',
        ],
        [
          ['gender', ' select', ' male{He} female{She} other{They}'],
          {gender: 'unknown'},
          'They',
        ],
        [
          [
            'role',
            ' select',
            ' admin{Hello {name}} user{Hi {name}} other{Greetings}',
          ],
          {role: 'admin', name: 'Alice'},
          'Hello Alice',
        ],
        [
          [
            'role',
            ' select',
            ' admin{Hello {name}} user{Hi {name}} other{Greetings}',
          ],
          {role: 'user', name: 'Bob'},
          'Hi Bob',
        ],
      ])('select %s with args %s -> %s', ([token, args, expected]) => {
        expect(parseToken(token as string[], args as Record<string, any>)).toBe(
          expected
        );
      });

      test.for([
        [['gender', ' select'], {gender: 'male'}],
        [['gender', ' select', ' male{He'], {gender: 'male'}],
        [
          ['status', ' select', ' active{Active} inactive{Inactive}'],
          {status: 'pending'},
        ],
      ])('returns false for invalid pattern %s', ([token, args]) => {
        expect(parseToken(token as string[], args as Record<string, any>)).toBe(
          false
        );
      });
    });

    describe('plural', () => {
      test.for([
        // With # replacement
        [
          ['count', ' plural', ' =0{no items} =1{one item} other{# items}'],
          {count: 0},
          'no items',
        ],
        [
          ['count', ' plural', ' =0{no items} =1{one item} other{# items}'],
          {count: 1},
          'one item',
        ],
        [
          ['count', ' plural', ' =0{no items} =1{one item} other{# items}'],
          {count: 5},
          '5 items',
        ],

        // Without # (prepends number for all matches)
        [['num', ' plural', ' =1{item} other{items}'], {num: 1}, 'item'],
        [['num', ' plural', ' =1{item} other{items}'], {num: 10}, 'items'],

        // With "one" keyword
        [
          ['count', ' plural', ' one{# item} other{# items}'],
          {count: 1},
          '1 item',
        ],
        [
          ['count', ' plural', ' one{# item} other{# items}'],
          {count: 2},
          '2 items',
        ],

        // Explicit values
        [
          ['count', ' plural', ' =0{none} =1{one} =2{two} other{many}'],
          {count: 0},
          'none',
        ],
        [
          ['count', ' plural', ' =0{none} =1{one} =2{two} other{many}'],
          {count: 1},
          'one',
        ],
        [
          ['count', ' plural', ' =0{none} =1{one} =2{two} other{many}'],
          {count: 2},
          'two',
        ],
        [
          ['count', ' plural', ' =0{none} =1{one} =2{two} other{many}'],
          {count: 5},
          'many',
        ],

        // With offset
        [
          [
            'count',
            ' plural',
            ' offset:1 =1{just you} other{you and # others}',
          ],
          {count: 1},
          'you and 0 others',
        ],
        [
          [
            'count',
            ' plural',
            ' offset:1 =0{did not like this} =1{liked this} one{and one other person liked this} other{and # others liked this}',
          ],
          {count: 2},
          'and one other person liked this',
        ],

        // With nested tokens
        [
          [
            'count',
            ' plural',
            ' =1{{name} has one item} other{{name} has # items}',
          ],
          {count: 1, name: 'Alice'},
          'Alice has one item',
        ],
        [
          [
            'count',
            ' plural',
            ' =1{{name} has one item} other{{name} has # items}',
          ],
          {count: 5, name: 'Bob'},
          'Bob has 5 items',
        ],
      ])('plural %s with args %s -> %s', ([token, args, expected]) => {
        expect(parseToken(token as string[], args as Record<string, any>)).toBe(
          expected
        );
      });

      test.for([
        [['count', ' plural'], {count: 1}],
        [['count', ' plural', ' =1{item'], {count: 1}],
      ])('returns false for invalid pattern %s', ([token, args]) => {
        expect(parseToken(token as string[], args as Record<string, any>)).toBe(
          false
        );
      });

      test('returns false for invalid offset pattern', () => {
        const token = ['count', ' plural', ' offset:'];
        expect(() => parseToken(token, {count: 1})).toBeFalsy;
      });
    });
  });

  describe('formatMessage', () => {
    test.for([
      ['Hello {name}', {name: 'World'}, 'Hello World'],
      ['Hello {first} {last}', {first: 'John', last: 'Doe'}, 'Hello John Doe'],
      ['Count: {num, number}', {num: 1234}, 'Count: 1,234'],
      [
        'You have {count, plural, =0{no items} =1{one item} other{# items}}',
        {count: 0},
        'You have no items',
      ],
      [
        'You have {count, plural, =0{no items} =1{one item} other{# items}}',
        {count: 1},
        'You have one item',
      ],
      [
        'You have {count, plural, =0{no items} =1{one item} other{# items}}',
        {count: 5},
        'You have 5 items',
      ],
      [
        'The selected {relatedType} {count, plural, =1{contains} other{contain}} validation errors, preventing this {type} from being saved. Edit the {relatedType} to fix them.',
        {relatedType: 'user', count: 1, type: 'post'},
        'The selected user contains validation errors, preventing this post from being saved. Edit the user to fix them.',
      ],
      [
        '{gender, select, male{He} female{She} other{They}} arrived',
        {gender: 'male'},
        'He arrived',
      ],
      ['Just plain text', {}, 'Just plain text'],
      ['Hello {name}', {}, 'Hello {name}'],
      [
        '{name} has {count, plural, =0{no items} =1{one item} other{# items}} in {gender, select, male{his} female{her} other{their}} cart',
        {name: 'Alice', count: 3, gender: 'female'},
        'Alice has 3 items in her cart',
      ],
    ])('formatMessage(%s, %s) -> %s', ([pattern, params, expected]) => {
      expect(
        formatMessage(pattern as string, params as Record<string, any>)
      ).toBe(expected);
    });

    test.for([
      ['Hello {name', {}],
      ['{count, plural}', {count: 1}],
    ])('throws error for invalid pattern %s', ([pattern, params]) => {
      expect(() =>
        formatMessage(pattern as string, params as Record<string, any>)
      ).toThrow('Message pattern is invalid.');
    });
  });
});
