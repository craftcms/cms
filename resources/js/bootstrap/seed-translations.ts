import {setTranslations} from '@craftcms/ui/utilities/translate';

// Imported first by each control panel entry. Custom elements already on the
// page upgrade, and translate their labels, as soon as their modules define
// them, which is before the CP bootstrap loads its config.
setTranslations(
  (window as {Craft?: {translations?: Parameters<typeof setTranslations>[0]}})
    .Craft?.translations ?? {}
);
