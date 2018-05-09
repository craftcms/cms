# `{% requirePermission %}`

This tag will ensure that the current user is logged in with an account that has a given permission.

```twig
{% requirePermission "spendTheNight" %}

<h1>Slumber Party</h1>
```

The user can have the permission either directly or through one of their user groups. If they don’t have it, a 403 (Forbidden) error will be served.

### Available Permissions

The permissions Craft comes with are:

Permission | Handle
-|-
Access the site when the system is off | `accessSiteWhenSystemIsOff`
Access the CP | `accessCp`
↳  Access the CP when the system is off | `accessCpWhenSystemIsOff`
↳  Perform Craft and plugin updates | `performUpdates`
↳  Access _[Plugin Name]_ | `accessPlugin-[PluginHandle]`
Edit users | `editUsers`
↳  Register users | `registerUsers`
↳  Assign permissions | `assignUserPermissions`
↳  Administrate users | `administrateUsers`
Delete users | `deleteUsers`
Edit _[Locale Name]_ | `editLocale:[LocaleID]`
Edit entries | `editEntries:[SectionID]`
↳  Create entries | `createEntries:[SectionID]`
↳  Publish entries | `publishEntries:[SectionID]`
↳  Delete entries | `deleteEntries:[SectionID]`
↳  Edit other authors’ entries | `editPeerEntries:[SectionID]`
      ↳  Publish other authors’ entries | `publishPeerEntries:[SectionID]`
      ↳  Delete other authors’ entries | `deletePeerEntries:[SectionID]`
↳  Edit other authors’ drafts | `editPeerEntryDrafts:[SectionID]`
      ↳  Publish other authors’ drafts | `publishPeerEntryDrafts:[SectionID]`
      ↳  Delete other authors’ drafts | `deletePeerEntryDrafts:[SectionID]`
Edit _[Global Set Name]_ | `editGlobalSet:[GlobalSetID]`
Edit _[Category Group Name]_ | `editCategories:[CategoryGroupID]`
View _[Asset Source Name]_ | `viewAssetSource:[SourceID]`
↳  Upload files | `uploadToAssetSource:[SourceID]`
↳  Create subfolders | `createSubfoldersInAssetSource:[SourceID]`
↳  Remove files | `removeFromAssetSource:[SourceID]`
