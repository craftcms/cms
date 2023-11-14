# Release notes for Craft CMS 4.6 (WIP)

- Added the “Country” field type. ([#13789](https://github.com/craftcms/cms/discussions/13789))
- Added partial support for field types storing data in JSON columns (excluding MariaDB). ([#13916](https://github.com/craftcms/cms/issues/13916))
- “Updating search indexes” jobs are no longer queued when saving elements with change tracking enabled, if there’s no record of any fields or attributes of being dirty. ([#13917](https://github.com/craftcms/cms/issues/13917))
