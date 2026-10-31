import {describe, expect, it} from 'vite-plus/test';
import {resolveCmsEditionActions} from './cmsEditions';
import type {CmsEditionHandle, PluginStoreContext} from './types';

type Context = Pick<
  PluginStoreContext,
  'edition' | 'licensedEdition' | 'editions' | 'canTestEditions'
>;

function context(overrides: Partial<Context>): Context {
  return {
    edition: 'solo',
    licensedEdition: 'solo',
    editions: ['solo', 'team', 'pro', 'enterprise'],
    canTestEditions: true,
    ...overrides,
  };
}

const prices: Record<string, number> = {solo: 0, team: 279, pro: 399};

describe('resolveCmsEditionActions', () => {
  it.each([
    {
      name: 'the licensed edition that’s running is installed',
      handle: 'solo',
      ctx: {},
      expected: {status: 'installed', purchase: null, switchTo: null},
    },
    {
      name: 'a higher edition can be bought or tried',
      handle: 'pro',
      ctx: {},
      expected: {status: null, purchase: 'buy', switchTo: 'try'},
    },
    {
      name: 'a higher edition can only be bought where testing isn’t allowed',
      handle: 'pro',
      ctx: {canTestEditions: false},
      expected: {status: null, purchase: 'buy', switchTo: null},
    },
    {
      name: 'an edition running without a license is a trial',
      handle: 'pro',
      ctx: {edition: 'pro'},
      expected: {status: 'installed-trial', purchase: 'buy', switchTo: null},
    },
    {
      name: 'the licensed edition can be gone back to from a trial',
      handle: 'team',
      ctx: {edition: 'pro', licensedEdition: 'team'},
      expected: {status: 'licensed', purchase: null, switchTo: 'reactivate'},
    },
    {
      name: 'a lower edition than the licensed one offers nothing',
      handle: 'team',
      ctx: {edition: 'pro', licensedEdition: 'pro'},
      expected: {status: null, purchase: null, switchTo: null},
    },
    {
      name: 'an unlicensed install can buy its way up',
      handle: 'team',
      ctx: {licensedEdition: null},
      expected: {status: null, purchase: 'buy', switchTo: 'try'},
    },
  ])('$name', ({handle, ctx, expected}) => {
    expect(
      resolveCmsEditionActions(
        handle as CmsEditionHandle,
        prices[handle]!,
        context(ctx as Partial<Context>),
        false
      )
    ).toEqual(expected);
  });

  it('points to the cart once the edition is in it', () => {
    expect(
      resolveCmsEditionActions('pro', 399, context({}), true).purchase
    ).toBe('in-cart');
  });
});
