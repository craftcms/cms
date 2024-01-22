# Release Notes for Craft CMS 4.7 (WIP)

### Content Management
- Admin tables now have sticky footers. ([#14149](https://github.com/craftcms/cms/pull/14149))

### Administration
- Added “Save and continue editing” actions to all core settings pages with full-page forms. ([#14168](https://github.com/craftcms/cms/discussions/14168))
- Added the `utils/prune-orphaned-matrix-blocks` command. ([#14154](https://github.com/craftcms/cms/pull/14154))

### Extensibility
- Added `craft\base\ElementInterface::beforeDeleteForSite()`.
- Added `craft\base\ElementInterface::afterDeleteForSite()`.
- Added `craft\base\FieldInterface::beforeElementDeleteForSite()`.
- Added `craft\base\FieldInterface::afterElementDeleteForSite()`.

### System
- Reduced the system font file size, and prevented the flash of unstyled type for it. ([#13879](https://github.com/craftcms/cms/pull/13879))
- Log message timestamps are now set to the system time zone. ([#13341](https://github.com/craftcms/cms/issues/13341))
- Fixed a bug where deleting an entry for a site wasn’t propagating to Matrix blocks for that entry/site. ([#13948](https://github.com/craftcms/cms/issues/13948))
