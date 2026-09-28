import {describe, expect, it} from 'vite-plus/test';
import {nestedOwnerContext} from './nested-owner';
import type {FormPayload} from '@/modules/forms/types';

describe('nestedOwnerId', () => {
  it('resolves the requested nested block owner rather than the root element', () => {
    const control = (path: string[], ownerId: number) => ({
      type: 'NestedElementCards',
      component: 'craft:nested-element-cards',
      mode: 'editable',
      path,
      deltaGroup: path,
      props: {
        manager: {
          ownerId,
          ownerIsDerivative: false,
          ownerIsInDerivativeTree: ownerId === 74,
        },
      },
    });
    const form = {
      scope: [],
      refreshable: false,
      values: {},
      errors: [],
      globalErrors: [],
      nodes: [
        {
          type: 'control',
          component: 'craft:control',
          props: {},
          control: control(['fields', 'cards'], 31),
        },
        {
          type: 'control',
          component: 'craft:control',
          props: {},
          control: {
            type: 'NestedElementBlocks',
            component: 'craft:nested-element-blocks',
            mode: 'editable',
            path: ['fields', 'blocks'],
            forms: [
              {
                scope: [],
                refreshable: false,
                nodes: [
                  {
                    type: 'control',
                    component: 'craft:control',
                    props: {},
                    control: control(
                      [
                        'fields',
                        'blocks',
                        'entries',
                        'first',
                        'fields',
                        'cards',
                      ],
                      74
                    ),
                  },
                ],
              },
            ],
          },
        },
      ],
    } as FormPayload;

    expect(nestedOwnerContext(form, ['fields', 'cards'])).toMatchObject({
      ownerId: 31,
      ownerIsDerivative: false,
      ownerIsInDerivativeTree: false,
    });
    expect(
      nestedOwnerContext(form, [
        'fields',
        'blocks',
        'entries',
        'first',
        'fields',
        'cards',
      ])
    ).toMatchObject({
      ownerId: 74,
      ownerIsDerivative: false,
      ownerIsInDerivativeTree: true,
    });
    expect(
      nestedOwnerContext(form, [
        'fields',
        'blocks',
        'entries',
        'other',
        'fields',
        'cards',
      ])
    ).toBeNull();
  });
});
