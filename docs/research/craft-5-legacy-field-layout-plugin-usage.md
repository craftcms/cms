# Craft 5 legacy field-layout plugin usage

Research date: 2026-08-03

## Questions

1. How do public Craft 5 plugins use `FieldLayout::EVENT_CREATE_FORM` and `CreateFieldLayoutFormEvent`?
2. Do public Craft 5 `FieldLayoutElement::formHtml()` implementations emit named inputs, and do those inputs share one submission root?
3. Do sampled legacy form fragments depend on normal form submission of `<input type="file">` values?

## Conclusions

- Supporting `CreateFieldLayoutFormEvent::$tabs` and `::$static` covers all observed current Craft 5 plugin usage and Craft's documented extension point. No current public Craft 5 handler was found mutating `$event->form->tabIdPrefix`, `$event->form->errorKeyPrefix`, or `$event->form->tabs`.
- A single-root rule for a legacy field-layout element is **not compatible with observed Craft 5 plugins**. Navigation, Mux, Campaign, and Courier all provide current counterexamples with multiple unrelated top-level input names in one `formHtml()` result.
- The sample also contains pathless display elements and a one-root element. The bridge therefore needs to handle zero, one, and multiple roots if its goal is broad Craft 5 compatibility.
- No sampled PHP/Twig-rendered field, settings, or field-layout fragment depended on submitting a selected file with the enclosing form. Mux renders a real file input, but consumes it through an asynchronous upload, clears the selection, and stores the uploaded object in a normal hidden input. Direct multipart file submission remains an unproven compatibility case rather than something ordinary JSON serialization can preserve.

## `EVENT_CREATE_FORM`

Exact public-code searches found two current Craft 5 plugin handlers:

- Workflow 3.0.16 sets `$event->static = true` when a workflow submission locks the element. It does not mutate the form or tabs ([handler](https://github.com/verbb/workflow/blob/b8af40633c15772f41fdda2fe0bffecf69f114e6/src/services/Service.php#L196-L230), [Craft 5 constraint](https://github.com/verbb/workflow/blob/b8af40633c15772f41fdda2fe0bffecf69f114e6/composer.json#L27-L31)).
- Vizy 3.2.3 sets `$event->static = false` for nested Vizy block/Matrix-anchor elements. It does not mutate the form or tabs ([handler](https://github.com/verbb/vizy/blob/be086089dd03eb0377107b072c9c17ba0bed2c0f/src/Vizy.php#L121-L143), [Craft 5 constraint](https://github.com/verbb/vizy/blob/be086089dd03eb0377107b072c9c17ba0bed2c0f/composer.json#L29-L34)).

`$event->tabs` must still be supported even though no current public Craft 5 plugin hit was found. Craft 5 documents appending a `FieldLayoutTab` to it as the event's intended extension mechanism ([official example](https://github.com/craftcms/cms/blob/9e0d6cb26b9acdc27ea1bebef60834ae3d774595/src/models/FieldLayout.php#L133-L171)). Historical plugin code uses the same mechanism ([Recurring Orders example](https://github.com/TopShelfCraft/Commerce-Recurring-Orders/blob/cbb1afeff1ca827a1311cc8337443c8cf5cd7542/src/web/cp/FieldLayoutBehavior.php#L23-L30), [tab append](https://github.com/TopShelfCraft/Commerce-Recurring-Orders/blob/cbb1afeff1ca827a1311cc8337443c8cf5cd7542/src/web/cp/FieldLayoutBehavior.php#L107-L117)).

Craft 5 exposes the mutable `form`, `static`, and `tabs` properties on the event ([event class](https://github.com/craftcms/cms/blob/9e0d6cb26b9acdc27ea1bebef60834ae3d774595/src/events/CreateFieldLayoutFormEvent.php#L21-L41)). After dispatch, however, `createForm()` explicitly copies back only `tabs` and `static` before compiling the form ([core flow](https://github.com/craftcms/cms/blob/9e0d6cb26b9acdc27ea1bebef60834ae3d774595/src/models/FieldLayout.php#L1498-L1512)). Direct mutation of `form->tabIdPrefix`, `form->errorKeyPrefix`, or its initially empty `tabs` array can affect the old renderer, but no current public plugin doing so was found.

Verdict: retain `tabs` and `static`; a targeted incompatibility error for changed renderer bookkeeping or prepopulated rendered tabs is consistent with observed public usage. Private and unindexed plugins remain the risk.

## Named inputs from `formHtml()`

The sample used current default-branch code whose `composer.json` explicitly requires Craft 5.

| Plugin | Observed `formHtml()` result | Root shape |
| --- | --- | --- |
| Navigation | `linkedElementSiteId` plus `linkedElementId` ([source](https://github.com/verbb/navigation/blob/de130d2c4433c93b1f0cfb7c8408ba06a54d1d1a/src/fieldlayoutelements/NodeTypeElements.php#L45-L75), [Craft 5 constraint](https://github.com/verbb/navigation/blob/de130d2c4433c93b1f0cfb7c8408ba06a54d1d1a/composer.json#L29-L32)) | Multiple unrelated roots |
| Mux | `elementType`, `asset_id`, `meta[...]`, and the rendered title field ([template](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/src/templates/_includes/fields.twig#L10-L36), [Craft 5 constraint](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/composer.json#L15-L20)) | Multiple unrelated roots |
| Campaign | A sendout element emits `campaignIds`, `subject`, `fromNameEmail`, and recipient-related names from one template ([representative fields](https://github.com/putyourlightson/craft-campaign/blob/13ccd1529b44666282454d4df7d8b02c2d16a1b9/src/templates/sendouts/_includes/fields.twig#L5-L68), [additional recipient roots](https://github.com/putyourlightson/craft-campaign/blob/13ccd1529b44666282454d4df7d8b02c2d16a1b9/src/templates/sendouts/_includes/fields.twig#L80-L167), [Craft 5 constraint](https://github.com/putyourlightson/craft-campaign/blob/13ccd1529b44666282454d4df7d8b02c2d16a1b9/composer.json#L18-L24)) | Multiple unrelated roots |
| Courier | `triggerMode`, an `eventTrigger` field, and condition/date roots are produced by one element ([source](https://github.com/yellowrobotstudios/craft-courier/blob/ce8656ed5c36b9d672fc5d03e9c2422f670cf50f/src/fieldlayoutelements/ConditionsField.php#L51-L92), [Craft 5 constraint](https://github.com/yellowrobotstudios/craft-courier/blob/ce8656ed5c36b9d672fc5d03e9c2422f670cf50f/composer.json#L27-L31)) | Multiple unrelated roots |
| Guide | Its UI element contains one named `guideId` select ([template](https://github.com/wbrowar/craft-guide/blob/c43f7be1a6743442dfe524a3d32dd22d13c78e39/src/templates/fieldlayoutelements/guide_display_body.twig#L46-L64), [Craft 5 constraint](https://github.com/wbrowar/craft-guide/blob/c43f7be1a6743442dfe524a3d32dd22d13c78e39/composer.json#L28-L32)) | One root |
| Neo | The child-block UI element emits only a placeholder `<div>` ([source](https://github.com/spicywebau/craft-neo/blob/b872c0a25f13d8322c4758a2b66e8fa37ce5bbc9/src/fieldlayoutelements/ChildBlocksUiElement.php#L37-L49), [Craft 5 constraint](https://github.com/spicywebau/craft-neo/blob/b872c0a25f13d8322c4758a2b66e8fa37ce5bbc9/composer.json#L26-L30)) | No named inputs |
| Hyper | The embed-preview element emits display HTML only ([source](https://github.com/verbb/hyper/blob/859090ecf076bee1edbfe73718f6437e8c029e9e/src/fieldlayoutelements/EmbedPreview.php#L17-L26), [Craft 5 constraint](https://github.com/verbb/hyper/blob/859090ecf076bee1edbfe73718f6437e8c029e9e/composer.json#L27-L33)) | No named inputs |
| Membership | The element renders a log table with no form inputs ([element](https://github.com/oof-bar/craft-membership/blob/039e33568e83d1b3890458670b6ef18acc75dc6b/src/fieldlayoutelements/MembershipLogs.php#L45-L51), [template](https://github.com/oof-bar/craft-membership/blob/039e33568e83d1b3890458670b6ef18acc75dc6b/src/templates/_fieldlayoutelements/logs.twig#L1-L43), [Craft 5 constraint](https://github.com/oof-bar/craft-membership/blob/039e33568e83d1b3890458670b6ef18acc75dc6b/composer.json#L24-L28)) | No named inputs |

Craft 5 namespaces each layout element's complete HTML after `formHtml()` returns; it does not require the element itself to provide a single root ([core render path](https://github.com/craftcms/cms/blob/9e0d6cb26b9acdc27ea1bebef60834ae3d774595/src/models/FieldLayout.php#L1514-L1567)). That explains why multi-root plugin elements are valid today.

Verdict: failing on multiple roots would knowingly exclude active Craft 5 plugins. If core must see one ordinary Control value, the adapter needs to aggregate the island's complete relative name/value map behind that Control and restore those names at the legacy shim boundary; the old plugin cannot be assumed to have supplied one common root.

## File inputs

Across the sampled Craft 5 repositories, no PHP/Twig legacy field, settings, or field-layout fragment was found that relied on the enclosing form to submit file bytes.

Mux is the useful edge case. Its custom field inserts a genuine named file input ([file input](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/src/web/src/vue/FileInput.vue#L17-L25)), but its change handler validates/emits the selected files and clears the input ([selection handling](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/src/web/src/vue/FileInput.vue#L105-L123)). The parent component uploads the file asynchronously in chunks ([async upload](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/src/web/src/vue/MuxAsset.vue#L145-L173)) and represents the finished upload through a hidden input containing JSON ([hidden value](https://github.com/rocketpark/craft-mux/blob/265650c476093892201ad5da1cba2c17f244bb91/src/web/src/vue/MuxAsset.vue#L28-L32)).

Verdict: normal DOM serialization covers the observed persisted values. A still-selected `File` cannot be represented as a JSON-safe scalar/list/object, so the bridge should fail explicitly if it encounters a non-empty file input rather than silently dropping it. Multipart or pre-upload transport should be added only if a real supported plugin requires it.

## Method and limitations

The investigation used GitHub code search for exact event/class/method names, then inspected cloned public repositories at their current default-branch commits. Craft 5 compatibility was checked from each repository's `composer.json`. The field-layout sample covers eight repositories and includes display-only, single-root, and multi-root elements; it is representative, not exhaustive.

GitHub search covers indexed public source, principally default branches. It cannot reveal private or closed-source plugins, unindexed code, dynamically assembled event names, maintained non-default branches missed by the query, or runtime-generated HTML whose names do not appear statically. Search API rate limiting also prevented an exhaustive census. Historical Craft 3 code is cited only to confirm the long-standing documented `tabs` mechanism, not as evidence of current Craft 5 usage.
