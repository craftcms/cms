import {describe, expect, it} from 'vite-plus/test';
import {
  resolveEditionActions,
  type EditionActionsInput,
} from './editionActions';
import type {
  Plugin,
  PluginEdition,
  PluginLicenseInfo,
  PluginStoreContext,
} from './types';

const lite: PluginEdition = {
  id: 1,
  name: 'Lite',
  handle: 'lite',
  price: '49',
  renewalPrice: '19',
  features: null,
};

const pro: PluginEdition = {
  id: 2,
  name: 'Pro',
  handle: 'pro',
  price: '99',
  renewalPrice: '39',
  features: null,
};

const free: PluginEdition = {...lite, handle: 'standard', price: null};

function makePlugin(overrides: Partial<Plugin> = {}): Plugin {
  return {
    handle: 'seo',
    version: '2.0.0',
    latestCompatibleVersion: '2.0.0',
    phpVersionCompatible: true,
    incompatiblePhpVersion: null,
    phpConstraint: '^8.2',
    minCmsEdition: null,
    abandoned: false,
    editions: [lite, pro],
    ...overrides,
  } as Plugin;
}

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

const context: PluginStoreContext = {
  edition: 'solo',
  licensedEdition: 'solo',
  editions: ['solo', 'team', 'pro', 'enterprise'],
  canTestEditions: true,
  phpVersion: '8.4.0',
  composerPhpVersion: '8.2.0',
  allowUpdates: true,
  cmsLicenseKey: null,
  craftIdEndpoint: 'https://console.craftcms.com',
};

function resolve(overrides: Partial<EditionActionsInput>) {
  return resolveEditionActions({
    plugin: makePlugin(),
    edition: lite,
    licenseInfo: null,
    context,
    inCart: false,
    ...overrides,
  });
}

describe('resolveEditionActions', () => {
  it.each([
    {
      name: 'a free edition of a plugin that isn’t installed can be installed',
      input: {plugin: makePlugin({editions: [free]}), edition: free},
      purchase: null,
      install: {
        kind: 'install',
        method: 'install',
        primary: true,
        disabled: false,
      },
    },
    {
      name: 'a commercial edition can be bought or tried',
      input: {},
      purchase: {kind: 'add-to-cart', disabled: false},
      install: {
        kind: 'try',
        method: 'install',
        primary: false,
        disabled: false,
      },
    },
    {
      name: 'the licensed edition that’s installed is just installed',
      input: {licenseInfo: makeInfo()},
      purchase: {kind: 'licensed'},
      install: {kind: 'installed'},
    },
    {
      name: 'an edition running without its license is a trial',
      input: {licenseInfo: makeInfo({licensedEdition: null, isTrial: true})},
      purchase: {kind: 'add-to-cart', disabled: false},
      install: {kind: 'installed-trial'},
    },
    {
      name: 'a pricier edition of an installed plugin is tried by switching',
      input: {licenseInfo: makeInfo(), edition: pro},
      purchase: {kind: 'add-to-cart', disabled: false},
      install: {kind: 'try', method: 'switch', primary: false, disabled: false},
    },
    {
      name: 'moving back to the licensed edition reactivates it',
      input: {licenseInfo: makeInfo({edition: 'pro'})},
      purchase: {kind: 'licensed'},
      install: {
        kind: 'reactivate',
        method: 'switch',
        primary: false,
        disabled: false,
      },
    },
    {
      name: 'a cheaper edition than the licensed one can’t be bought',
      input: {licenseInfo: makeInfo({edition: 'pro', licensedEdition: 'pro'})},
      purchase: null,
      install: null,
    },
    {
      name: 'a mismatched license can’t be extended',
      input: {
        licenseInfo: makeInfo({licenseKeyStatus: 'mismatched'}),
        edition: pro,
      },
      purchase: {kind: 'add-to-cart', disabled: true},
      install: {kind: 'try', method: 'switch', primary: false, disabled: false},
    },
    {
      name: 'a disabled plugin can’t be tried in another edition',
      input: {licenseInfo: makeInfo({isEnabled: false}), edition: pro},
      purchase: {kind: 'add-to-cart', disabled: false},
      install: {kind: 'try', method: 'switch', primary: false, disabled: true},
    },
    {
      name: 'nothing is offered when updates are disallowed and it isn’t installed',
      input: {context: {...context, allowUpdates: false}},
      purchase: null,
      install: null,
    },
  ])('$name', ({input, purchase, install}) => {
    const actions = resolve(input as Partial<EditionActionsInput>);

    expect(actions.purchase).toEqual(purchase);
    expect(actions.install).toEqual(install);
  });

  it.each([
    {
      name: 'only an older version is compatible',
      plugin: {latestCompatibleVersion: '1.5.0'},
      notice: {kind: 'partially-compatible', version: '1.5.0'},
    },
    {
      name: 'no version is compatible',
      plugin: {latestCompatibleVersion: null},
      notice: {kind: 'incompatible'},
    },
    {
      name: 'the environment’s PHP is too old',
      plugin: {phpVersionCompatible: false, incompatiblePhpVersion: 'php'},
      notice: {kind: 'php', required: '^8.2', current: '8.4.0'},
    },
    {
      name: 'composer.json pins an older PHP',
      plugin: {phpVersionCompatible: false, incompatiblePhpVersion: 'composer'},
      notice: {kind: 'composer-php', required: '^8.2', current: '8.2.0'},
    },
    {
      name: 'it needs a higher Craft edition',
      plugin: {minCmsEdition: 'pro'},
      notice: {kind: 'cms-edition', edition: 'pro'},
    },
    {
      name: 'it is abandoned',
      plugin: {abandoned: true},
      notice: {kind: 'abandoned'},
    },
  ] as const)(
    'explains why it can’t be installed when $name',
    ({plugin, notice}) => {
      const actions = resolve({plugin: makePlugin(plugin as Partial<Plugin>)});

      expect(actions.notice).toEqual(notice);
    }
  );

  it('blocks buying and trying a plugin this install can’t run', () => {
    const actions = resolve({plugin: makePlugin({minCmsEdition: 'pro'})});

    expect(actions.purchase).toEqual({kind: 'add-to-cart', disabled: true});
    expect(actions.install).toMatchObject({kind: 'try', disabled: true});
  });
});
