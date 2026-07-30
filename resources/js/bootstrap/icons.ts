import {createUrlIconResolver, getIconUrl, setIconResolver} from '@craftcms/ui';

/**
 * Points the icon resolver at the CP's published icon directory.
 *
 * Name resolution itself — Craft's pre-6 icon aliases and the icons that only
 * ship under `custom-icons` — lives in `getIconUrl()`, so it applies to the
 * default resolver too. That matters because the default is installed when
 * `@craftcms/ui` loads, whereas this runs from the CP's ES-module bootstrap,
 * by which point the legacy classic scripts have already rendered their first
 * icons.
 */
export function configureIcons(baseUrl: string = '/vendor/craft/icons') {
  setIconResolver(
    createUrlIconResolver((name, family, variant) =>
      getIconUrl(name, family, variant, baseUrl)
    )
  );
}
