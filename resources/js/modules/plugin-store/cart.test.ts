import {describe, expect, it} from 'vite-plus/test';
import {
  cartItemsData,
  cmsTrialRow,
  isPluginBuyable,
  newCartItemData,
  pendingTrialRows,
  pluginTrialRow,
  updateOptions,
} from './cart';
import type {
  Cart,
  CartLineItem,
  PluginLicenseInfo,
  PluginStoreContext,
  PluginSummary,
} from './types';

function makeInfo(
  overrides: Partial<PluginLicenseInfo> = {}
): PluginLicenseInfo {
  return {
    isInstalled: true,
    isEnabled: true,
    isTrial: false,
    edition: 'lite',
    licensedEdition: 'lite',
    licenseKey: 'KEY',
    licenseKeyStatus: 'valid',
    licenseIssues: [],
    ...overrides,
  };
}

function makeLineItem(
  overrides: Partial<CartLineItem['options']> = {}
): CartLineItem {
  return {
    price: 99,
    purchasable: {
      type: 'plugin-edition',
      handle: 'pro',
      name: 'Pro',
      renewalPrice: '39',
      pluginId: 7,
      plugin: {id: 7, handle: 'seo'},
    },
    options: {
      expiryDate: '1y',
      autoRenew: true,
      licenseKey: 'new:ABC',
      ...overrides,
    },
    adjustments: [],
  };
}

const plugin: PluginSummary = {
  id: 7,
  handle: 'seo',
  name: 'SEO',
  editions: [
    {
      id: 1,
      name: 'Lite',
      handle: 'lite',
      price: '49',
      renewalPrice: '19',
      features: null,
    },
    {
      id: 2,
      name: 'Pro',
      handle: 'pro',
      price: '99',
      renewalPrice: '39',
      features: null,
    },
  ],
} as PluginSummary;

describe('newCartItemData', () => {
  const item = {type: 'plugin-edition', plugin: 'seo', edition: 'pro'} as const;

  it.each([
    {name: 'a valid license', info: makeInfo(), keeps: true},
    {
      name: 'a trial license',
      info: makeInfo({licenseKeyStatus: 'trial'}),
      keeps: true,
    },
    {
      name: 'a license for another edition',
      info: makeInfo({licenseIssues: ['wrong_edition']}),
      keeps: true,
    },
    {
      name: 'a license tied to another install',
      info: makeInfo({licenseIssues: ['mismatched']}),
      keeps: false,
    },
    {
      name: 'an invalid license',
      info: makeInfo({licenseKeyStatus: 'invalid'}),
      keeps: false,
    },
    {name: 'no license', info: null, keeps: false},
  ])('keeps the plugin’s license only for $name', ({info, keeps}) => {
    const data = newCartItemData(item, info, 'CMS');

    expect(data).toMatchObject({
      ...item,
      expiryDate: '1y',
      autoRenew: true,
      cmsLicenseKey: 'CMS',
    });
    expect(data.licenseKey).toBe(keeps ? 'KEY' : undefined);
  });

  it('upgrades the install’s own Craft license', () => {
    expect(
      newCartItemData({type: 'cms-edition', edition: 'pro'}, null, 'CMS')
    ).toEqual({
      type: 'cms-edition',
      edition: 'pro',
      expiryDate: '1y',
      autoRenew: true,
      licenseKey: 'CMS',
    });
  });
});

describe('cartItemsData', () => {
  it('sends back existing license keys but not placeholders for new ones', () => {
    const cart = {
      lineItems: [
        makeLineItem(),
        makeLineItem({licenseKey: 'REAL', autoRenew: false}),
      ],
    } as Cart;

    expect(cartItemsData(cart, 'CMS')).toEqual([
      {
        type: 'plugin-edition',
        edition: 'pro',
        plugin: 'seo',
        cmsLicenseKey: 'CMS',
        expiryDate: '1y',
        autoRenew: true,
      },
      {
        type: 'plugin-edition',
        edition: 'pro',
        plugin: 'seo',
        cmsLicenseKey: 'CMS',
        expiryDate: '1y',
        autoRenew: false,
        licenseKey: 'REAL',
      },
    ]);
  });
});

describe('updateOptions', () => {
  const options: Array<[string, string]> = [
    ['1y', '2027-10-28'],
    ['2y', '2028-10-28'],
    ['3y', '2029-10-28'],
  ];

  it('prices each option against the one chosen', () => {
    const labels = updateOptions(makeLineItem({expiryDate: '2y'}), options).map(
      (option) => option.label
    );

    expect(labels[0]).toContain('(-$39)');
    expect(labels[1]).not.toContain('$');
    expect(labels[2]).toContain('(+$39)');
  });
});

describe('isPluginBuyable', () => {
  it.each([
    {name: 'isn’t installed', info: null, buyable: true},
    {
      name: 'is on trial',
      info: makeInfo({licenseKeyStatus: 'trial'}),
      buyable: true,
    },
    {name: 'has no license', info: makeInfo({licenseKey: null}), buyable: true},
    {
      name: 'is licensed to another install',
      info: makeInfo({licenseIssues: ['mismatched']}),
      buyable: true,
    },
    {name: 'is already licensed', info: makeInfo(), buyable: false},
  ])('is buyable when it $name: $buyable', ({info, buyable}) => {
    expect(isPluginBuyable(plugin, info)).toBe(buyable);
  });

  it('never offers a free plugin', () => {
    const free = {editions: [{...plugin.editions[0]!, price: null}]};
    expect(isPluginBuyable(free, null)).toBe(false);
  });
});

describe('trial rows', () => {
  const context = {
    edition: 'pro',
    licensedEdition: 'solo',
    editions: ['solo', 'team', 'pro', 'enterprise'],
  } as PluginStoreContext;
  const editions = [
    {handle: 'pro', name: 'Pro', price: '299', renewalPrice: '59'},
  ] as const;

  it('offers the Craft edition being trialed', () => {
    expect(cmsTrialRow(context, [...editions], 'icon.svg')).toMatchObject({
      item: {type: 'cms-edition', edition: 'pro'},
      price: 299,
    });
    expect(
      cmsTrialRow(
        {...context, licensedEdition: 'pro'},
        [...editions],
        'icon.svg'
      )
    ).toBeNull();
  });

  it('prices a plugin trial as an upgrade from its licensed edition', () => {
    expect(pluginTrialRow(plugin, makeInfo({edition: 'pro'}))).toMatchObject({
      item: {type: 'plugin-edition', plugin: 'seo', edition: 'pro'},
      price: 99,
      upgradePrice: 50,
    });
  });

  it('leaves out trials that are already in the cart', () => {
    const row = pluginTrialRow(
      plugin,
      makeInfo({edition: 'pro', licensedEdition: null})
    )!;
    const cart = {lineItems: [makeLineItem()]} as Cart;

    expect(pendingTrialRows([row], cart)).toEqual([]);
    expect(pendingTrialRows([row], null)).toEqual([row]);
  });
});
