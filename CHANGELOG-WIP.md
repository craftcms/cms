# Release Notes for Craft CMS 5.13 (WIP)

### Addresses

- United Kingdom administrative areas (counties) now come from `commerceguys/addressing`, and are stored by their ISO codes rather than their names. Existing UK addresses are migrated automatically. ([#19488](https://github.com/craftcms/cms/pull/19488))
- `craft\services\Addresses::getUsedFields()` and `getUsedSubdivisionFields()` now remove duplicate fields added by event handlers. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Dependencies

- Updated `commerceguys/addressing` to v2.3. ([#19488](https://github.com/craftcms/cms/pull/19488))
