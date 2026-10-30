# Release Notes for Craft CMS 6

## Unreleased

> [!IMPORTANT]
> This update contains breaking changes for plugins. See [#19882](https://github.com/craftcms/cms/pull/19882) for details.

> [!WARNING]
> Plugins should pass dates they write or compare with the query builder through `CraftCms\Cms\Support\Query::prepareDateForDb()`, so they’re stored in UTC like Craft’s own. Laravel stores a bound date in the system time zone. Eloquent models that extend `CraftCms\Cms\Shared\BaseModel` are converted automatically. See [#20053](https://github.com/craftcms/cms/pull/20053) for details.

### Control Panel UI

- Added a command palette, opened from the search button in the global header, with <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>K</kbd>, or with <kbd>/</kbd> when not typing in a field. It finds fields, tabs, and Matrix blocks on the current page, control panel pages and settings, entries, assets, users, and releases, and runs actions such as creating entries and settings, publishing releases, clearing caches, and changing preferences, with the same permission checks as the screens they stand in for. It also searches the Craft CMS documentation, and lists recently visited pages when nothing has been typed. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Live Preview is back. The “Preview” button on element edit pages and in element editor slideouts shows the element’s page beside its editor, reloading as content changes, at desktop, tablet, or phone size. Tablets and phones can be rotated. ([#19964](https://github.com/craftcms/cms/pull/19964))
- Element cards can now be cover cards, where the thumbnail image fills the card, with the header and content laid over it on a blurred bar, backing, or scrim that keeps them legible over any image: white on black, or black on white over light images. Cover cards’ attribute previews are limited to one line. Card view designers have a new “Thumb Style” setting, with “Normal” and “Cover” options, which defaults to “Cover” for asset volumes. Other element types’ cards can be covered too, when their thumbnail is an asset’s image, such as a user’s photo, or an image from the first of their “Thumb Sources” that has a thumbnail, including fields within Content Blocks and related elements’ own thumbnails. Only images from assets can cover a card, and images smaller than 480×360, narrower than 3:4, wider than 2:1, or possibly transparent keep the normal card. ([#20148](https://github.com/craftcms/cms/pull/20148), [#20152](https://github.com/craftcms/cms/pull/20152), [#20181](https://github.com/craftcms/cms/pull/20181))
- Card view designers’ “Thumbnail Source” setting is now “Thumb Sources”, where several fields can be chosen and ordered. Chips and cards show the thumbnail from the first one that has one. ([#20156](https://github.com/craftcms/cms/discussions/20156), [#20181](https://github.com/craftcms/cms/pull/20181))
- Element cards whose header has no label, such as asset cards, now show just their checkbox and action button over the card, without a header bar, and cards whose header has nothing in it no longer show one at all. Entry cards keep their header bar, with their entry type’s label and icon. ([#20148](https://github.com/craftcms/cms/pull/20148))
- HUD tips now match their HUD’s colors. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added a “Theme” user preference, with “Light”, “Dark”, and “System” options. “System” is the default. Pages shown to logged-out users, such as the login page, always follow the system’s light or dark setting. ([#19966](https://github.com/craftcms/cms/pull/19966))
- The control panel now warns users a couple of minutes before their session expires, with options to stay signed in or sign out, and lets them sign back in without leaving the page once it has. ([#20086](https://github.com/craftcms/cms/pull/20086))
- Improved chip styling. ([#19902](https://github.com/craftcms/cms/pull/19902))
- Modals now have a subtle outline instead of a border, header and footer bars that match the control panel’s, and rounder corners. Dialogs, such as confirmations and copy prompts, have a compact header with a plain close button. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Asset previews, file conflict prompts, the entry index’s “Move to” action, and the modals for reassigning or replacing relations before a delete now use the control panel’s dialog styling. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Text file previews now have “Copy” and “Close” buttons. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Text files’ previews on asset edit pages now show a close-up of the top of the file, rather than its icon. ([#20094](https://github.com/craftcms/cms/pull/20094))
- Improved the Customize Sources modal: only its three panels scroll, each under a fixed header, and page and source items have tighter, aligned drag handles and action menus. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Element selection modals now have a header-height toolbar, table headings that match the Customize Sources modal’s, and a world icon on the site menu. ([#20085](https://github.com/craftcms/cms/pull/20085))
- World and earth icons now show the globe for the system time zone. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Copy prompts now show their value in a read-only input with a copy button, like Craft 5. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Standalone inputs’ instructions and tips now have the same spacing and styling as other fields. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Selected cards’ checkboxes now stand out against their headers. ([#20085](https://github.com/craftcms/cms/pull/20085))
- File and folder icons have been redesigned: files look like a sheet of paper, and folders are light blue, each with corners that match nearby image thumbnails and a soft shadow at any size. Files have a 1px outline. The dark theme gets a black sheet and dark blue folders, both outlined. Asset previews show them on the same gradient images get, rather than stretched to the preview area’s shape. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The icon picker’s icons are now 32px squares, evenly spaced. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Secondary navigation items are now as wide as the global navigation’s. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Element revision listings now use the control panel’s standard table, with a choice of page sizes. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The Queue Manager utility now lists jobs a page at a time, with a choice of page sizes. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Queue job pages are now laid out like other detail pages, titled after the job, with a breadcrumb back to the Queue Manager and their Retry and Release buttons in the footer. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The MCP server page’s setup snippets are now syntax-highlighted. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The System Report, PHP Info, and Migrations utilities’ tables now line up with their headings. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Dates with times, such as element creation and update dates, revision dates, release dates, and queue job dates, now show the time zone’s abbreviation, like Craft 5. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Dates are now shown in the calendar of the user’s formatting locale, such as the Persian calendar for Persian or the Buddhist calendar for Thai. Named and ICU date formats in Twig, such as `|date('short')`, follow the site locale’s calendar too. ([#8741](https://github.com/craftcms/cms/discussions/8741), [#20085](https://github.com/craftcms/cms/pull/20085))
- Links are no longer underlined until they’re hovered, unless the “Underline links” accessibility preference is enabled, in which case they’re underlined until hovered. ([#19905](https://github.com/craftcms/cms/pull/19905))
- Folder names in asset indexes are now links.
- The “License purchase required” screen is now shown in a centered pane, like other message pages.
- Field labels and headings throughout the control panel now consistently use title case.
- Element index tables and padded admin tables now span the page’s inline padding, with their outer cells padded to match. ([#19906](https://github.com/craftcms/cms/pull/19906), [#19936](https://github.com/craftcms/cms/pull/19936))
- Link fields now show their Link Type and destination inputs side by side, with their other settings nested under an “Advanced” section, within an outlined pane. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Asset folders’ labels are now links in thumbnail and card views. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Element index view mode buttons now have tooltips. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Element index View menus now have a “Thumb Size” setting in table, structure, and thumbnail views, with “Small”, “Medium”, and “Large” options, plus “XL” and “2XL” in thumbnail view. Each view remembers its own size, and chips take a size to suit their thumbnails. Changing it resizes the results in place, scaling their current thumbnails until sharper ones load. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Element indexes now show placeholders while their results load, rather than a spinner. Placeholders match the view mode and thumbnail size the results will be shown at, and the source’s card thumbnail style and alignment, so results no longer show at the default thumbnail size before switching to the chosen one. ([#20199](https://github.com/craftcms/cms/pull/20199), [#20142](https://github.com/craftcms/cms/issues/20142))
- Thumbnail views in element indexes and relational fields now show each element as a chip, with its thumbnail above its label. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Element index View menus now only show “Table Columns” in table and structure views. ([#20124](https://github.com/craftcms/cms/pull/20124))
- Element index View menus now have a “Use defaults” button, like Craft 5, which shows when the sort, table columns, or thumb size differ from the source’s defaults and restores them. ([#20205](https://github.com/craftcms/cms/issues/20205), [#20223](https://github.com/craftcms/cms/pull/20223))
- Popovers, such as element index View menus, now grow into the room below or above them, rather than stopping at 40% of the window’s height. ([#20124](https://github.com/craftcms/cms/pull/20124))
- The control panel’s navigation and details panes now have thin scrollbars, and scrolling past their ends no longer scrolls the page. ([#20011](https://github.com/craftcms/cms/issues/20011))
- Empty states’ messages and icons are now lighter. ([#19942](https://github.com/craftcms/cms/pull/19942))
- User preference screens now have “General”, “Accessibility”, and “Development” tabs. ([#19942](https://github.com/craftcms/cms/pull/19942))
- The Notification Position and Slideout Position preferences’ buttons now have tooltips. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Buttons in button groups no longer have gaps between them, and adjacent buttons now share a border. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Outline and dashed buttons now have the same border color as text inputs and checkboxes. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Selected buttons in single-choice button groups, such as element index view modes, no longer show hover effects or pointer cursors. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Element selection modals no longer have a header, and their toolbars now fit on one row. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Expand/collapse toggles’ chevrons now follow their labels, and point toward the end of the line while collapsed and down while expanded. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Image thumbnails’ placeholders now cover the same area as the image, and thumbnails no longer shift once their image loads. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Asset previews’ background gradients are now more transparent, and asset edit pages now have a hairline below the preview. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Edit pages for elements without statuses, such as assets, no longer show a status badge or “Status” metadata, except for drafts. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Element edit pages’ “View” button is now on the right side of the toolbar, beside the action menu.
- Editable tables’ text and select inputs now fill their cells, and clicking anywhere in a cell focuses its control. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Editable tables’ headers now have a background color, and their checkbox, lightswitch, color, and icon cells are more compact. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Checkboxes and radio buttons now look the same in every browser, and their focus rings follow their rounded shape. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Text inputs and selects now take on their container’s background color. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Disabled lightswitches are now dimmed. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Chips’ handles are now shown as plain text below their labels. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Component selects no longer show a reorder button when only one item is selected. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Relational fields’ chips and cards now have reorder buttons with menu options for moving them, including on touch devices. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Move icons on Matrix blocks, chips, and editable table rows are now larger. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Action menu buttons’ ellipsis icons are now larger, and chips’ action menu and reorder buttons are now closer together. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Nav items’ icons are now closer to their labels, and expanded nav sections now have a hairline in line with the selected item’s indicator. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Metadata values in details panes are now left-aligned. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Collapsible sections’ hairlines now start at the section’s edge. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Date and time inputs now share a single border, with a divider between them, and their picker icons now come before their values.
- Date and time fields’ clear buttons are now shown within the input’s border, and are hidden when there’s nothing to clear, keeping their room so the inputs don’t change width. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Text inputs’ suffix buttons now fill their segment of the input.
- Image transform mode and view mode illustrations now have transparent backgrounds, and follow the control panel’s light or dark theme.
- Slideouts can now be resized horizontally, and remember their width. ([#19978](https://github.com/craftcms/cms/pull/19978))
- Slideouts’ details panes that were folded away for lack of room now reopen once the slideout is wide enough again. ([#19978](https://github.com/craftcms/cms/pull/19978))
- Slideouts’ details panes now open on the panel that was open in the last slideout, or start out folded if it was folded. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Links within colored badges, chips, and cards now use the badge’s, chip’s, or card’s text color, and are underlined until hovered. ([#19989](https://github.com/craftcms/cms/pull/19989))
- The image editor’s header, tabs, controls, and footer now share the control panel’s standard container padding.
- The global nav now groups Entries, Assets under a “Content” heading, and Users, GraphQL, Utilities, Settings, and Plugin Store under an “Administration” heading. ([#19903](https://github.com/craftcms/cms/pull/19903))
- Element sources’ `status` and `badgeCount` values are now shown in the secondary nav, as they were in the Craft 5 sidebar. ([#19892](https://github.com/craftcms/cms/pull/19892))
- Tabs now mirror the selected tab in the URL hash, select the tab named by the hash on page load, and keep it after saving.
- Activity timeline day headings now show the date as a badge, and the Workflow details panel’s review panes are no longer outlined. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Selectize fields are now rendered as `<craft-combobox>` web components, rather than Selectize instances.
- Autosuggest inputs are now rendered as `<craft-combobox>` web components, rather than Vue 2 `vue-autosuggest` instances.
- Autosuggest inputs now match suggestions on their hint as well as their label, and no longer cap each suggestion group at five items.
- Improved control panel styling for medium-sized viewports.
- Search inputs throughout the control panel now have a search icon and a clear button, and pressing <kbd>Esc</kbd> clears them. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Medium-sized buttons and inputs, nav items, and header bars are now 32px tall, rather than 34px, and small and medium chips are now 32px tall as well, unless they’re plain. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Native select menus now have the same chevron and height as other select menus. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Slug fields’ refresh buttons are now drawn within the input’s border. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Date and time inputs’ divider is now softer and spans the full width when they’re stacked, and their clear button stays aligned with the time input. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Keys in instructions and tips, such as “Type <kbd>$</kbd> to choose an environment variable”, are now shown as keycaps. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Instructions for typing a key, such as in section settings’ URI formats and the System Messages utility, now all read “Type <kbd>…</kbd> to choose…”.
- Info icons’ tooltips now close when something outside them is clicked.
- Keyboard shortcuts in menus and tooltips, such as the Save menu’s and the Search button’s, now show each key as a keycap of its own. ([#20219](https://github.com/craftcms/cms/pull/20219))
- Fields’ tip and warning icons are no longer padded. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Grouped inputs’ sub-labels, such as Field Limit’s “Maximum” and “Unit”, are now smaller and lighter. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Colored chips’ fills are now subtler, matching colored cards’ bodies, and selected colored chips now use their own color rather than the accent color. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Checkboxes in colored chips and card headers now take the chip’s or header’s text color. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Neutral buttons on colored cards, such as the “+5” button after a card’s related elements, now take the card’s color rather than gray. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Relational fields’ chips now take their element’s color, as element index chips do. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card thumbnails are no longer cropped to squares, as in Craft 5, and now sit closer to the card’s edges. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card attribute previews are now capped at two lines, unless they opt out with the `no-truncate` class, as in Craft 5. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Sortable checkbox selects, such as “Card Attributes”, “Table Columns”, and “Default Table Columns”, now list only the selected options, with an “Add item” menu for the rest. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Entry types’ and fields’ “Used by” metadata now lists usages as chips. ([#20001](https://github.com/craftcms/cms/pull/20001))
- The global header is now lighter in the light theme. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Content within black cards, chips, and badges, such as buttons and badges, is now styled as in the dark theme.
- Links in gray chips, and in black chips, cards, and badges, now have the default link styling, rather than an underline in the chip’s or card’s text color.
- Card view designers now preview entries as live, and users with a sample user’s name, default avatar, and active status.
- Color selects’ swatches now match the headers of cards in their colors.
- Slide pickers, such as field layout designers’ width pickers, now have lighter segments, and the dividers between selected segments are no longer dark.
- Resizable modals, such as the Customize Sources modal, now have grips along their right and bottom edges, plus a smaller one in their corner, which show on hover. Only the corner grip takes keyboard focus. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Element selection modals can now be resized. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Controls, hairlines, and UI text within colored cards and Matrix blocks now take the card’s color rather than gray, except for white, gray, slate, and black, which also keep the default link styling in cards, chips, and badges. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Matrix blocks are now styled like cards in their color, including their borders, white blocks’ darker surface in the dark theme, and black blocks’ dark-themed contents. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Chips, cards, and Matrix blocks without a color no longer have a fill of their own, and take on the color of the card or Matrix block they’re within, including the UI within them. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Dashboard widgets are now gray cards. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Thumbnails’ headers are now padded like chips, and their action menu buttons no longer touch the thumbnail’s edge. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Editable tables’ single-line text cells now wrap their text, and their date and time cells’ picker icons now come before their values. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Editable tables’ cell contents now line up with their headers, and their row action buttons are closer together. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Color inputs’ `#` is now shown as the start of their value, and color cells in editable tables now act as a single text field, apart from their swatch. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Side-by-side fields now have a wider gap between them. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Combobox clear buttons no longer have a background color until they’re hovered. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Long tooltips now wrap at a readable width. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Large thumbnails in relational fields, such as Assets fields with the “Large Thumbnails” view mode, now have rounded corners, matching card thumbnails.
- “Paste” buttons now echo Craft 5’s, with a fuchsia duplicate icon and a fuchsia-to-indigo gradient border and tint.
- Details for the issues that block an element’s deletion now list the related elements as chips, rather than an embedded element index.
- Draft name labels in element chips now drop below the element’s title when they don’t fit beside it.
- The header’s notification button is now a plain icon button, matching the search button beside it. ([#20106](https://github.com/craftcms/cms/pull/20106))
- Plain links to control panel pages, such as those in elements’ metadata and other server-rendered HTML, now open with Inertia rather than reloading the page. ([#20118](https://github.com/craftcms/cms/pull/20118))
- Link and Email field values in element indexes and cards now stay on one line, cut off with an ellipsis, after a button that shows the full address with options to open or copy it. Hovering over the link shows the same details. ([#20123](https://github.com/craftcms/cms/pull/20123))
- Fixed a bug where legacy buttons and text inputs were unstyled, and `fullwidth` controls didn’t fill their containers, on Twig control panel pages rendered inside the Inertia shell.
- Fixed a bug where release dates were formatted for the control panel’s language rather than the user’s formatting locale. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Fixed a bug where the User Profile Fields and Address Fields settings pages had no Save button. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Fixed a bug where some status badges and dots had no color, including “Pending” jobs in the Queue Manager utility, disabled sites on the Sites settings page, and options in legacy select menus, such as the “Yes” option of `forms.booleanMenu()`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Fixed a bug where Language and Formatting Locale options’ hints were shown in the current language, rather than in each option’s own language.
- Fixed a bug where control panel pages without a title had extra space above their content. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where edit pages for elements without a title, such as nested entries whose entry type has no Title field, had a blank page title. They’re now titled “Untitled {type}”. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where element indexes kept the page count of the listing they first loaded, after switching sources or filtering. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where changing an element index’s view mode changed it for every source, rather than just the selected one. View modes are now remembered per source, so sources will show their default view mode until a different one is chosen for them. ([#20143](https://github.com/craftcms/cms/issues/20143), [#20200](https://github.com/craftcms/cms/pull/20200))
- Fixed a bug where element indexes resumed inline editing after the page was reloaded. ([#20200](https://github.com/craftcms/cms/pull/20200))
- Fixed a bug where choosing a new “Sort by” attribute in element index View menus could switch back to the previous one. ([#20202](https://github.com/craftcms/cms/issues/20202), [#20220](https://github.com/craftcms/cms/pull/20220))
- Fixed a bug where element index View menus’ sort direction buttons didn’t update until the results had loaded. ([#20203](https://github.com/craftcms/cms/issues/20203), [#20220](https://github.com/craftcms/cms/pull/20220))
- Fixed a bug where element indexes sorted by a custom field ignored the sort direction. ([#20204](https://github.com/craftcms/cms/issues/20204), [#20220](https://github.com/craftcms/cms/pull/20220))
- Fixed a bug where submit buttons created by `Craft.ui.createSubmitButton()` and `createSubmitButton()` from `@craftcms/ui` weren’t styled as primary buttons. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where tooltips within sticky footers could be positioned off-screen or cut off.
- Fixed a bug where draft name labels in element chips were cut off at the bottom.
- Fixed a bug where clicking on a relational field’s chip or card deselected the others. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where pending progress bars’ stripes were oversized and had a seam. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where selectable chips and thumbnails had a text cursor. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Fixed a bug where comboboxes’ menus could grow as wide as the window and extend off-screen when an option had a long label or hint, such as an environment variable suggestion.
- Fixed a bug where typing an option’s exact label or value into a combobox that accepts free text could make two options look selected. Exact matches are now listed first.
- Fixed a bug where rich selects’ menus could extend off-screen when an option had a long label or hint, and a long option could stretch the select itself past its container.
- Fixed a bug where required `CraftCms\Cms\Ui\Controls\Choice` selects displayed the first option while their value was empty. They now show a disabled prompt until an option is chosen. ([#20178](https://github.com/craftcms/cms/pull/20178))
- Fixed a bug where permission trees, such as user group permissions and the public MCP settings, used the browser’s native checkboxes instead of the control panel’s checkbox styling.
- Fixed a bug where logging in from the control panel’s login page could redirect to the front end instead of the control panel, when a front-end login path was also configured.
- Fixed a bug where element edit pages could have a few pixels of vertical scroll after the window was resized, even though their content fit within it. ([#20028](https://github.com/craftcms/cms/pull/20028))
- Fixed a bug where control panel pages and details panes shifted sideways when a scrollbar appeared. ([#20030](https://github.com/craftcms/cms/pull/20030))
- Fixed a bug where the control panel header didn’t reach the edge of the window on pages that don’t scroll, and details panes reserved an extra, empty scrollbar gutter. ([#20011](https://github.com/craftcms/cms/issues/20011))
- Fixed a bug where every source page listed all enabled sources when the first source, such as “All entries”, was disabled. ([#20016](https://github.com/craftcms/cms/issues/20016))
- Fixed a bug where thumbnails for animated image formats, such as WebP and GIF, were shown as a 1×1 dot. ([#20019](https://github.com/craftcms/cms/issues/20019))
- Fixed a bug where Plain Text fields with a character limit weren’t full width when line breaks were allowed. ([#20017](https://github.com/craftcms/cms/issues/20017))
- Fixed a bug where element index tables with only a title column split the rest of the row between the checkbox and reorder columns. ([#20021](https://github.com/craftcms/cms/issues/20021))
- Fixed a bug where fields that share a row in element edit forms had no gap between them. They’re now separated by a hairline, as in Craft 4 and 5. ([#20022](https://github.com/craftcms/cms/issues/20022))
- Fixed a bug where checking the “All” option in server-rendered checkbox selects didn't check the other options. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Fixed a bug where changes to which missing folders and files to delete were ignored when finishing an asset indexing session, and their names were shown escaped. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Fixed a bug where the login page’s “Stay signed in” checkbox wasn’t styled.
- Fixed a bug where text in form inputs could be clipped at the bottom with some fonts, such as Segoe UI on Windows. ([#19928](https://github.com/craftcms/cms/issues/19928))
- Fixed a bug where the “This entry has been updated” notice appeared after discarding changes, and its Reload button didn’t dismiss it. ([#20038](https://github.com/craftcms/cms/issues/20038))
- Fixed a bug where every custom field was marked as modified when saving an entry with unsaved changes failed validation. ([#20039](https://github.com/craftcms/cms/issues/20039))
- Fixed a bug where editing one field in a Matrix block marked every field in the Matrix field as modified. ([#20040](https://github.com/craftcms/cms/issues/20040))
- Fixed a bug where element edit pages titled their validation error summary “Could not save settings”.
- Fixed a bug where text inputs with a long character limit, such as Title fields, could stretch past their column after a failed save.
- Fixed a bug where white, gray, and slate nested entry blocks took some of their colors from a colored block they were nested in. ([#20041](https://github.com/craftcms/cms/issues/20041))
- Fixed a bug where outline, dashed, plain, and link buttons with a white or black color, such as Matrix fields’ “Add {type}” buttons for white or black entry types, could have unreadable text.
- Fixed a bug where control panel forms that failed validation didn’t show an error notification, such as “Couldn’t save entry.” ([#20044](https://github.com/craftcms/cms/issues/20044))
- Fixed a bug where element editors still said “Saved at …” after a failed save, and after discarding changes. ([#20044](https://github.com/craftcms/cms/issues/20044))
- Fixed a bug where element editors said the element had been updated after a failed save. ([#20044](https://github.com/craftcms/cms/issues/20044))
- Fixed a bug where element editors kept a failed save’s errors after discarding changes. ([#20044](https://github.com/craftcms/cms/issues/20044))
- Fixed a bug where element editors’ error summaries could disappear right after a failed save. ([#20044](https://github.com/craftcms/cms/issues/20044))
- Fixed a bug where double-clicking a Matrix block’s header to expand or collapse it could also select text. ([#20045](https://github.com/craftcms/cms/issues/20045))
- Fixed a bug where Matrix fields in cards view didn’t show a dropped entry in its new position until the reorder had saved, and drop placeholders had no gap next to the neighboring card. ([#20046](https://github.com/craftcms/cms/issues/20046))
- Fixed a bug where Markdown inputs without a toolbar had less padding above and below their text than beside it.
- Fixed a bug where pressing <kbd>Return</kbd> on a control within an editable table’s HTML column moved focus to the next row. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Fixed a bug where clicking a combobox’s chevron didn’t focus it or open its options. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Fixed a bug where clicking a combobox cell in an editable table could focus its clear button rather than its text input. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Fixed a bug where the hairline between fields that share a row sat off-center before narrow fields, such as quarter-width ones.
- Fixed a bug where the dialog listing what to resolve before deleting an element wasn’t shown as a dialog on most control panel pages, and its content was added to the bottom of the page unstyled.
- Fixed a bug where modals built on Garnish’s `Modal`, such as the “Replace relations” and “Move to” modals and asset upload conflict prompts, weren’t shown as modals, and their buttons were unstyled.
- Fixed a bug where element selects’ “Choose” buttons in server-rendered forms, such as the “Replace relations” modal, were unstyled.
- Fixed a bug where sections couldn’t be chosen in the “Move to” modal. Sections are now listed as radio buttons.
- Fixed a bug where asset upload conflict prompts used unstyled radio buttons and checkboxes.
- Fixed a bug where nested element fields’ “Paste” buttons and relational fields’ “Copy” menu items were missing their icons.
- Fixed a bug where buttons didn’t show an inset shadow while pressed.
- Fixed a bug where control panel error pages could be shown in a modal over the previous page, rather than as a full page. ([#20120](https://github.com/craftcms/cms/pull/20120))
- Fixed a bug where medium chips’ thumbnails were smaller than small chips’. They’re now 32px, between small and large chips’. ([#20124](https://github.com/craftcms/cms/pull/20124))
- Fixed a bug where portrait image thumbnails in medium and large chips were letterboxed in a square, so their rounded corners didn’t show. ([#20124](https://github.com/craftcms/cms/pull/20124))
- Fixed a bug where the checkmark in enabled lightswitches was slightly off-center. ([#20107](https://github.com/craftcms/cms/pull/20107))
- Fixed a bug where going back to an element index page, such as a volume’s root after opening a subfolder, could switch it from thumbs or cards view to table view with blank rows. ([#20137](https://github.com/craftcms/cms/issues/20137))
- Fixed a bug where Twig control panel pages’ action buttons, action menus, and context menus were missing when rendered inside the Inertia shell.
- Fixed a bug where Categories and Tags fields showed “Legacy input [] has an invalid name.” in the element editor, and their relations weren’t saved. ([#20179](https://github.com/craftcms/cms/pull/20179))
- Fixed a bug where legacy menus, such as the Tags field’s suggestions, were shown below the page instead of positioned beside their trigger. ([#20179](https://github.com/craftcms/cms/pull/20179))

### Content Management

- Asset conditions, such as for index filters and Assets fields’ sources, now have an “Orientation” rule, which filters images by whether they’re landscape, portrait, or square. Images whose width and height differ by up to 2% count as square. ([#20114](https://github.com/craftcms/cms/pull/20114))
- Asset edit pages’ Info panels now show what was found in images, apart from the rest of their metadata: their type, a bar of their main colors, each as wide as the share of the image it covers, and embedded metadata, such as their creator, copyright notice, the date they were taken, their camera, and a map of where they were taken, with a link to Google Maps. The panels’ “Location” item, and asset indexes’ “Location” column and card attribute, which show an asset’s folder, are now “Folder”. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset indexes can now show “Image Type”, “Colors”, “Creator”, “Credit”, “Copyright”, “Date Taken”, “Camera”, and “Embedded Keywords” columns and card attributes. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Element edit forms now show sidebar field layout tabs as panels in the details sidebar, between Info and Activity, using the tab’s icon (or its name’s first letter) and name. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Double-clicking an element chip in an element index now opens the chip’s own element in a slideout, rather than the row’s element or the chip’s link. ([#19907](https://github.com/craftcms/cms/pull/19907))
- In element index tables, only an element’s title now links to it, rather than its whole chip, and clicking a link or button no longer waits to rule out a double-click. ([#19932](https://github.com/craftcms/cms/pull/19932))
- In element index thumbnail views, only an element’s label now links to it. Clicking anywhere else on a thumbnail, card, or table row selects it, including for asset folders, which now only open from their links. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Matrix fields’ blocks views now outline the block that’s being edited. ([#19974](https://github.com/craftcms/cms/pull/19974))
- Clicking a Matrix block in blocks view no longer selects it. Blocks are selected with their checkboxes instead. ([#19974](https://github.com/craftcms/cms/pull/19974))
- Collapsed Matrix blocks are now remembered per site, like their enabled status. Blocks that were already collapsed stay collapsed on every site until they’re expanded there. ([#20119](https://github.com/craftcms/cms/pull/20119))
- Fields without a visible label or instructions no longer have an action menu.
- Double-clicking an element in a relational field’s card or thumbnail view now opens its slideout, as it does in list views.
- Relational fields now have a “Paste” button when the clipboard holds elements one of their sources would list. Pasting relates the copied elements and clears the clipboard.
- Double-clicking an asset folder in an asset index or element selection modal now opens it, and double-clicking an element’s thumbnail in an element index now opens its slideout. ([#19979](https://github.com/craftcms/cms/pull/19979))
- Saving the Customize Sources modal from an element index’s action menu now goes to the source that was last edited, as it does from the secondary nav. ([#19929](https://github.com/craftcms/cms/pull/19929))
- Element details panels (Info, Activity, Revisions, and plugin-provided panels) are now opened by disclosure buttons rather than tabs. Opening a panel moves focus to its heading, and pressing <kbd>Esc</kbd> or the panel’s close button returns focus to its button.
- The element details pane now spans the full viewport height once the page is scrolled, and the Activity panel’s timeline scrolls within the panel, keeping the comment form in view. ([#19919](https://github.com/craftcms/cms/pull/19919))
- The “View all activity” and “View all revisions” links are now shown in their details panels’ headers. ([#19919](https://github.com/craftcms/cms/pull/19919))
- The Activity panel now shows a change from one text to another as a word-level diff, rather than the text before and after.
- Comments can now be submitted with <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd> in the Activity and Workflow details panels. ([#19919](https://github.com/craftcms/cms/pull/19919))
- Comment fields in the Activity and Workflow details panels now start one line high, keep the Comment button inside the field, and show how many characters a comment is over the limit. ([#19923](https://github.com/craftcms/cms/pull/19923))
- Comments in the Activity and Workflow details panels are now more compact, with a background tinted by the gradient from their author’s default photo. The Activity panel’s change summaries are more compact too, and its comment field’s “Comment” button is now a round, icon-only button.
- Workflow review notes can now be up to 10,000 characters long, matching activity comments. ([#19923](https://github.com/craftcms/cms/pull/19923))
- The Workflow panel’s review action radio buttons and image transforms’ “Mode” radio buttons now look like the control panel’s other radio buttons.
- Table fields now have a “Link” column type, with the same settings as Link fields. Link cells return `CraftCms\Cms\Field\Data\LinkData` objects, and Table fields now track the elements they link to. ([#16009](https://github.com/craftcms/cms/discussions/16009), [#19998](https://github.com/craftcms/cms/pull/19998))
- Table field column settings now open in a slideout. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Table cell validation errors now name the column. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Table columns with invalid settings are now flagged in the Table field’s settings, and link setting validation errors in Link, Markdown, and Table fields now name the setting. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Link cells in Table fields now truncate long URLs, so columns keep their configured widths.
- Double-clicking an admin table row now opens its link’s slideout, if it has one. ([#19957](https://github.com/craftcms/cms/pull/19957))
- Element edit pages now have a “Sites” pane in their details sidebars on multi-site installs, in place of the “Status” pane. It lists the sites the element is saved to (that the user can edit), with status toggles and links to the element in each site, and lets authors add the element to other sites. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Element editor slideouts can now be switched to other sites, by clicking on a site name within the “Sites” pane. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Sites that are added to elements within the “Sites” pane can now be removed again. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Assets are now initially saved to the site they were uploaded from. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Filename conflicts for files uploaded from asset indexes are now resolved within the upload notification, which has “Cancel”, “Keep both”, and “Replace” buttons in place of an “Action required” button. ([#20121](https://github.com/craftcms/cms/pull/20121))
- Images uploaded from asset indexes that look like copies of images that are already uploaded, even resized or recompressed, now wait within their upload notification, which shows the existing images as chips and has “Cancel”, “Keep both”, and “Replace” buttons, like filename conflicts. Copies of several images show the closest one’s chip with the others stacked behind it, and have “Keep all” in place of “Keep both” and “Replace”. Images uploaded elsewhere, such as to Assets fields, show a notice with the existing images’ chips instead, which open in a new window. ([#20113](https://github.com/craftcms/cms/pull/20113))
- Assets in volumes set to let each asset choose which sites it should be saved to can now be deleted for individual sites, as long as they exist in another site. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Deleting an element for the only site it exists in now soft-deletes it, unless it’s a draft or nested element. ([#20060](https://github.com/craftcms/cms/pull/20060))
- The “Schedule release” modal now has a single date and time input, like releases’ Target Date, in place of separate Date, Time, and Time Zone inputs. Releases’ publication and target dates are now always entered in the user’s time zone.
- Release pages now list each draft as a chip labeled with its draft name, in a single “Draft” column.
- Release pages’ “Publish now” and “Schedule” buttons now have tooltips explaining why they’re disabled, and the page counts blocking drafts rather than entries.
- Fixed a bug where typing in a newly added nested entry’s fields could replace the edit form with a “UI array path … must be a row index” error once the entry autosaved.
- Fixed a bug where changing a field back to the value it had when the edit page loaded wasn’t autosaved, so field layout elements shown or hidden by conditions based on it didn’t update.
- Fixed a bug where Table fields’ Date and Time cells, and their default row values, shifted by the site’s UTC offset each time they were saved from the control panel.
- Fixed bugs where Dropdown fields didn’t preserve blank option values and could show duplicate blank placeholders. ([#20140](https://github.com/craftcms/cms/pull/20140))
- Fixed a bug where Multi-select fields only kept one selected option. They’re now shown as comboboxes, as in Craft 5. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where “Allow custom options” had no effect on Checkboxes and Radio Buttons fields, and custom values couldn’t be saved. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where options fields accepted values that weren’t among their options. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where Checkboxes, Dropdown, Multi-select, and Radio Buttons fields didn’t show their options’ icons and colors, and Dropdown and Multi-select fields didn’t show their option groups. Dropdown fields with option icons or colors are now shown as comboboxes, as in Craft 5. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where Button Group fields showed only their options’ icons even when “Icons only” was disabled. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where Dropdown fields with option groups didn’t have a leading blank option. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Fixed a bug where going from one element edit page to another, such as switching sites, opened the next page’s “Revisions” details panel rather than the one that was open.
- Fixed a bug where viewing an element revision showed three read-only notices, including one about settings changes not being permitted, and no “Revert content from this revision” button. ([#20115](https://github.com/craftcms/cms/pull/20115))
- Fixed an error that occurred on release pages if one of the release’s drafts belonged to an element that had been deleted, such as along with its section. The draft is now listed as blocked with “The entry has been deleted.”, and can be removed by anyone who can manage the release.
- Fixed a bug where AI-generated alt text and titles replaced the ones filled in from an image’s embedded metadata, for images whose metadata was read by the image analysis queue job, such as indexed images.
- Fixed a bug where the “Duplicate” and “Delete” element actions didn’t do anything when unpublished drafts were selected, and element actions on element indexes ran for the primary site rather than the selected one.
- Fixed a bug where saving an element removed the values of fields whose field type was missing, such as fields from an uninstalled plugin, from some or all of the element’s sites. ([#20173](https://github.com/craftcms/cms/pull/20173))
- Fixed a bug where saving, creating, or deleting an element from its edit page went to the first source of its index, rather than the source it was opened from, such as a section, volume, or custom source. ([#20180](https://github.com/craftcms/cms/pull/20180))
- Fixed a bug where switching sites in an element’s editor didn’t update the site of the index it returns to. ([#20222](https://github.com/craftcms/cms/pull/20222))
- Fixed a bug where reverting an element to a revision dropped the index it was opened from, so saving it afterwards went to the index’s first source. ([#20222](https://github.com/craftcms/cms/pull/20222))
- Fixed a bug where relation fields with “Maintain hierarchy” enabled showed their selected elements as a flat, reorderable list, rather than nested under their parents. ([#20201](https://github.com/craftcms/cms/pull/20201))
- Fixed a bug where relation fields with “Maintain hierarchy” enabled didn’t relate their selected elements’ missing ancestors, or apply their “Branch Limit” setting. ([#20201](https://github.com/craftcms/cms/pull/20201))
- Fixed an XSS vulnerability where element editors followed unsigned `returnUrl` params pointing outside the control panel and sites. ([#20222](https://github.com/craftcms/cms/pull/20222))

### Accessibility

- Search fields’ clear buttons are no longer in the <kbd>Tab</kbd> order. <kbd>Esc</kbd> clears the field from the keyboard. ([#20127](https://github.com/craftcms/cms/pull/20127))
- Improved labeling and keyboard focus management in shared control panel modals. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Buttons and menu items with keyboard shortcuts now announce them to assistive technology, including the Save menu’s items, inline editing’s and modals’ Save buttons, and comments’ Comment buttons. On a Mac, the ⌥, ⇧, and ⌘ symbols are read as “Option”, “Shift”, and “Command”. ([#20219](https://github.com/craftcms/cms/pull/20219))
- Added the `returnFocus` property to `<craft-dialog>`, and the `returnFocus` option to `Craft.openSlideout()`, for deciding where focus goes when they close.
- Added `focusReturnFor()` to `@craftcms/ui/utilities/focus-return`. `event` actions now include a `returnFocus` resolver in their event’s `detail`, which finds the action menu’s button again after the menu closes or re-renders when it has a `data-focus-key` attribute.
- Fixed an accessibility issue where file previews that overflowed their dialog couldn’t be scrolled with the keyboard. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Fixed an accessibility issue where keyboard focus was lost after closing a dialog, slideout, or file preview opened from an action menu, such as “Copy attribute name”, “Copy URL”, “Field settings”, or “Entry type settings”. Focus now returns to the action menu’s button, except on control panel pages that still use legacy slideouts.
- Opening a slideout no longer focuses its resize handle. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where pressing <kbd>Esc</kbd> no longer closed a dialog after an element selection modal opened from it had been closed. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Fixed an accessibility issue where the image editor’s Undo and Redo buttons included their keyboard shortcuts’ symbols in their names. ([#20219](https://github.com/craftcms/cms/pull/20219))
- Page and source items in the Customize Sources modal now show a focus ring around the whole row, including their drag handle and action menu. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Tips on standalone inputs are now part of the input’s accessible description. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Error summaries now receive focus when a form fails validation, and each error links to its field, switching tabs if needed. ([#20044](https://github.com/craftcms/cms/issues/20044))
- The card view designer’s “Alignment” setting is now a radio group, which can be changed with the arrow keys.
- Controls in field layout designers, and rich selects such as color selects, now have the standard focus ring, rather than an inset or flush one.
- Pressing <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd> in a text input or textarea now submits its form, including in editable table cells, where it previously moved focus to the next row. ([#20025](https://github.com/craftcms/cms/pull/20025))
- Pressing <kbd>Shift</kbd> <kbd>Return</kbd> in an editable table’s first row now adds a row above it, if the table allows new rows. ([#20025](https://github.com/craftcms/cms/pull/20025))
- The dashboard’s widget settings now include each widget’s name in its reorder button, column picker, and Delete button labels, and announce a widget’s new position to screen readers after it’s reordered. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Reorder buttons no longer offer a “Move down” option for the only item in a list. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed an accessibility issue where buttons were still announced as “Loading” after they finished loading, and lost their accessible name if loading took more than five seconds. ([#19975](https://github.com/craftcms/cms/pull/19975))
- Fixed an accessibility issue where pressing <kbd>Return</kbd> on a dialog’s close button could reopen the dialog, such as the “Check your email” dialog on the Reset Password screen. ([#20049](https://github.com/craftcms/cms/pull/20049))
- `craft-spinner` now has a `label` property, which describes what is loading to screen readers. Slotted text still replaces it.
- `craft-field` now has an `instructions-sr-only` attribute, and UI fields and the `Field` view component have `instructionsSrOnly()`, which visually hide instructions while keeping them in the input’s accessible description.
- Fixed an accessibility issue where spinners in Content Block and Matrix inline-block fields weren’t announced as loading.
- Fixed an accessibility issue where pressing Escape in a popover inside an element details panel also closed the panel.
- Fixed an accessibility issue where keyboard focus was lost after moving an item with its reorder button’s “Move up” or “Move down” option. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed a bug where the “Move up” and “Move down” options didn’t move anything on Settings → Routes or in the dashboard’s widget settings. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed an accessibility issue where choosing an action menu item with <kbd>Return</kbd> could reopen the menu, or activate whatever received focus next, such as removing an option that had just been added to a checkbox select. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed an accessibility issue where keyboard focus could be lost after closing a popover with <kbd>Esc</kbd>, such as the dashboard’s widget settings in Firefox. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed an accessibility issue where <kbd>Esc</kbd> had to be pressed twice to close a popover after using a menu inside it, such as the dashboard’s widget settings after reordering a widget. ([#20073](https://github.com/craftcms/cms/pull/20073))
- Fixed a bug where single-select comboboxes had no visible focus ring. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where single-select comboboxes’ combobox element had no accessible name in Chromium. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed an accessibility issue where the environment variable and alias hints below the site group name and installer Base URL inputs weren’t announced by screen readers. ([#20050](https://github.com/craftcms/cms/pull/20050))
- Fixed an accessibility issue where links in callouts matched the surrounding text color and weren’t underlined, so they could only be told apart by hovering. ([#20050](https://github.com/craftcms/cms/pull/20050))
- Fixed an accessibility issue where fields’ translation indicators and their help text were hidden. ([#19915](https://github.com/craftcms/cms/pull/19915))
- Fixed an accessibility issue where field layout tabs and image editor tabs weren’t announced with a name for their tab list.
- Fixed an accessibility issue where focus rings on tab panels were cut off by the scrolling panel region.
- Fixed an accessibility issue where the selected tab in `<craft-tabs>`, and the open element details panel’s button, weren’t distinguishable in forced colors mode.
- Fixed an accessibility issue where an `aria-label` passed to a view component’s `attributes()` could be dropped, leaving icon-only buttons without an accessible name. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed an accessibility issue where tooltips explaining why a button was disabled, such as on disabled secondary form actions, never appeared.

### Administration

- Added Settings → Extend → Connections, where administrators choose the providers and models that plugins’ AI features use. Providers come from the `ai.providers` config, with keys set through environment variables. AI features stay off until a provider is chosen. ([#20006](https://github.com/craftcms/cms/pull/20006))
- AI Connections now checks that the models chosen for AI tasks have the capabilities the tasks need, such as vision, document, audio, or video input, structured output, or tool calling, when the settings are saved. Tasks whose model lacks one are unavailable until a different model is chosen, and the settings explain why. ([#20099](https://github.com/craftcms/cms/pull/20099))
- Added the “Generate alt text” AI task, which writes alt text for images in each site’s language, filling in images that are missing it when they’re uploaded, replaced, indexed, or analyzed. Sites that share alt text get one. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Images’ embedded alt text is now read in each language the file has it in, and each site of an image is given the alt text in its language, if the file has it.
- When the “Generate alt text” AI task is set up, it now writes the alt text that some of an image’s sites are missing as translations of the alt text the image already has, and images’ embedded descriptions are given to the model as context, rather than used as their alt text.
- Alternative Text fields now have a “Generate alt text” action, which fills in suggested alt text for editors to review before saving. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Generate titles” AI task, which titles images that are still titled after their filename, such as “IMG 4759”, in each site’s language, when they’re uploaded, replaced, indexed, or analyzed. Sites that share titles get one. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Uploaded images, and images whose files are replaced while they’re still titled after their filename, are now titled from the title embedded in their file, if they have one. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Detect focal points” AI task, which sets the focal points of images that don’t have one when they’re uploaded, replaced, indexed, or analyzed, so their crops keep the subject in view. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Tag images” AI task, which tags images with keywords that describe them and the text that appears in them, so they can be found by searching. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Classify images” AI task, which finds what kind of image each image is, such as a photo, an illustration, a screenshot, or a diagram, so images can be filtered by it, with the “Image Type” asset condition rule. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Images are now analyzed by AI tasks that are set up, such as for alt text, when they’re uploaded or replaced, or when asset indexing finds a new or changed file. Analysis only fills in what images are missing. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Analyze images” asset action, which queues the selected images to be analyzed by AI tasks that are set up. ([#20103](https://github.com/craftcms/cms/pull/20103))
- The Asset Indexes utility now has an “Analyze images” option, and the `index-assets` commands an `--analyzeImages` option, which sample missing image colors and metadata, and queue images for AI tasks. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset indexing now only samples the colors of images whose files are new or have changed, unless the “Analyze images” option is enabled, since remote images have to be downloaded to be sampled. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Templates and GraphQL queries can now choose white or black text for legibility over an image or one of its edges, with `asset.analysis.colors.textColor()` and the `textColor` GraphQL field, which also give the text’s contrast ratio. Images sampled before are sampled again the next time they’re indexed with the “Analyze images” option. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the `orientation` asset query param and GraphQL argument, which filter images by whether they’re landscape, portrait, or square, such as `craft.assets().orientation('portrait')`. Image assets’ orientations are available via `asset.orientation` in templates and the `orientation` GraphQL field. ([#20114](https://github.com/craftcms/cms/pull/20114))
- Images now keep the metadata embedded in their files when they’re uploaded, replaced, or indexed: their title, description, alt text, creator, credit line, copyright notice, keywords, date taken, and camera, the city, state, and country it shows, and the GPS coordinates it had, read before uploads are cleaned of them. Embedded keywords and descriptions are searchable. Uploads are read before they’re cleaned, and images indexed before are read the next time they’re indexed with the “Analyze images” option. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Control panel uploads now read the metadata embedded in images in the browser, and send it along with their colors, so the server doesn’t need to read the file itself. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Images uploaded straight to a remote filesystem, such as an S3 bucket, are no longer downloaded to be sampled, unless they’re cleaned or their dimensions can’t be read from the start of the file. If the uploader didn’t send their colors and metadata, they’re sampled by a queue job instead. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Field layout tabs can now be sidebar tabs, whose fields are shown in element edit forms’ details sidebar. Sidebar tabs always come after content tabs. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Field layout tabs can now have icons, which are shown before their names in field layout designers and on element edit forms. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Field layout designers now show content tabs and sidebar tabs in separate panes. Tabs can be moved between them by dragging, or with their “Move to the sidebar” and “Move to content” actions. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Field layout designers’ and card view designers’ workspaces now have a dotted grid, and their drop placeholders are now translucent. ([#20005](https://github.com/craftcms/cms/pull/20005))
- The MCP server’s field layout configs now support tabs’ `icon` and `type` settings. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Section and Matrix site settings now have a single “Route To” setting, in place of separate Route Type and Route settings. Namespaced controller actions and values that match a named route are saved as routes, and anything else is saved as a template. ([#19942](https://github.com/craftcms/cms/pull/19942))
- “Route To” settings now suggest the app’s named routes and controller actions, as well as templates. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Matrix fields’ Site Settings tables now have a narrow Site column, and give the Entry URI Format and Route To columns equal widths, as Sections’ do.
- Element index sources can now show a count of their elements in the control panel nav, via a new “Show count” setting in the “Customize sources” modal. Entries, assets, and users support it. ([#19967](https://github.com/craftcms/cms/pull/19967))
- Updated the index pages for user groups, GraphQL schemas and tokens, image transforms, asset volumes and transformers, workflows, sections, entry types, fields, and sites to use shared UI tables. ([#19935](https://github.com/craftcms/cms/pull/19935))
- The System Messages utility now shows each message in a card, and edits messages in a slideout with a Markdown editor. Typing `{{` in a message’s subject or body suggests the Twig variables it can use. ([#19946](https://github.com/craftcms/cms/pull/19946))
- The MCP server’s `elements.update` tool can now set and clear assets’ focal points via a `focalPoint` attribute. ([#19933](https://github.com/craftcms/cms/pull/19933))
- The MCP server now validates custom field values against the `elements.field-schema` schema, and returns an error that identifies each invalid value. ([#19965](https://github.com/craftcms/cms/pull/19965))
- The MCP server now accepts any date or time that Date and Time fields can parse, such as “2:30 PM” or a date with a UTC offset, rather than requiring ISO 8601. A value they can’t parse is rejected, rather than clearing the field, and a field’s instructions no longer replace the description of its format.
- Fixed an error that occurred when editing an entry type that was in use. ([#20138](https://github.com/craftcms/cms/issues/20138))
- Fixed a bug where the HTTP MCP servers rejected handshake-based clients. Both handshake sessions and per-request protocol metadata are now supported.
- The MCP server’s `mcp:use` OAuth scope has been replaced with `mcp:read`, `mcp:write`, and `mcp:admin`. Users now choose an access level when they authorize an MCP client, defaulting to read only, and only administrators are offered full access. Clients that authorize again keep the earlier level without another prompt. ([#20080](https://github.com/craftcms/cms/pull/20080))
- The MCP server now only shows and accepts the custom fields that users can see and edit in an element’s edit form, as field layouts’ conditions allow. ([#20091](https://github.com/craftcms/cms/pull/20091))
- Added the `assets.analyze` MCP tool, which queues images to be analyzed by the image analysis AI tasks that are set up, such as for alt text, filling in only what they’re missing. ([#20103](https://github.com/craftcms/cms/pull/20103))
- The MCP server’s `content-model.audit` tool now returns notes for its checks. The `missing-alt-text` check’s note says whether the “Generate alt text” AI task is set up, and if so, to queue the images with `assets.analyze`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `caches.list`, `caches.clear`, `queue.status`, `queue.run`, `queue.retry-all`, `queue.release-all`, and `asset-indexes.update` MCP tools, which do what the Caches, Queue Manager, and Asset Indexes utilities do, for users with access to those utilities. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `maintenance-mode.get`, `maintenance-mode.enable`, `maintenance-mode.disable`, and `project-config.rebuild` MCP tools for administrators. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `preferences.get` and `preferences.update` MCP tools, which read and change the user’s preferred language and formatting locale. ([#20095](https://github.com/craftcms/cms/pull/20095))
- The MCP server’s `configuration.*` tools can now manage asset transformers and workflows. Managing workflows requires Craft Pro. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `component-types.list` and `component-types.get` MCP tools, which list the available field types, workflow stage types, asset transform drivers, field layout elements, and condition rules, and describe each one’s settings as JSON Schema, with their labels, instructions, accepted values, and defaults. ([#20095](https://github.com/craftcms/cms/pull/20095))
- The MCP server now tells clients which features need a higher Craft edition, including more users than the edition allows, so they can explain that a feature such as workflows requires Craft Pro rather than treat it as unknown. ([#20095](https://github.com/craftcms/cms/pull/20095))
- The MCP server now redacts sensitive values, such as API keys, passwords, and license keys, in field settings, asset transformer settings, and project config. Clients can send a redacted value back to keep the saved value. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added the “Query for releases” GraphQL schema permission, nested under “Query for element drafts”, which adds `releases` and `release` queries, and a `release` field to drafts.
- Added the `hasUrls` field to volumes in GraphQL queries.
- GraphQL mutations now return an error when new Matrix entries aren’t listed in `sortOrder`, rather than ignoring them, and each Matrix entry input can only specify one entry type. ([#19965](https://github.com/craftcms/cms/pull/19965))
- Entry type and user group descriptions, fields’ default instructions, tips, and warnings, and field layout elements’ instructions, tips, warnings, and Markdown content now have Markdown inputs. ([#20001](https://github.com/craftcms/cms/pull/20001))
- The Markdown field’s default toolbar now only includes bold, italic, code, list, link, and preview buttons. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Field layouts with a card view designer now show it and the content tab designer in “Content” and “Appearance” tabs, which show an error icon when their content has errors. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Field layout designers now have a 16px background grid, which their tabs and New Tab button line up with. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card view designers’ previews now reflect the owner’s color, icon, and name, such as an entry type’s, as they’re edited. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card view designers’ thumbnail settings are now grouped under “Thumbnail”, below Card Attributes. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Entry types’ Icon and Color settings are now in their field layout designer’s Appearance tab, above Card Attributes. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card view designers’ previews now include a sample chip above the card, which reflects the owner’s color and whether its cards have thumbnails. ([#20003](https://github.com/craftcms/cms/pull/20003))
- Field layouts’ Generated Fields setting is now in the field layout designer’s Content tab. ([#20003](https://github.com/craftcms/cms/pull/20003))
- Field layout element and dashboard widget width pickers now preview the new width on hover. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Custom fields now have “Default Tip” and “Default Warning” settings. ([#19976](https://github.com/craftcms/cms/pull/19976))
- Volumes now have a “Propagation Method” setting, which can be set to “Let each asset choose which sites it should be saved to”. New volumes default to this, as do existing volumes on single-site installs. Existing volumes on multi-site installs continue to save assets to all sites. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Added the `defaultRateLimit` and `defaultRateLimitDuration` config settings, which configure the rate limits for login, password reset, and two-step verification requests. ([#20054](https://github.com/craftcms/cms/pull/20054))
- Added the `trashedAssetDisk` config setting. When it’s set, soft-deleting an asset moves its file to that disk, and restoring the asset moves it back. Otherwise, soft-deleting an asset deletes its file, and the asset can’t be restored, unless it was deleted along with its volume. ([#8745](https://github.com/craftcms/cms/discussions/8745), [#20093](https://github.com/craftcms/cms/pull/20093))
- GraphQL schemas can now include read-only components for sites, sections, entry types, volumes, and user groups. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added the `sites`, `site`, `sections`, `section`, `entryTypes`, `entryType`, `volumes`, `volume`, `userGroups`, and `userGroup` GraphQL queries, which are available when the schema includes the corresponding components. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Elements now have `site` fields in GraphQL, when the schema includes the “Query for sites” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Entries now have `section` and `type` fields in GraphQL, when the schema includes the “Query for sections” and “Query for entry types” components. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Assets now have `volume` fields in GraphQL, when the schema includes the “Query for volumes” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Users now have `groups` fields in GraphQL, when the schema includes the “Query for user groups” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Fixed a bug where impersonation URLs, including those generated by `craft:users:impersonate`, redirected to the login page rather than signing in as the user.
- Fixed an error when saving plugin settings that failed the settings model’s validation. ([#20158](https://github.com/craftcms/cms/pull/20158))
- Fixed a bug where the Template UI element wasn’t available in field layout designers. ([#20185](https://github.com/craftcms/cms/pull/20185))
- Fixed a bug where the `entrify/categories`, `entrify/tags`, and `entrify/global-set` commands failed with an “Unknown command” error. ([#20175](https://github.com/craftcms/cms/pull/20175))

### Extensibility

- Added the `cp-radio` and `cp-checkbox` classes, which draw native radio buttons and checkboxes like `<craft-radio>` and `<craft-checkbox>` draw theirs.
- Added `CraftCms\Cms\User\Elements\User::photoColors()`, which returns the two colors of a user’s default photo’s gradient.
- Added the `craft:ui-value-change` DOM event and `Cp.$ui.active()` for plugins to observe canonical values from Craft 6 UI-rendered fields. ([#19920](https://github.com/craftcms/cms/discussions/19920))
- Added `CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem`, `PageRegistry`, `ActionRegistry`, and `SearchProviderRegistry`, which plugins can use to add pages, actions, and search providers to the command palette, and `CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider` and `CraftCms\Cms\Cp\CommandPalette\SearchProviders\ElementSearchProvider`, for searching custom content or element types. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Added `Craft.commandPalette`, with `open()`, `close()`, `registerActions()`, `registerProvider()`, and `registerView()` methods, for opening the command palette and adding page actions, client-side search providers, and views to it. A page’s own action menus are listed as command palette actions when they’re within an element with a `data-command-palette-actions` attribute. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Added `CraftCms\Cms\Asset\Elements\Asset::getCardCoverUrl()`. ([#20148](https://github.com/craftcms/cms/pull/20148))
- Added the `cover-src` and `luminance` attributes to `craft-card`, which make it a cover card. ([#20148](https://github.com/craftcms/cms/pull/20148))
- Added `CraftCms\Cms\FieldLayout\FieldLayout::getCardThumbStyle()`, `setCardThumbStyle()`, and `cardThumbStyle()`, and the `cardThumbStyle` field layout config key. ([#20148](https://github.com/craftcms/cms/pull/20148))
- Added `CraftCms\Cms\FieldLayout\FieldLayout::$thumbSources` and `thumbSources()`, and the `thumbSources` field layout config key, which replaces `thumbFieldKey`. ([#20156](https://github.com/craftcms/cms/discussions/20156), [#20181](https://github.com/craftcms/cms/pull/20181))
- Added `CraftCms\Cms\Element\Data\Thumb`, `CraftCms\Cms\Element\Contracts\ElementInterface::getThumb()`, `CraftCms\Cms\FieldLayout\FieldLayout::getThumbForElement()`, `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::thumb()`, `CraftCms\Cms\Field\BaseRelationField::getThumb()`, and `CraftCms\Cms\Asset\Elements\Asset::canCoverCard()`. Element types with their own thumbnails can override the protected `thumb()` method, and native layout fields can override `thumb()`, so their thumbnails are known without being rendered. ([#20181](https://github.com/craftcms/cms/pull/20181))
- Deprecated `CraftCms\Cms\FieldLayout\FieldLayout::$thumbFieldKey` and `thumbFieldKey()`. `$thumbSources` and `thumbSources()` should be used instead. ([#20156](https://github.com/craftcms/cms/discussions/20156), [#20181](https://github.com/craftcms/cms/pull/20181))
- Added `CraftCms\Cms\Asset\Elements\Asset::getOrientation()`, `CraftCms\Cms\Element\Queries\AssetQuery::orientation()`, `CraftCms\Cms\Asset\Enums\ImageOrientation`, `CraftCms\Cms\Asset\Conditions\OrientationConditionRule`, and the `orientation` GraphQL argument and field for assets, which say whether images are landscape, portrait, or square, from their width and height. ([#20114](https://github.com/craftcms/cms/pull/20114))
- `CraftCms\Cms\Asset\AssetsHelper::iconSvg()` now has a `$dark` argument, and returns the dark variant for the control panel’s dark theme by default. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Asset\AssetsHelper::folderIconSvg()`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Asset\Enums\FileKind::isText()`. ([#20094](https://github.com/craftcms/cms/pull/20094))
- Added `CraftCms\Cms\Cp\Enums\Theme::resolveFor()`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Translation\Locale::getLanguageTag()`, and the `Craft.formattingLocale` JavaScript variable, which hold the formatting locale as a BCP 47 language tag for `Intl` APIs. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Utility\Utility::subpageTitle()`, which titles a utility’s subpages and gives them a breadcrumb back to the utility. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Element\ElementHelper::postEditRedirectUrl()`, which returns the current request’s `returnUrl` param, or else the element’s post-edit URL. ([#20180](https://github.com/craftcms/cms/pull/20180))
- Added `CraftCms\Cms\Element\ElementHelper::returnUrlForSite()`, which sets the `site` param of a `returnUrl` param value, keeping it encrypted if it was. ([#20222](https://github.com/craftcms/cms/pull/20222))
- `craft\web\assets\prismjs\PrismJsAsset` no longer registers Prism, or Craft 5.12’s Prism shim. Plugins that rely on Prism should bundle it themselves, or use `Craft.highlighter` instead. ([#20088](https://github.com/craftcms/cms/pull/20088))
- Added `CraftCms\Cms\Support\Query::prepareDateForDb()`, which converts a date to the UTC string Craft stores dates as, for writing or comparing it with the query builder. ([#20053](https://github.com/craftcms/cms/pull/20053))
- Eloquent models that extend `CraftCms\Cms\Shared\BaseModel` now store and read dates in UTC, including their timestamps and soft-delete dates. ([#20053](https://github.com/craftcms/cms/pull/20053))
- Added `CraftCms\Cms\Ai\Task` and `CraftCms\Cms\Ai\AiTasks`, for registering AI tasks. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added `CraftCms\Cms\Support\Facades\Ai`, which plugins use to run `laravel/ai` agents and requests with the provider and model administrators choose for each task. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added `CraftCms\Cms\Ai\Events\TaskRunning`, `TaskCompleted`, and `TaskFailed`, which plugins can use to track AI usage and failures, and cancel requests. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added `CraftCms\Cms\Ai\Contracts\TaskInterface::requirements()` and `CraftCms\Cms\Ai\Enums\Capability`, which AI tasks use to say what they need from their model, such as vision or tool calling. ([#20099](https://github.com/craftcms/cms/pull/20099))
- Added `CraftCms\Cms\Ai\CapabilityChecks`, which checks whether providers’ models have a capability. ([#20099](https://github.com/craftcms/cms/pull/20099))
- Added `CraftCms\Cms\Ai\Enums\AiUnavailableReason::MissingCapability`. ([#20099](https://github.com/craftcms/cms/pull/20099))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalysisTask`, which plugins can extend to analyze images with AI. Image analysis tasks that use the same provider and model share one request per image. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalyzer` and the `CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage` job. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalyzer::suggest()`, which returns the changes image analysis tasks would make without saving them. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Elements\Asset::$analysis`, `CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis`, and the `analysis` GraphQL field for assets, which hold what was found in an image: its colors, and the keywords and text found by the “Tag images” AI task, which are searchable. ([#20103](https://github.com/craftcms/cms/pull/20103))
- `CraftCms\Cms\Asset\Elements\Asset::$colors` and the `colors` GraphQL field for assets have moved to `$analysis->colors` and `analysis { colors }`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\Data\ImageColors::$palette` and `labels()`, and the `palette` and `labels` GraphQL fields for image colors, which list an image’s main colors and their names. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\ColorNames`, which names colors in plain words. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\Data\ImageColors::$luminance` and `textColor()`, `CraftCms\Cms\Image\ColorGrid::luminance()` and `relativeLuminance()`, and the `luminance` and `textColor` GraphQL fields for image colors, which say how light the darker and lighter parts of an image’s regions are, and which text color is legible over them. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\Data\ImageColors::$weights` and the `weights` GraphQL field for image colors, which say how much of an image each of its palette’s colors covers. Images sampled before are sampled again the next time they’re indexed with the “Analyze images” option. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\Data\ImageMetadata`, `CraftCms\Cms\Image\ImageMetadataReader`, `CraftCms\Cms\Image\Images::metadata()`, `CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis::$metadata`, and the `metadata` GraphQL field for image analysis, which hold the metadata embedded in an image’s file. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Image\Data\ImageMetadata::$latitude`, `$longitude`, `$city`, `$state`, and `$country`, and the `city`, `state`, and `country` GraphQL fields for image metadata. The coordinates aren’t available to GraphQL or sandboxed templates. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Element\Contracts\ElementInterface::getMetadataGroups()`, which returns groups of metadata that are shown apart from each other in the editor sidebar. Elements that implement `ElementInterface` without extending `CraftCms\Cms\Element\Element` must add it. ([#20103](https://github.com/craftcms/cms/pull/20103))
- `CraftCms\Cms\Cp\Html\ContentHtml::metadataHtml()` now accepts several groups of metadata, which are separated by a rule. ([#20103](https://github.com/craftcms/cms/pull/20103))
- `CraftCms\Cms\Asset\Elements\Asset::$uploadColors` has been replaced by `$uploadAnalysis`, which holds what was already read from the incoming file, its colors and embedded metadata. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset upload completion requests now take what the uploader read from the file as `analysis`, with `colors` and `metadata` keys, rather than `colors`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset upload completion requests now take the difference hash the uploader found for an image as `analysis.hash`, alongside `analysis.colors`. ([#20113](https://github.com/craftcms/cms/pull/20113))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalyzer::needsSampling()`, and a `$sample` argument to `CraftCms\Cms\Asset\Analysis\ImageAnalyzer::queue()`, which also queues images that are missing their colors or metadata. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Filesystem\Data\UploadedFile::isLocal()` and `imageSize()`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Elements\Asset::hasDefaultTitle()`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalysisTask::fillFromMetadata()`, which image analysis tasks can override to fill in values from an image’s embedded metadata in any of its sites, such as Craft’s alt text and title tasks do, whether or not AI is set up, and `CraftCms\Cms\Asset\Analysis\ImageAnalyzer::metadataChanges()` and `fillOtherSitesFromMetadata()`. ([#20103](https://github.com/craftcms/cms/pull/20103))
- `CraftCms\Cms\Asset\Analysis\Contracts\ImageAnalysisTaskInterface::instructions()` and `schema()` now have `$overwrite` arguments, like `needed()` and `apply()`.
- Added `CraftCms\Cms\Image\Data\ImageMetadata::$altTextTranslations` and `altTextIn()`.
- Content-model audit checks registered with `CraftCms\Cms\Mcp\ContentModel\CheckRegistry` can now return a `note` with their findings. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\ImageDuplicates`, which finds images that look like copies of each other. ([#20113](https://github.com/craftcms/cms/pull/20113))
- Added `CraftCms\Cms\Image\ImageHash`, `CraftCms\Cms\Image\Data\ImageSample`, `CraftCms\Cms\Image\Images::sample()`, `CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis::$hash`, and the `hash` GraphQL field for image analysis, which hold an image’s difference hash. ([#20113](https://github.com/craftcms/cms/pull/20113))
- Added `CraftCms\Cms\Asset\Enums\ImageType`, `CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis::$type`, and the `type` GraphQL field for image analysis. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Elements\Asset::getImageType()`, `CraftCms\Cms\Element\Queries\AssetQuery::imageType()`, `CraftCms\Cms\Asset\Conditions\ImageTypeConditionRule`, and the `imageType` GraphQL argument for asset queries, which filter images by what kind of image they are. Image types are indexed through the generated `assets.imageType` column. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added `CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges::get()`, so image analysis tasks that change the same attribute, such as an asset’s analysis, can build on each other’s changes. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Field action menu items can now dispatch a `craft:generate-field-value` event with a `url` and `params`, to fill the field with a value generated on the server. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the `craft:screen-panel` global Vue component, which plugins can use on their own Inertia pages to add panels, with an optional status and header actions, to the screen’s details pane. ([#20084](https://github.com/craftcms/cms/pull/20084))
- `CraftCms\Cms\Mcp\Contracts\ProvidesInputSchema` has been renamed to `CraftCms\Cms\Field\Contracts\InputSchemaFieldInterface`, and its `getMcpInputSchema()` method to `getInputSchema()`.
- Added `CraftCms\Cms\Update\Maintenance`, which turns maintenance mode on and off. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Utility\Utilities\ClearCaches::clear()`. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Workflow\WorkflowDefinitions`, which validates workflow definitions. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Asset\AssetTransformDrivers::settings()`. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Ui\Controls\Control::inputSchema()`, which describes the values a control accepts as JSON Schema. Custom controls can override it so the MCP server can describe the settings they set. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Component\Kinds\ComponentKinds`, `CraftCms\Cms\Component\Kinds\ComponentKind`, and `CraftCms\Cms\Component\Kinds\TypeRegistryKind`, which plugins can use to describe their own kinds of extendable components and their settings, such as for the `component-types.list` and `component-types.get` MCP tools. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Added `CraftCms\Cms\Ui\SettingsSchema`, which describes a settings UI as JSON Schema. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Routes outside the `craft` middleware group can now use `Route::allowDuringMaintenance()` to stay reachable during maintenance mode. ([#20095](https://github.com/craftcms/cms/pull/20095))
- MCP tools without `readOnlyHint: true` now require the `mcp:write` scope, and capabilities with `#[RequiresAdmin]` or `#[RequiresAdminChanges]` require `mcp:admin`. ([#20080](https://github.com/craftcms/cms/pull/20080))
- Plain Text, Markdown, Email, Link, Number, Money, Lightswitch, Date, Time, Color, Country, Table, and option fields now implement `CraftCms\Cms\Field\Contracts\InputSchemaFieldInterface`.
- Added `CraftCms\Cms\Gql\GqlInputSchema`, which describes the values GraphQL input types accept as JSON schemas.
- Control panel screens from plugins that haven’t been updated for Craft 6 now render inside the control panel shell, with their tabs, assets, and JavaScript intact.
- Element index screens from plugins that haven’t been updated for Craft 6 now use the new element index, with the element type’s sources in the secondary nav and a Customize Sources action.
- Added `CraftCms\Cms\Ui\Nodes\Table`, for rendering admin tables from PHP UI definitions. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Added `CraftCms\Cms\Cp\Components\CopyAttribute` for rendering inline copyable values in PHP and templates. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Added `CraftCms\Cms\Element\Contracts\ElementInterface::supportsSourceBadgeCounts()`, `sourceSupportsBadgeCount()`, and `sourceBadgeCount()`, and the `defaultBadgeCount` source key, for element types whose sources can show a count of their elements. ([#19967](https://github.com/craftcms/cms/pull/19967))
- Added `CraftCms\Cms\Cp\Data\ActionItem::$badgeLabel`. Element sources’ `badgeLabel` keys are now passed to screen readers in the control panel nav. ([#19967](https://github.com/craftcms/cms/pull/19967))
- Added the `<craft-text-diff>` web component, which shows two versions of a text as one word-level diff, with each rewritten phrase as one deletion and one insertion. It can collapse long unchanged stretches, and mark passages of the text.
- Fixed a bug where `t()` from `@craftcms/ui` failed to format a message with a comma inside a plural or select option.
- `<craft-badge-indicator>` now has a `max` property, which sets the largest count shown before it’s shortened to “99+”. Set it to `Infinity` to show the whole count. Counts are now formatted for the page’s language. ([#19967](https://github.com/craftcms/cms/pull/19967))
- Added `CraftCms\Cms\Asset\Elements\Asset::$trashedFileExists` and `getTrashedFilePath()`. ([#20093](https://github.com/craftcms/cms/pull/20093))
- Removed `CraftCms\Cms\Asset\Elements\Asset::$keptFile`, and the `assets` table’s `keptFile` column. `$trashedFileExists` should be used instead. ([#20093](https://github.com/craftcms/cms/pull/20093))
- Removed `CraftCms\Cms\Element\ElementHelper::renderElements()`. `CraftCms\Cms\Support\Template::render()` should be used instead.
- System messages can now list the Twig variables they’re rendered with via a `variables` attribute, which the System Messages utility suggests while editing. Added `CraftCms\Cms\SystemMessage\SystemMessages::getTemplateVariables()`. ([#19946](https://github.com/craftcms/cms/pull/19946))
- Admin table links and create actions can now open in a slideout with a custom width, via the `slideoutWidth` link option and new `slideout` and `slideoutWidth` arguments on `CraftCms\Cms\Ui\Nodes\Table::createAction()`. ([#19956](https://github.com/craftcms/cms/pull/19956))
- Core slideouts now open on the Vue slideout stack. Core no longer uses `Craft.CpScreenSlideout`, `Craft.ElementEditorSlideout`, or `Craft.createElementEditor()`, except for two-step verification setup. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Action menu items can now open a control panel screen in a slideout via a `craft:open-slideout` event action, with the screen’s `url` in its detail. ([#19961](https://github.com/craftcms/cms/pull/19961))
- `openSlideout()` now accepts an `onClosed` callback, which is called once the slideout is gone, however it was closed. ([#19961](https://github.com/craftcms/cms/pull/19961))
- `useAppLayout()` now accepts a `submitButtonLabel` option. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Plugins’ control panel nav items can now be shown as groups, by calling `group(true)` on the item returned by `getCpNavItem()` and passing its items to `subnav()`. ([#19903](https://github.com/craftcms/cms/pull/19903))
- Plugin bundles can now import the control panel’s components and composables from the `@craftcms/cp` import-map module, including `ElementIndexPage`, `ElementEditor`, `AdminTable`, `SearchForm`, `DeleteButton`, `useCraftTable`, `createCraftColumnHelper`, and `useInertiaReorder`. ([#19912](https://github.com/craftcms/cms/issues/19912))
- `useCraftTable` now has an `inertia` option, which pages and sorts a table through Inertia visits that reload its rows, `pagination`, and `sort` props together. ([#19912](https://github.com/craftcms/cms/issues/19912))
- Added the `cp-table--flush` class, which pads the outer edges of each table row by `--cp-container-padding`. ([#19906](https://github.com/craftcms/cms/pull/19906), [#19936](https://github.com/craftcms/cms/pull/19936))
- Added the `appearance` prop to the `AdminTable` Vue component, and `CraftCms\Cms\Ui\Nodes\Table::appearance()`, which set how a table meets its container: `plain`, `flush`, or `raised`. The values are available as `TableAppearance` from `@craftcms/cp` and `CraftCms\Cms\Cp\Enums\TableAppearance`. ([#19936](https://github.com/craftcms/cms/pull/19936))
- Added the `max-height` attribute to `<craft-markdown-field>`, which sets how tall the editor grows before it scrolls. ([#19923](https://github.com/craftcms/cms/pull/19923))
- Added the `--markdown-field-font-size` CSS custom property, which sets the size of `<craft-markdown-field>`’s text.
- `<craft-markdown-field>` now reflects its `show-toolbar` property to the attribute.
- Added the `separated` attribute to `<craft-field-group>`, and `CraftCms\Cms\Cp\Components\FieldGroup::separated()`, which widen the gap between columns and draw a hairline between fields that share a row. ([#20022](https://github.com/craftcms/cms/issues/20022), [#20036](https://github.com/craftcms/cms/pull/20036))
- Added the `outline-fill` appearance to `<craft-pane>`, and `CraftCms\Cms\Cp\Enums\PaneAppearance::OutlineFill`. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added the `appearance` and `size` properties to `<craft-disclosure>`. `appearance="plain"` drops the content’s hairline and indent, and `size` sizes the default toggle. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added the `hyperlinkUrl`, `hyperlinkTag`, and `hyperlinkAttributes` config options to `CraftCms\Cms\Cp\Html\ElementHtml::elementChipHtml()`, and the `bare` prop to the `CpLink` Vue component. ([#19932](https://github.com/craftcms/cms/pull/19932))
- Added `Cp.$elementDetailsPanels`, for registering element details panels, along with the `ElementDetailsPanelDescriptor`, `ElementDetailsPanelContext`, `ElementDetailsPanelStatus`, and `ElementDetailsPanelRegistry` types.
- Element details panels’ content is no longer padded by the details pane. Panels registered via `Cp.$elementDetailsPanels` with a `component` should set their own padding, using `--cp-container-padding` for the inline padding. ([#19919](https://github.com/craftcms/cms/pull/19919))
- Added the `label` attribute to `<craft-tabs>`, and `CraftCms\Cms\Cp\Components\Tabs::label()`, which set the tab list’s accessible name.
- Added `CraftCms\Cms\Cp\Navigation::sourceSubnav()`, which builds a subnav from an element type’s sources, for plugins adding their own control panel section.
- Added `CraftCms\Cms\Element\Eloquent\EloquentElement` and `CraftCms\Cms\Element\Eloquent\EloquentElements::register()`, for managing an app’s Eloquent models as elements in the control panel, kept in sync with their models. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Added `CraftCms\Cms\Element\Eloquent\EloquentElementsField`, a base class for field types that relate elements to an Eloquent element type’s elements. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Added `CraftCms\Cms\Http\Responses\CpScreenResponse::ui()`, for rendering UI definitions or resolved payloads. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Added `CraftCms\Cms\Cp\Data\ActionItem::$status` and `status()`. ([#19892](https://github.com/craftcms/cms/pull/19892))
- Added `craft.cp.selectize()` and `CraftCms\Cms\Cp\FormFields::selectizeFromConfig()`, which render the legacy selectize variables as a combobox.
- Added `CraftCms\Cms\Ui\Nodes\TemplateContent::trusted()`, which allows developer-controlled HTML to include interactive content and inline SVG. ([#19913](https://github.com/craftcms/cms/pull/19913))
- Added `craft.cp.select()` and `CraftCms\Cms\Cp\FormFields::selectFromConfig()`, which render the legacy select variables as a `<craft-select>`.
- Added `craft.cp.autosuggest()` and `CraftCms\Cms\Cp\FormFields::autosuggestFromConfig()`, which render the legacy autosuggest variables as a combobox. Craft 5’s `suggestions` shape is still accepted.
- Added `CraftCms\Cms\Cp\Components\Combobox::labelledBy()`, for a combobox labelled by an element the caller renders itself.
- Added `CraftCms\Cms\Ui\Nodes\Field::headingPrefix()`, `headingSuffix()`, `translatable()`, `orientation()`, `inputWidth()`, `fieldset()`, and `showStatus()`. ([#19915](https://github.com/craftcms/cms/pull/19915))
- Updated `CraftCms\Cms\Ui\Nodes\Field::make()` and `label()` to accept `Illuminate\Contracts\Support\Htmlable` and `Twig\Markup` labels. ([#19915](https://github.com/craftcms/cms/pull/19915))
- Added `CraftCms\Cms\Route\ElementRoute::isRoute()`, which determines whether an element site settings destination refers to a route or a template. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added `CraftCms\Cms\Cp\SelectOptions::getRouteSuggestions()`, which returns the app’s named routes and controller actions, optionally limited to those that accept a given element type. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added `CraftCms\Cms\Element\Data\ElementSiteSettings::destinationColumn()`, which replaces `routeColumn()`. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Element site settings forms now post a single `destination` value, in place of `routeType` and `route`. Explicit `template` and `route` values are still supported. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added the `centered` editable table column class, and column classes now apply to header cells as well as body cells. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Removed the `prefixSelect` editable table column option. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Removed the `--c-select-*`, `--c-input-spacing-block`, and `--c-form-control-spacing-block` CSS custom properties. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added support for field presentation settings, formatted instructions, and field layout identifiers to `CraftCms\Cms\Ui\Nodes\Group` when using `asField()`, and fixed field spacing in UI modals. ([#19940](https://github.com/craftcms/cms/pull/19940))
- Added `CraftCms\Cms\Plugin\Concerns\HasSettings::createSettings()`, a static method which replaces `CraftCms\Cms\Contracts\PluginInterface::createSettings()`. ([#19574](https://github.com/craftcms/cms/pull/19574))
- `<craft-tabs>` now always has a tab selected. A `selected-index` of `-1` resolves to the first enabled tab.
- Updated `CraftCms\Cms\Cp\Components\Tabs` to throw an `InvalidArgumentException` when rendered without a label, and `<craft-tabs>` to log a console error when rendered without a `label` attribute.
- `CraftCms\Cms\Element\Element::searchableAttributes()` is now `final`. `defineSearchableAttributes()` should be overridden instead, including by element types extending `craft\base\Element` through the Yii adapter.
- `CraftCms\Cms\Element\Element::prepareEditScreen()` now accepts a `Symfony\Component\HttpFoundation\Response|CraftCms\Cms\Http\Responses\CpScreenResponse` argument, rather than `yii\web\Response`. Overrides must update their signatures.
- `CraftCms\Cms\Element\Queries\ElementQuery::statusCondition()` now returns a `Closure` which modifies an `Illuminate\Database\Query\Builder`, rather than a Yii query condition. Overrides must update their signatures and return closures.
- `craft\base\Model::attributes()` now declares an `array` return type. Overrides must update their signatures.
- Plugin settings models must now extend `CraftCms\Cms\Plugin\PluginSettings` rather than `craft\base\Model`.
- Plugin settings are now validated using Laravel validation rules returned by `CraftCms\Cms\Plugin\PluginSettings::getRules()`, rather than Yii validation rules returned by `rules()`. Validation errors are added via the `$fail` callback or `errors()`, rather than `addError()`.
- Plugin settings are now persisted based on the settings model’s public properties, via `CraftCms\Cms\Plugin\PluginSettings::configData()`. Settings stored in private properties with getter and setter methods must be redeclared as public properties, or included by overriding `configData()`.
- Removed the `collapsible` attribute, the reflected `collapsed` attribute, and the `close()` and `open()` methods from `<craft-tabs>`, and `CraftCms\Cms\Cp\Components\Tabs::collapsible()`. Panels that can all be closed should use `<craft-disclosure>` instead.
- Deprecated `Cp.$elementDetailsTabs`, its `tabs` property, and the `ElementDetailsTab*` types. `Cp.$elementDetailsPanels`, its `panels` property, and the `ElementDetailsPanel*` types should be used instead.
- Removed the `data` and `methods` blocks from the `_includes/forms/autosuggest.twig` template, along with the Vue instance they configured. Templates that overrode them should pass `suggestions` instead.
- Removed `CraftCms\Cms\Plugin\Contracts\PluginInterface::createSettingsModel()`. The static `createSettings()` must be implemented instead. (`createSettingsModel()` remains supported for plugins extending `craft\base\Plugin` through the Yii adapter.) ([#19574](https://github.com/craftcms/cms/pull/19574))
- Renamed `CraftCms\Cms\Form` to `CraftCms\Cms\Ui`, along with its `Form` classes and methods, and the JavaScript `forms` module and its definition APIs to `ui`, including payload fields and refresh endpoints. ([#19882](https://github.com/craftcms/cms/pull/19882))
- Moved the shared control panel table code (`useCraftTable`, `createCraftColumnHelper`, `useServerPagination`, `useServerSort`, and `useEditableTable`) from `modules/admin-table` to `common/table`. ([#19897](https://github.com/craftcms/cms/pull/19897))
- Added `CraftCms\Cms\Ui\Nodes\Scope` for embedding component settings under a path prefix, with optional shared delta tracking. ([#19914](https://github.com/craftcms/cms/pull/19914))
- `CraftCms\Cms\Field\Contracts\TableCellInterface::serializeValue()` can now return arrays. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added `CraftCms\Cms\Field\Contracts\ElementAwareTableCellInterface`, for Table cell types that reference elements. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added `CraftCms\Cms\Field\LinkResolver`, which normalizes, validates, and localizes link values for a set of link settings. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added `CraftCms\Cms\Ui\Controls\Link::compact()`, which shows a link summary with a button that edits the link in a popover. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added `CraftCms\Cms\Ui\Controls\Link::registeredTypes()`, which locks links whose type is no longer available. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added support for hiding editable table rows while preserving their inputs and submitted values, using `CraftCms\Cms\Ui\Controls\Table::hiddenRows()` or legacy `_hidden` row values. ([#19939](https://github.com/craftcms/cms/pull/19939))
- Added the `<craft-input-search>` web component. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `<craft-checkbox-select>` web component, which combines checkbox selects and sortable checkbox selects. It has an “All” option, independent `sortable` and `presentation="add-menu"` options (the latter lists only the checked options, with the rest in an “Add item” menu), and an `options` property along with `addOption()`, `updateOption()`, and `removeOption()` methods for changing its options at runtime. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added `CraftCms\Cms\Cp\Components\CheckboxSelect::allOption()`, `label()`, and `presentation()`, and `CraftCms\Cms\Cp\Enums\CheckboxSelectPresentation`. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added `CraftCms\Cms\Ui\Enums\ChoicePresentation::AddMenu`. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added the `presentation` config option to the `forms.checkboxSelect` and `forms.checkboxSelectField` macros, `CraftCms\Cms\Cp\FormFields::checkboxSelectFromConfig()`, and `Craft.ui.createCheckboxSelect()`. Sortable checkbox selects still list only the checked options with an “Add item” menu, unless it's set to `checkboxes`. ([#20036](https://github.com/craftcms/cms/pull/20036))
- `CraftCms\Cms\Cp\Components\CheckboxSelect` now renders a `<craft-checkbox-select>`. While its “All” option is checked, the other options are left checked but unnamed, rather than disabled. ([#20036](https://github.com/craftcms/cms/pull/20036))
- `CraftCms\Cms\Ui\Controls\Choice::sortable()` no longer implies the “Add item” menu, and can now be combined with `allOption()`. `presentation(ChoicePresentation::AddMenu)` should be used for the menu. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added `CraftCms\Cms\Ui\Controls\Choice::allowCustom()` and `iconsOnly()`, and the `color` and `custom` option keys. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Added `CraftCms\Cms\Cp\FormFields::customCheckboxTemplate()`. ([#20166](https://github.com/craftcms/cms/pull/20166))
- `<craft-combobox>` now shows selected options’ icons and indicators on `multiple-choice` tokens, and tints option icons with their `color` data. ([#20166](https://github.com/craftcms/cms/pull/20166))
- Deprecated `CraftCms\Cms\Cp\Components\CheckboxSelect::storageKey()`, which now does nothing. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Deprecated `Craft.SortableCheckboxSelect`. `<craft-checkbox-select>` should be used instead. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Deprecated `CraftCms\Cms\Element\DeletionBlockers\RelationDeletionBlocker::$elementIndexSettings`, which no longer has any effect.
- Removed `Craft.UserPhotoInput`. `craft\web\assets\userphoto\UserPhotoAsset` is now deprecated and does nothing. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `Craft.openAssetImageEditor()`, which opens the image editor dialog for an asset ID or its editor settings. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Deprecated `Craft.AssetImageEditor`. `Craft.openAssetImageEditor()` should be used instead. Constructing it now opens the image editor dialog, and only its `onSave` setting is supported. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The `assets/image-editor` action now returns the image editor dialog’s settings, rather than the legacy editor’s HTML. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The control panel no longer loads Fabric.js. `craft\web\assets\fabric\FabricAsset` is now deprecated and doesn’t register Fabric.js, so plugins that rely on it should bundle it themselves. ([#20085](https://github.com/craftcms/cms/pull/20085))
- `Craft.PreviewFileModal` and `Craft.CpModal` are now built on `<craft-dialog>` rather than `Garnish.Modal`. `Craft.PreviewFileModal`’s `$container` is now the jQuery-wrapped element holding the preview, and `Craft.CpModal` accepts a `title` setting. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed `Craft.ElementEditor::createEditMetaAction()`, `showMetaModal()`, `createMetaModal()`, `checkMetaValues()`, `shakeMetaModal()`, `saveMeta()`, and `showFieldCopyModal()`, which were no longer reachable. Cross-site value copying is handled by the field’s own action menu. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed `Craft.AuthManager` and `Craft.cp.authManager`, which no longer had a remaining session time to act on. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed the `users/login-modal` action and the `_special/login-modal` template. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed the unused `images/thumbs/file.svg` and `images/thumbs/folder.svg` control panel resources. `CraftCms\Cms\Asset\AssetsHelper::iconSvg()` and `folderIconSvg()` should be used instead. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Labeled `@craftcms/ui` form controls (`<craft-input>`, `<craft-textarea>`, `<craft-select>`, `<craft-combobox>`, etc.) now lay out their label and help text the same way `<craft-field>` does, and support `tip` and `warning` slots. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added the `control` CSS part to `<craft-action-item>`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- `Craft.createElementEditor()` now opens the element editor in a Vue slideout on control panel pages that have them, rather than in the legacy `Craft.ElementEditorSlideout`. It returns an object with the legacy slideout’s `submit`, `load` and `close` events, `close()` method, and `elementEditor` `update` event. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Added `CraftCms\Cms\Auth\SessionTimeout`. ([#20086](https://github.com/craftcms/cms/pull/20086))
- Control panel responses that extend a signed-in user’s session now include an `X-Craft-Session-Expires` header with the session’s expiry, as a Unix time. ([#20086](https://github.com/craftcms/cms/pull/20086))
- The `users/session-info` action now returns `sessionExpiresAt`. ([#20086](https://github.com/craftcms/cms/pull/20086))
- Added the `users/renew-session` and `users/login-methods` actions. ([#20086](https://github.com/craftcms/cms/pull/20086))
- The `elements/recent-activity` and `queue/get-job-info` actions no longer extend the session, and the `dontExtendSession` parameter is no longer used. ([#20086](https://github.com/craftcms/cms/pull/20086))
- Removed `CraftCms\Cms\Cp\Components\CheckboxSelect::allCheckbox()`. `allOption()` should be used instead. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Removed the `<craft-sortable-checkbox-select>` web component. `<craft-checkbox-select>` should be used instead. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added the `<craft-resize-grips>` web component. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Added the `resizable` property to `<craft-dialog>`. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Added the `--c-icon-size` CSS custom property, which sets the size of `<craft-icon>` icons, including ones nested inside slotted content. ([#20106](https://github.com/craftcms/cms/pull/20106))
- Added the `<craft-link>` web component, which shows a link on one line with a details button, and the `--c-link-max-width` CSS custom property, which caps its width. Table cells cap links at 200px by default. ([#20123](https://github.com/craftcms/cms/pull/20123))
- Links to control panel pages in plugins’ server-rendered HTML now open with Inertia. Pages that aren’t Inertia pages still load in full, after an Inertia request. Links to files or other responses that aren’t HTML need `download` or `data-inertia="false"`, on the link or an element around it, as Inertia can’t open them. ([#20118](https://github.com/craftcms/cms/pull/20118))
- Added the `inline-suffix` attribute to `<craft-input>`, and `CraftCms\Cms\Cp\Components\Input::inlineSuffix()`, which draw suffix buttons within the input’s border. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `suffix` slot to `<craft-tab>`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `--c-callout-icon-padding-inline` and `--c-callout-text-padding-inline` CSS custom properties to `<craft-callout>`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `--c-field-label-font-size`, `--c-field-label-font-weight`, `--c-field-label-color`, and `--c-field-label-line-height` CSS custom properties to `<craft-field>`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `--c-slide-picker-segment-fill`, `--c-slide-picker-segment-active-fill`, and `--c-slide-picker-segment-hover-fill` CSS custom properties to `<craft-slide-picker>`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `--c-thumbnail-height` CSS custom property to `<craft-thumbnail>`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `thumb-size` attribute to `<craft-chip>`, which sizes its thumbnail independently of the chip (`sm`, `md`, `lg`, `xl`, or `2xl`). Chips without a `size` now take the size that suits their `thumb-size`. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Added the `prefix-placement` attribute to `<craft-chip>`. Set to `block-start`, a chip stacks its thumbnail or icon above its label, with its checkbox and suffix along the top. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Added the `select-disabled` attribute to `<craft-chip>`. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Added `CraftCms\Cms\Cp\Enums\ChipThumbSize` and `CraftCms\Cms\Cp\Html\ElementHtml::CHIP_SIZE_MEDIUM`. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Added the `thumbSize` config option to `CraftCms\Cms\Cp\Html\ElementHtml::chipHtml()` and `elementChipHtml()`. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Added `CraftCms\Cms\Ui\Nodes\Group::outlet()`, which renders a group in a named outlet elsewhere in the form when the form is rendered by Vue, such as the field layout designer’s Appearance tab (`field-layout-designer.appearance`). ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added the `labelSrOnly` config option to `CraftCms\Cms\Cp\FormFields::fieldHtml()`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- The `--c-size-control-md` and `--c-size-touch-target` CSS custom properties are now 32px. ([#20001](https://github.com/craftcms/cms/pull/20001))
- UI payload errors that are reassigned to a control’s path now include the path they were raised at, as `source`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- UI table columns’ `info` is now rendered as Markdown, and passed to the client as `infoHtml`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Element select chips’ payloads now include the element’s `color`. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Added `CraftCms\Cms\FieldLayout\Enums\TabType`. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added `CraftCms\Cms\FieldLayout\FieldLayoutTab::$icon` and `$type`. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added `CraftCms\Cms\Ui\Nodes\Tab::icon()` and `sidebar()`, and `$icon` and `$sidebar` arguments to `CraftCms\Cms\Ui\Ui::addTab()`. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added `CraftCms\Cms\Cp\Components\Tab::icon()`. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added a `prefix` slot to `<craft-tab>`. ([#20005](https://github.com/craftcms/cms/pull/20005))
- The `capitalize`, `lower`, `title`, and `upper` Twig filters now have `language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- Improved Twig attribute access on Eloquent models to resolve missing and null attributes, casts, accessors, and relations through Eloquent property access, while preserving explicit method calls. ([#20165](https://github.com/craftcms/cms/pull/20165))
- Added the `craft:project-config:check` command, which checks project config schema compatibility without applying changes.
- Added the `{% render %}` Twig tag. `{% render %}` tags and `render()` methods are allowed within the Twig sandbox. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `CraftCms\Cms\Component\Contracts\Renderable`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `CraftCms\Cms\Support\Template::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `Illuminate\Support\Collection` now has a `render()` macro. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Addresses now support drafts.
- Element fixtures no longer create revisions by default. Set their `createRevisions` property to `true` to retain the previous behavior. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `CraftCms\Cms\Element\Element::showSitesField()`, `sitesFieldHtml()`, and `sitesNodes()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `CraftCms\Cms\Ui\Controls\ElementSites`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `CraftCms\Cms\Ui\Controls\ElementSelect::presentation()`, which returns the data an element select control draws an element from.
- Added `CraftCms\Cms\Field\Field::$tip` and `$warning`.
- Added `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::defaultTip()` and `defaultWarning()`.
- Nested elements now have a `$touchOwnersOnSave` property.
- Added `CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements::mustHaveField()` and `mustHaveOwner()`.
- Added `CraftCms\Cms\Gql\Queries\Settings`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `CraftCms\Cms\Gql\Types\EntryType`, `Section`, `Site`, `UserGroup`, and `Volume`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `CraftCms\Cms\Translation\Locale::languageId()`.
- Added `CraftCms\Cms\Asset\Data\Volume::$propagationMethod`. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Added `CraftCms\Cms\Asset\Assets::findAcrossSites()`. ([#20060](https://github.com/craftcms/cms/pull/20060))
- `CraftCms\Cms\Asset\Assets::getAssetById()` now returns the asset in another site if it doesn’t exist in the requested site, when no site ID is passed. ([#20060](https://github.com/craftcms/cms/pull/20060))
- `CraftCms\Cms\Element\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `CraftCms\Cms\Support\Str::lower()`, `::title()`, and `::upper()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `elements/save-draft` action responses now include a `canDeleteForSite` key. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `CraftCms\Cms\Plugin\Plugins::hasPluginIcon()`. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Added `CraftCms\Cms\Ui\Controls\Textarea::singleLine()`. ([#20074](https://github.com/craftcms/cms/pull/20074))
- Added `craft\helpers\Db::isSafeOrderBy()`. ([#19994](https://github.com/craftcms/cms/pull/19994))
- Control panel elements with a `data-icon` attribute, such as those in plugins’ templates and scripts, now show the matching SVG icon via `<craft-icon>` when `craftcms/yii2-adapter` is installed, since the legacy icon font is no longer loaded. Core control panel markup uses `<craft-icon>` instead. ([#20179](https://github.com/craftcms/cms/pull/20179))
- Fixed a bug where `CraftCms\Cms\Plugin\Testing\PluginTestCase` could install plugins twice when workbench project config existed. ([#20177](https://github.com/craftcms/cms/pull/20177))
- Fixed a bug where plugin tests could overwrite or delete Composer’s plugin manifest. ([#20177](https://github.com/craftcms/cms/pull/20177))
- Fixed a bug where plugins’ named routes were unavailable in plugin tests. ([#20177](https://github.com/craftcms/cms/pull/20177))
- Fixed a bug where Craft installation could fail to create an admin user when the default SQLite connection changed after boot. ([#20177](https://github.com/craftcms/cms/pull/20177))
- Fixed a bug where plugin test suites extending `CraftCms\Cms\Plugin\Testing\PluginTestCase` failed without additional service providers. ([#20169](https://github.com/craftcms/cms/pull/20169))
- Fixed a bug where plugin control panel routes and control panel action routes did not require authentication and the `accessCp` permission. ([#20164](https://github.com/craftcms/cms/pull/20164))

### System

- An element editor or queue indicator left open no longer keeps the user’s session from expiring. ([#20086](https://github.com/craftcms/cms/pull/20086))
- MySQL database backups and restores now carry over SSL/TLS settings from the system’s MySQL option files (e.g. `ssl-ca` or `disable-ssl-verify-server-cert`), if the database connection doesn’t have any `PDO::MYSQL_ATTR_SSL_*` attributes set. ([#19366](https://github.com/craftcms/cms/pull/19366))
- MySQL database backups and restores now respect the `PDO::MYSQL_ATTR_SSL_CAPATH` and `PDO::MYSQL_ATTR_SSL_CIPHER` database connection attributes. ([#19366](https://github.com/craftcms/cms/pull/19366))
- Craft can now determine the dimensions of BMP and JPEG XL images on remote volumes without downloading them.
- Saving nested elements on multi-site installs now executes fewer database queries. ([#20090](https://github.com/craftcms/cms/pull/20090))
- User permission checks are now faster for users with many permissions. ([#20149](https://github.com/craftcms/cms/pull/20149))
- Requests now read from the database’s write connection for two seconds after the same session writes data, in addition to POST requests. ([#20122](https://github.com/craftcms/cms/pull/20122))
- Craft now requires Laravel 13.38 or later. ([#20122](https://github.com/craftcms/cms/pull/20122))
- Removed support for the non-standard `ED256` passkey algorithm. ([#19701](https://github.com/craftcms/cms/pull/19701))
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Fixed a bug where eager-loading relation fields with “Maintain hierarchy” enabled logged an `orderBy()` deprecation warning. ([#20197](https://github.com/craftcms/cms/pull/20197))
- Fixed an error that occurred when sending system messages on sites upgraded from Craft 5 whose email settings had an empty “HTML Email Template” value. ([#20194](https://github.com/craftcms/cms/pull/20194))
- Fixed an error that prevented the control panel from loading for users with an unread announcement migrated from Craft 5, such as a plugin’s “What’s new” announcement. ([#20195](https://github.com/craftcms/cms/pull/20195))
- Fixed an error that occurred when Craft 5 templates called `setTransform()` on assets returned by Assets fields or asset queries with the Yii adapter installed. The transform now applies to the asset’s URL. ([#20191](https://github.com/craftcms/cms/pull/20191))
- Fixed a bug where numeric custom field comparisons and sorting, Lightswitch field queries, and Date field queries could return incorrect results on SQLite. ([#20167](https://github.com/craftcms/cms/pull/20167))
- Fixed errors in empty and negated custom field queries on MySQL caused by treating SQL expressions as JSON column selectors. ([#20167](https://github.com/craftcms/cms/pull/20167))
- Fixed a bug where custom fields declaring small integer, big integer, float, or double database types were compared and sorted as text on PostgreSQL. ([#20172](https://github.com/craftcms/cms/pull/20172))
- Fixed incorrect JSON paths when querying Date fields and other custom fields with multiple stored values. ([#20172](https://github.com/craftcms/cms/pull/20172))
- Fixed an error when ordering element queries by custom field handles. ([#20170](https://github.com/craftcms/cms/pull/20170))
- Fixed an error when ordering element queries by `RAND()` or `random()` on MySQL and PostgreSQL. Other SQL expressions passed to `orderBy()`, `select()`, or `groupBy()` as strings now throw an exception explaining how to pass them with `expression()` or `DB::raw()`, rather than failing as unknown columns. ([#20190](https://github.com/craftcms/cms/pull/20190))
- Fixed a bug where element query date params, such as `postDate`, `before`, `after`, `dateCreated`, and Date field params, as well as date condition rules, were off by the system time zone’s UTC offset. ([#19963](https://github.com/craftcms/cms/pull/19963))
- Fixed a bug where many dates Craft stores, such as elements’ soft-delete dates and sites’, plugins’, relations’, and activity events’ dates, were stored in the system time zone rather than UTC. ([#20053](https://github.com/craftcms/cms/pull/20053))
- Fixed a bug where revisions were created for elements that hadn’t changed since their last revision, including nested entries that hadn’t changed between their owner’s revisions, if the system time zone wasn’t UTC. ([#20053](https://github.com/craftcms/cms/pull/20053))
- Fixed a bug where dates more than six days out, such as pending entries’ post dates in element indexes, were shown as a day of the week. ([#19972](https://github.com/craftcms/cms/pull/19972))
- Fixed a bug where nested elements weren’t indented in structure views’ element index tables. ([#19972](https://github.com/craftcms/cms/pull/19972))
- Fixed a bug where users’ Permissions, Preferences, and Password screens had two “Save and continue editing” actions. ([#19972](https://github.com/craftcms/cms/pull/19972))
- Fixed a bug where some elements’ action menu items, such as “Section settings”, “Entry type settings”, and “Field settings”, didn’t open their slideouts on some control panel pages. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Fixed a bug where assets’ “Volume settings” and user groups’ “User group settings” action menu items led to 404 errors. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Fixed a bug where saving an image as a new asset, from an asset opened via an Assets field, didn’t replace the asset in the field. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Fixed a bug where field layout designers’ field library didn’t reflect changes saved from a field’s settings slideout. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Fixed a bug where native fields, such as assets’ Alternative Text field, were missing from field layout designers’ field library. ([#20136](https://github.com/craftcms/cms/pull/20136))
- Fixed a bug where errors added with `Craft.ui.addErrorsToField()` weren’t shown. ([#19961](https://github.com/craftcms/cms/pull/19961))
- Fixed a bug where black cards’ body text had insufficient contrast. ([#19989](https://github.com/craftcms/cms/pull/19989))
- Fixed a bug where thumbnails in relational fields linked to their elements. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where comboboxes didn’t show the selected option’s status indicator while closed.
- Fixed a bug where some focus rings were clipped within editable tables. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where fields’ “Used by” metadata listed asset volumes’ field layouts as unlinked counts, and showed oversized icons. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where the Notification Position and Slideout Position preferences had large gaps between their labels and buttons, and their button groups had no accessible names. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where clicking the selected button in a single-choice button group again could make it look deselected. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where the “Save” button in element editors kept the user on the edit page instead of returning to the element index. ([#19970](https://github.com/craftcms/cms/pull/19970))
- Fixed a bug where page-header actions could be clipped on narrow screens. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Fixed a bug where the public GraphQL schema could be deleted through its numeric URL. ([#19935](https://github.com/craftcms/cms/pull/19935))
- Fixed a bug where the System Messages utility’s language menu was empty. ([#19946](https://github.com/craftcms/cms/pull/19946))
- Fixed a bug where variables weren’t HTML-escaped in system messages’ HTML bodies, because Twig string and object templates reused whichever escaping they were first compiled with. The default system messages no longer use the `|e` filter, which showed HTML entities in their plain-text bodies. ([#19946](https://github.com/craftcms/cms/pull/19946))
- Fixed a bug where new nested elements, such as Commerce product variants or entries in Matrix fields set to the element index view mode, could be deleted by the owner element’s autosave before they were saved. ([#19943](https://github.com/craftcms/cms/pull/19943))
- Fixed a bug where nested element indexes with a single “New” option showed it as a menu containing that one item, rather than a button, when there wasn’t room for their create buttons. ([#19944](https://github.com/craftcms/cms/pull/19944))
- Fixed a bug where sorting the Sections, Fields, or Entry Types settings tables from a later page showed the first page’s rows with the later page’s pagination. ([#19912](https://github.com/craftcms/cms/issues/19912))
- Fixed a bug where replacing references in Link fields could replace links to other elements, and discarded the links’ labels and attributes. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Fixed an error that could occur after upgrading to Craft 6 when Laravel queue or other supporting database tables were missing. ([#19889](https://github.com/craftcms/cms/pull/19889))
- Fixed a bug where failed queue jobs weren’t removed when releasing all jobs. ([#19969](https://github.com/craftcms/cms/pull/19969))
- Fixed a bug where new control panel assets weren’t getting published when updating via the control panel or `craft:update` command.
- Fixed an error when rendering the Craft 5 starter homepage with the Yii adapter installed. ([#19917](https://github.com/craftcms/cms/pull/19917)
- Fixed a bug where fields in element editors didn’t receive their text direction or translation settings. ([#19915](https://github.com/craftcms/cms/pull/19915))
- Fixed a bug where element editor fields didn’t preserve field grouping, static presentation settings, or suppression of modification indicators. ([#19915](https://github.com/craftcms/cms/pull/19915))
- Fixed an error that occurred when a Craft 5 plugin registered a utility, which no longer satisfied `CraftCms\Cms\Utility\UtilityTypes`’ contract. ([#19852](https://github.com/craftcms/cms/pull/19852))
- Fixed an error that occurred when registering asset bundles that depend on `CraftCms\Cms\View\LegacyAssets\HtmxAsset`. The bundle is now deprecated and does nothing.
- Fixed a bug where the tab strip wasn’t guttered with the content it sits above. ([#19861](https://github.com/craftcms/cms/pull/19861))
- Fixed a bug where an editable table that wasn’t visible when the page loaded didn’t initialize until the window was resized.
- Fixed a bug where environment variable, alias, and template suggestions weren’t sorted alphabetically.
- Fixed a bug where status indicators for disabled elements were filled in rather than outlined, and custom status colors were shown as gray. ([#19892](https://github.com/craftcms/cms/pull/19892))
- Fixed a bug where admin tables no longer had selectable rows. ([#19893](https://github.com/craftcms/cms/pull/19893))
- Fixed a bug where the license enforcement screen wasn’t showing the “Continue to the control panel” link after the countdown ended. ([#19895](https://github.com/craftcms/cms/issues/19895))
- Fixed a bug where the “Underline links” accessibility preference wasn’t respected on most control panel screens. ([#19905](https://github.com/craftcms/cms/pull/19905))
- Fixed a bug where section edit slideouts didn’t have “Open in a new tab” buttons.
- Fixed a bug where the Activity details panel didn’t scroll to the newest activity when opened. ([#19923](https://github.com/craftcms/cms/pull/19923))
- Fixed a bug where cards with `data-color="white"` had translucent bodies that didn’t match their headers. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where control panel nav flyouts could close as soon as the pointer reached them. ([#19926](https://github.com/craftcms/cms/pull/19926))
- Fixed a bug where action menus on thumbnails in single-element relation fields were positioned at the start of the thumbnail rather than the end.
- Fixed a bug where element chips within links weren’t following the link underline behavior or the “Underline links” accessibility preference.
- Fixed a bug where site Inertia middleware registered in `craft.web` could break control panel authentication pages.
- Fixed a bug where authenticated control panel routes didn’t invalidate sessions with stale password hashes.
- Fixed a bug where asset folders could be selected in asset selection modals. ([#19937](https://github.com/craftcms/cms/pull/19937))
- Fixed a bug where asset folders in asset selection modals couldn’t be opened. Folders are now linked, and clicking one shows its contents in the modal, with breadcrumbs leading back to the volume’s root. ([#19937](https://github.com/craftcms/cms/pull/19937))
- Fixed a bug where `yii\db\Expression` values assigned to legacy Active Record attributes were saved as JSON strings. ([#19948](https://github.com/craftcms/cms/issues/19948), [#19953](https://github.com/craftcms/cms/pull/19953))
- Fixed a bug where the “Users” nav item was marked as the current page when viewing your own account or a user’s edit screen. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where the “Preview file”, “Download”, “Rename file”, “Replace file”, “Copy URL”, “Copy reference tag”, “Show in folder”, “Edit Image”, and “Move…” actions in asset indexes’ Actions menu didn’t do anything, and cleared the selection instead. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where the folder picker for moving assets and folders was unstyled, and offered “Temporary Uploads” as a destination when moving folders. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed a bug where element indexes and element selector modals could show empty cards or thumbnails when the view mode they remembered differed from the one they loaded with. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed an error that occurred when a `selectize` or `booleanMenu` form field was passed a boolean or numeric value.
- Fixed a bug where element edit pages’ and slideouts’ details panes didn’t scroll when their content was too tall to fit. ([#19952](https://github.com/craftcms/cms/pull/19952))
- Fixed a bug where pages with a secondary nav sidebar, such as Commerce → Store Management → General, showed an empty actions menu below the nav. ([#19959](https://github.com/craftcms/cms/pull/19959))
- Fixed a bug where double-clicking an element in an element selection modal didn’t select it. ([#19979](https://github.com/craftcms/cms/pull/19979))
- Fixed a bug where the MCP server’s `elements.create`, `elements.update`, and `elements.validate` tools silently ignored Matrix entries submitted in the format `elements.field-schema` described. ([#19965](https://github.com/craftcms/cms/pull/19965))
- Fixed a bug where the MCP server’s upload session endpoints couldn’t find upload sessions when a control panel trigger was set. ([#20080](https://github.com/craftcms/cms/pull/20080))
- Fixed a bug where updating a Matrix entry with a GraphQL mutation or the MCP server cleared its title when no title was given. ([#20091](https://github.com/craftcms/cms/pull/20091))
- Fixed a bug where the MCP server was unavailable during maintenance mode, even to users who can access the control panel while the system is off. ([#20095](https://github.com/craftcms/cms/pull/20095))
- Fixed a bug where asset indexes didn’t offer to undo moving assets or folders. ([#19985](https://github.com/craftcms/cms/pull/19985)))
- Fixed a bug where the “License purchase required” screen’s Refresh button didn’t work.
- Fixed a bug where link buttons with an icon and a label had no inline padding.
- Fixed a bug where the Deprecation Warnings utility listed warnings caused by Craft’s own Yii adapter code, rather than by project or plugin code. ([#19995](https://github.com/craftcms/cms/pull/19995))
- Fixed a bug where the GraphQL `orderBy` argument’s sort direction was ignored. ([#19996](https://github.com/craftcms/cms/pull/19996))
- Fixed a bug where entry index pages whose names weren’t written in plain Latin letters, such as “Αναρτήσεις” or “Événements”, opened an empty index rather than their first source. ([#20058](https://github.com/craftcms/cms/issues/20058))
- Fixed an error that occurred when executing GraphQL queries with schemas that could query categories, tags, or global sets.
- Fixed a bug where custom `orderBy` params on relational field values, such as `entry.myEntriesField.orderBy('title')`, didn’t take precedence over the field’s related order. ([#19997](https://github.com/craftcms/cms/pull/19997))
- Fixed a bug where action menu items could overflow their menu, such as user groups’ descriptions in the user permissions “Choose” menu. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where tables without body rows had a double border below their header, and header cells’ backgrounds extended past the table’s rounded corners. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where the card view designer’s Thumbnail Alignment buttons didn’t change the alignment. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where selected buttons showed hover effects. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where field layout element settings slideouts weren’t padded. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where chips’ statuses, such as the draft icon, took the chip’s color. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed an error that occurred when rendering Number fields’ previews without a preview format. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Fixed a bug where `{placeholder}` parameters weren’t replaced in messages from translation categories without translations, such as Yii’s console error messages.
- Fixed a bug where control panel screens that hadn’t been ported to Vue, such as plugins’ Twig-rendered pages, kept reloading when an element source had “Show count” enabled. ([#20082](https://github.com/craftcms/cms/pull/20082))
- Uploads staged on an S3 disk are now copied into their volume within S3, rather than downloaded and uploaded again, when the volume’s disk uses the same credentials and endpoint. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Fixed a bug where `withTransforms` criteria were ignored for eager-loaded assets. ([#20117](https://github.com/craftcms/cms/pull/20117))
- Fixed errors that occurred when running the legacy `queue` console commands with the Yii adapter installed, such as `php craft queue:run` and `php craft queue:info --no-interaction`. ([#20162](https://github.com/craftcms/cms/pull/20162))
- Fixed a bug where Composer warned about ambiguous class resolution for `Legacy*` traits when `craftcms/yii2-adapter` was installed. ([#20161](https://github.com/craftcms/cms/pull/20161))
- Fixed a bug where the lists in `config/craft/twig-sandbox.php` replaced Craft’s default Twig sandbox allowlists rather than extending them, which broke system emails for projects upgraded from Craft 5. ([#20159](https://github.com/craftcms/cms/pull/20159))
- Fixed a bug where front-end forms with an empty `redirectInput('')` redirected to the primary site rather than the current site’s home page, such as after logging in on a site with a base path like `/fr`. ([#20174](https://github.com/craftcms/cms/pull/20174))
- Fixed a bug where front-end requests posted with an `action` param, and token routes, ran on the primary site rather than the requested site, when the site had a base path. ([#20174](https://github.com/craftcms/cms/pull/20174))
- Fixed a 404 error that occurred when posting to an action URL on a site with a base path, such as `/fr/actions/users/login`. ([#20174](https://github.com/craftcms/cms/pull/20174))
- The database table prefix is now set with the `DB_PREFIX` environment variable. The installer saved it to `DB_TABLE_PREFIX`, and now also appends an underscore, like the `craft:setup:db-creds` command. ([#20192](https://github.com/craftcms/cms/pull/20192))
- When `craftcms/yii2-adapter` is installed and no table prefix is configured, the `CRAFT_DB_TABLE_PREFIX` and `DB_TABLE_PREFIX` environment variables are applied with a trailing underscore, like Craft 5. ([#20192](https://github.com/craftcms/cms/pull/20192))
- Fixed a bug where Craft couldn’t boot when the database connection had a table prefix, because queries that aliased tables didn’t apply the prefix. ([#20192](https://github.com/craftcms/cms/pull/20192))
- Fixed a bug where orphaned foreign key rows weren’t deleted during garbage collection when the database connection had a table prefix. ([#20192](https://github.com/craftcms/cms/pull/20192))
- Fixed an error that occurred when running `craft:up` on PostgreSQL sites that were first installed on Craft 3 or 4. ([#20188](https://github.com/craftcms/cms/pull/20188))
- Fixed an error that occurred when running `craft:up` or loading entry types on sites upgraded from Craft 5, if an entry type had an empty title or slug translation method. ([#20187](https://github.com/craftcms/cms/pull/20187))

## 6.0.0-alpha.20 - 2026-10-07

### Content Management

- Made several minor UI refinements. ([#19887](https://github.com/craftcms/cms/pull/19887), [#19839](https://github.com/craftcms/cms/pull/19839), [#19842](https://github.com/craftcms/cms/pull/19842), [#19847](https://github.com/craftcms/cms/pull/19847), [#19871](https://github.com/craftcms/cms/pull/19871))
- Added the full-page Save menu to slideouts with Vue forms, including “Save and continue editing” and any additional actions and buttons. ([#19854](https://github.com/craftcms/cms/pull/19854))
- Improved consistency of control panel breadcrumbs. ([#19786](https://github.com/craftcms/cms/pull/19786))
- Refined status badges and status indicators. Labeled badges now match the height of small controls, and their status indicators are vertically centered and no longer outlined. ([#19842](https://github.com/craftcms/cms/pull/19842))
- Updated entries’ Post Date and Expiry Date fields to span the full width of the sidebar. Their inputs no longer shift when the clear button appears. ([#19871](https://github.com/craftcms/cms/pull/19871))
- Brought back users’ gradient-based default avatars.
- Updated nested element fields in HTML forms, including Global Set content editors, to use the shared cards and element index controls. ([#19804](https://github.com/craftcms/cms/pull/19804))
- Updated Matrix fields using the Blocks view mode in HTML forms to use the shared Vue control. ([#19832](https://github.com/craftcms/cms/pull/19832))
- Updated generated fields in the Field Layout Designer and Table field inputs in HTML forms to use form-builder tables. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Updated the user Addresses screen to use the shared nested element manager, including duplicating, deleting, and the element index view for users with many addresses. ([#19792](https://github.com/craftcms/cms/pull/19792))

### Accessibility

- Improved the accessibility of element index and edit screens. ([#19785](https://github.com/craftcms/cms/pull/19785))
- Fixed a bug where searching or clearing a search on element indexes moved keyboard focus away from the search input. ([#19785](https://github.com/craftcms/cms/pull/19785))
- Fixed an accessibility issue where `craft-button` links couldn’t be reached with the <kbd>Tab</kbd> key. ([#19785](https://github.com/craftcms/cms/pull/19785))
- Fixed an accessibility issue where the control panel’s skip links were hidden behind the header bar when focused. ([#19886](https://github.com/craftcms/cms/pull/19886))
- Fixed an accessibility issue where switches in editable tables weren’t labeled by their column headers. ([#19886](https://github.com/craftcms/cms/pull/19886))
- Fixed a bug where editable tables on the same page could label each other’s inputs, or move focus to another table’s column header when sorting. ([#19886](https://github.com/craftcms/cms/pull/19886))
- Fixed an accessibility issue where checkboxes and select menus in form-builder table cells were named twice for screen readers, and checkboxes showed their label beside them. ([#19890](https://github.com/craftcms/cms/pull/19890))
- Button Group fields, single-choice button controls, and the Notification Position and Slideout Position preferences are now radio groups drawn as buttons, so they’re announced as radios and their selection can be changed with the arrow keys. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Single-choice button groups are now announced as groups of toggle buttons rather than radio groups. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Fixed an accessibility issue where element indexes’ view mode and sort direction buttons weren’t in named groups. ([#19942](https://github.com/craftcms/cms/pull/19942))

### Administration

- Added authenticated and configurable public Model Context Protocol (MCP) servers for inspecting and managing Craft sites, content, assets, users, plugins, and project config. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added `elements.*` MCP tools that list, read, create, update, delete, restore, validate, and duplicate elements of any supported type, with `elements.schema` and `craft://element-types` resources describing each type’s criteria and attributes. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added MCP tools for restoring entries, assets, users, and addresses, validating saved state or proposed changes without saving, and duplicating entries and addresses. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added `activity.list` and `activity.comments.create` MCP tools for reading element timelines and adding editorial comments. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added MCP origin attribution to activity timelines, including activity generated by queued work initiated through MCP. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added the `mcp:serve` command, which serves the authenticated MCP server over stdio as a named user. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added admin MCP server instructions, the `CraftCms\Cms\Config\McpConfig::$instructions` setting, and the `CraftCms\Cms\Mcp\Events\CollectingAdminInstructions` event for site and plugin guidance. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Updated admin MCP instructions and tool schemas with separate read and edit sequences, precise draft identifiers, and task-specific workflow and safety guidance. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added pagination continuation metadata to the `elements.list`, `drafts.list`, `revisions.list`, and `search.query` MCP tools. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Updated section edit pages to use the form builder for their entry types, site settings, and preview targets. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Updated component selects, such as entry type selects, to use `<craft-component-select>`, keeping selected entry types listed as checked options. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Added support for `money` columns and grouped options in legacy editable tables and Form tables, including a configurable Money cell type for Table fields. ([#19870](https://github.com/craftcms/cms/pull/19870))

### Extensibility

- Added the `buttons` attribute to `<craft-radio-group>`, and `CraftCms\Cms\Cp\Components\RadioGroup::buttons()`, which show a radio group’s options as a row of joined buttons. ([#19942](https://github.com/craftcms/cms/pull/19942))
- Added `CraftCms\Cms\Form\Controls\NestedElements` and `CraftCms\Cms\Element\NestedElementManager::formControl()`, allowing plugins to manage custom nested element types as cards or embedded element indexes. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Unified entry edit pages and nested element slideouts under the generic element editor, with a default `CraftCms\Cms\Http\ViewModels\ElementEditViewModel` for custom element types. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Added the `craft:layout-slot`, `craft:app-layout`, and `craft:cp-container` global Vue components, which plugins can use to fill control panel screen layout regions on their own Inertia pages. ([#19854](https://github.com/craftcms/cms/pull/19854))
- Added the `primary-action` screen layout slot and `CraftCms\Cms\Http\Responses\CpScreenResponse::primaryAction()`, for replacing the Save button while keeping its action menu. ([#19854](https://github.com/craftcms/cms/pull/19854))
- Added `CraftCms\Cms\Element\Events\ElementActionMenuDescriptorsResolving`, allowing plugins to add action menu items to the Inertia element editor and element chips. ([#19783](https://github.com/craftcms/cms/pull/19783))
- Added `CraftCms\Cms\Mcp\Elements\ElementAdapter`, `CraftCms\Cms\Mcp\Elements\BaseElementAdapter`, and `CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry`, allowing plugins to make their element types available through the `elements.*` MCP tools. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added `CraftCms\Cms\Mcp\Attributes\RequiresHttp`, for MCP capabilities that only work over HTTP. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added `directPermissions` to MCP user permission results and `CraftCms\Cms\User\UserPermissions::getDirectPermissionsByUserId()`. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Element types can now add buttons to the end of the Inertia element editor’s footer by overriding `defineAdditionalButtonDescriptors()`, and plugins can add them via `CraftCms\Cms\Element\Events\ElementAdditionalButtonDescriptorsResolving`. They take the same `behavior` descriptors as action menu items, so they can open links, downloads, modals, and slideouts without submitting the form. ([#19910](https://github.com/craftcms/cms/issues/19910))
- The `additional-buttons` layout slot now sits at the end of the content footer.
- Added the `footer-meta` layout slot, beside the content footer’s save controls. Element editors show their autosave status there.
- `CpScreenResponse::additionalButtonsHtml()` is now rendered in the content footer for screens without their own Inertia page.
- Slideout footers now share the full-page content footer’s layout and layout slots, including `content-footer`.
- Craft 5 element edit page additional buttons (`getAdditionalButtons()`, `EVENT_DEFINE_ADDITIONAL_BUTTONS`, and `additionalButtonsHtml()`) are now shown in the Inertia element editor’s footer when the Yii adapter is installed. ([#19910](https://github.com/craftcms/cms/issues/19910))
- Added `CraftCms\Cms\Element\Events\ElementEditorPayloadResolving`. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Added `CraftCms\Cms\Asset\AssetUploadHandler::ingest()`, allowing non-HTTP adapters to reuse Craft’s asset creation workflow. ([#19823](https://github.com/craftcms/cms/pull/19823))
- Added support for passing prepared, unsaved assets to `CraftCms\Cms\Asset\Data\AssetIngest`, allowing transport adapters to set metadata and custom field values before validation and persistence. ([#19827](https://github.com/craftcms/cms/pull/19827))
- Added `CraftCms\Cms\Element\UserInitiatedElementSave` and `CraftCms\Cms\Element\Data\UserInitiatedElementSaveResult`, providing a shared workflow for user-initiated element saves. ([#19822](https://github.com/craftcms/cms/pull/19822))
- Added `CraftCms\Cms\Component\TypeRegistry::defer()`, for registering types once the registry is first read rather than at boot. ([#19851](https://github.com/craftcms/cms/pull/19851))
- Added an `$appearance` argument to `CraftCms\Cms\Cp\Html\StatusHtml::statusIndicatorHtml()` and `componentStatusIndicatorHtml()`. ([#19842](https://github.com/craftcms/cms/pull/19842))
- Added `CraftCms\Cms\Markdown\MarkdownOptions::$indentedCode`, which determines whether indented lines can start code blocks. ([#19859](https://github.com/craftcms/cms/pull/19859))
- Added `CraftCms\Cms\Search\Events\KeywordsIndexing::$layoutElementUid`. ([#13991](https://github.com/craftcms/cms/pull/13991))
- Added the `--c-layer-header` and `--c-layer-skip-link` CSS custom properties, and the `z-header` and `z-skip-link` utilities. ([#19886](https://github.com/craftcms/cms/pull/19886))
- Added a `width` attribute to `<craft-input-date-time>`, along with `CraftCms\Cms\Cp\Components\InputDateTime::width()` and `CraftCms\Cms\Form\Controls\DateTime::fullWidth()`, for stretching date and time inputs across their container. ([#19871](https://github.com/craftcms/cms/pull/19871))
- Added `CraftCms\Cms\Ui\Controls\BooleanMenu`. ([#19875](https://github.com/craftcms/cms/pull/19875))
- Added `createHttpClient()`, `http`, `isHttpError()`, and `isCancel()` to `@craftcms/ui`.
- Added `Craft.isCancel()`.
- Added `CraftCms\Cms\Cp\Components\ComponentSelect` and `CraftCms\Cms\Cp\Components\EntryTypeSelect`, which the `_includes/forms/componentSelect.twig` and `_includes/forms/entryTypeSelect.twig` templates now render. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Added `CraftCms\Cms\Cp\FormFields::componentSelectFromConfig()` and `entryTypeSelectFromConfig()`. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Added `CraftCms\Cms\Form\Controls\EntryTypeSelect`. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Added the `checkbox-options` attribute to `<craft-component-select>`, which keeps selected options listed as checked items. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Added the `text-quiet` Tailwind utility. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Updated the Inertia element editor and element chips to show action menu items added via `craft\base\Element::EVENT_DEFINE_ACTION_MENU_ITEMS`, or by plugin element types’ `safeActionMenuItems()` and `destructiveActionMenuItems()` overrides, through the Yii adapter, if they define a `url` or `action`. Items that rely on JavaScript are left out, and deprecation warnings point to the code to update. ([#19783](https://github.com/craftcms/cms/pull/19783))
- Updated `CraftCms\Cms\Cp\FormFields::editableTableHtml()`, `editableTableFieldHtml()`, and the corresponding Twig macros to use form-builder tables. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Updated `CraftCms\Cms\Entry\EntryTypes::saveEntryType()` to validate entry types and their field layouts by default, with a `$runValidation` argument for bypassing validation. ([#19821](https://github.com/craftcms/cms/pull/19821))
- Updated `<craft-indicator>` to use the `solid` appearance by default, rather than `outline-fill`, except for white and black fills, which keep `outline-fill` (with a white outline for black). ([#19842](https://github.com/craftcms/cms/pull/19842))
- Updated `actionClient` and `apiClient` from `@craftcms/ui` to use a fetch-based HTTP client with the same request methods, rather than Axios.
- Updated `Craft.sendActionRequest()` and `Craft.sendApiRequest()` to no longer use Axios. Their errors still pass `axios.isAxiosError()` and `axios.isCancel()` checks.
- Updated the control panel to only load the global `axios` when `craftcms/yii2-adapter` is installed. `Craft.sendActionRequest()` or `actionClient` from `@craftcms/ui` should be used instead.
- Updated `craft\queue\Queue::getJobId()` to return `null` when the current job isn’t being run by Yii’s queue. ([#19835](https://github.com/craftcms/cms/pull/19835))
- Moved legacy nested element HTML rendering and Yii event compatibility into `craftcms/yii2-adapter`. ([#19804](https://github.com/craftcms/cms/pull/19804))
- Deprecated the editable table `initJs` option and number column `locale` option. Tables now mount automatically, and number columns submit unformatted values. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Deprecated the `cancelToken` request option for `Craft.sendActionRequest()` and `Craft.sendApiRequest()`. `signal` should be used instead.
- Deprecated `craft\base\Plugin::getVersion()` and `setVersion()`. The `$version` property should be used instead. ([#19850](https://github.com/craftcms/cms/pull/19850))
- Removed `Cp.$axios`.
- Removed `craft\elements\db\ElementQuery::beforePrepare()`. `CraftCms\Cms\Element\Queries\ElementQuery::elementQueryBeforeQuery()` should be overridden instead. ([#19838](https://github.com/craftcms/cms/pull/19838))
- Removed `craft\elements\db\ElementQuery::joinElementTable()`. Element queries should now declare their element table via the `$table` property, which is joined automatically. ([#19838](https://github.com/craftcms/cms/pull/19838))
- Removed `craft\elements\db\ElementQuery::$subQuery`. Element queries are now a single query, so joins and conditions should be applied to `$query`. ([#19838](https://github.com/craftcms/cms/pull/19838))
- Removed `CraftCms\Cms\Element\Contracts\ElementInterface::getAdditionalButtons()` and `CraftCms\Cms\Element\Events\ElementAdditionalButtonsResolving`. `defineAdditionalButtonDescriptors()` and `CraftCms\Cms\Element\Events\ElementAdditionalButtonDescriptorsResolving` should be used instead. The Yii adapter still provides `getAdditionalButtons()` and `EVENT_DEFINE_ADDITIONAL_BUTTONS`. ([#19921](https://github.com/craftcms/cms/pull/19921))
- Removed the `breadcrumbs` and `submit-button` screen layout slots, which never rendered on full pages. The `crumbs` page prop and the `primary-action` slot should be used instead. ([#19854](https://github.com/craftcms/cms/pull/19854))
- Removed the `pasteableEntryTypeIds` nested element manager setting. `pasteableData` should be used instead. ([#19792](https://github.com/craftcms/cms/pull/19792))

### System

- Updated control panel navigation to refresh on each server navigation, including items added or removed dynamically by plugins.
- Reduced database queries when building control panel navigation.
- Fixed a bug where pressing <kbd>Cmd/Ctrl</kbd>+<kbd>S</kbd> on read-only element edit pages submitted a save request. ([#19865](https://github.com/craftcms/cms/pull/19865))
- Fixed a bug where element autosaves could continue after the editor was closed. ([#19817](https://github.com/craftcms/cms/pull/19817))
- Fixed a bug where creating, duplicating, or pasting Matrix blocks could modify their canonical owner instead of its draft. ([#19818](https://github.com/craftcms/cms/pull/19818))
- Fixed a bug where reordering, removing, or deleting nested elements did not update their owners’ modification dates. ([#19867](https://github.com/craftcms/cms/pull/19867))
- Fixed a bug where indented markup in field instructions and other control panel content could be rendered as code, which could leave tabs after the first one empty. ([#19859](https://github.com/craftcms/cms/pull/19859))
- Fixed a bug where Addresses fields’ configured Cards and Index view modes weren’t used in element forms. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where nested element cards’ Copy, Duplicate, and Delete actions were always disabled. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where nested elements without their own edit page, such as addresses, couldn’t be opened from their cards. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where nested element slideouts for element types other than entries always used the legacy editor. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where addresses couldn’t be saved from an element editor slideout. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where element actions couldn’t find a user’s addresses. ([#19792](https://github.com/craftcms/cms/pull/19792))
- Fixed a bug where component select menus weren’t showing components’ icons, colors, handles, or descriptions. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where component selects’ Create buttons didn’t open a slideout when their action was a URL. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where Matrix fields’ entry type settings were missing the “Add Group” button. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where moving, dragging, renaming, or reordering Matrix fields’ entry type groups wasn’t saved. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where entry types added to Matrix fields’ settings could lose their groups. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where saving a section after its settings had refreshed could submit empty site settings rows and cause an error. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where templates generated from section settings weren’t saved. ([#19888](https://github.com/craftcms/cms/pull/19888))
- Fixed a bug where element selector fields in server-rendered control panel forms and slideouts only displayed their labels and instructions. ([#19812](https://github.com/craftcms/cms/pull/19812))
- Fixed a bug where Link fields displayed the Label input even when `showLabelField` was disabled. ([#19811](https://github.com/craftcms/cms/pull/19811))
- Fixed a bug where nested HTML forms hid all their tabs and reused tab panel IDs across instances. ([#19816](https://github.com/craftcms/cms/pull/19816))
- Fixed an error when Form API table controls needed to add rows to meet their `minRows` setting before becoming visible. ([#19815](https://github.com/craftcms/cms/pull/19815))
- Fixed a bug where long Tip labels overflowed their tabs in the field layout designer. ([#19814](https://github.com/craftcms/cms/pull/19814))
- Fixed a bug where apostrophes in lightswitch labels were displayed as HTML entities. ([#19830](https://github.com/craftcms/cms/pull/19830))
- Fixed a bug where HTML 5 apostrophe entities could be double-encoded in control panel form attributes. ([#19832](https://github.com/craftcms/cms/pull/19832))
- Fixed a bug where the “All” option in element indexes’ status menus was missing its gradient indicator. ([#19842](https://github.com/craftcms/cms/pull/19842))
- Fixed a bug where draft elements’ chips weren’t showing their draft icon.
- Fixed a bug where users’ breadcrumb chips weren’t getting hyperlinked.
- Fixed a bug where plugins’ control panel nav items weren’t showing their icons. ([#19794](https://github.com/craftcms/cms/pull/19794))
- Fixed a bug where control panel navigation links could point to site URLs when cached outside control panel requests. ([#19810](https://github.com/craftcms/cms/pull/19810))
- Fixed a bug where selecting a text-expander suggestion in Firefox could cover the input and prevent further typing. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Fixed a bug where form-builder checkboxes submitted `on` instead of their configured checked value in HTML forms. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Fixed a bug where adding a form-builder table row with a row ID failed to focus its first editable cell. ([#19873](https://github.com/craftcms/cms/pull/19873))
- Fixed a bug where legacy embedded element indexes were missing actions, exporters, and reorder controls. ([#19789](https://github.com/craftcms/cms/pull/19789))
- Fixed a bug where subsequent embedded index requests lost configuration supplied by non-Matrix nested element managers. ([#19788](https://github.com/craftcms/cms/pull/19788))
- Fixed inconsistent handling of owner-specific Matrix entry types when creating, rendering, and duplicating entries. ([#19819](https://github.com/craftcms/cms/pull/19819))
- Fixed a bug where control panel form fields and groups received tab-only attributes, which logged Vue warnings and gave groups a stray `slot` attribute. ([#19874](https://github.com/craftcms/cms/pull/19874))
- Fixed a race condition where concurrent requests could invalidate the same workflow run and record duplicate activity. ([#19866](https://github.com/craftcms/cms/pull/19866))
- Fixed a bug where transaction rollback errors could hide the original element save exception after the database had aborted the transaction. ([#19866](https://github.com/craftcms/cms/pull/19866))
- Fixed a bug where multi-instance fields’ search keywords were indexed per field rather than per instance, so searching by one instance’s handle could match other instances’ values. ([#13991](https://github.com/craftcms/cms/pull/13991))
- Fixed a bug where named image transforms saved outside the Settings controller could bypass dimension and asset transformer parameter validation and normalization. ([#19826](https://github.com/craftcms/cms/pull/19826))
- Fixed bugs that could allow sites saved outside the control panel to reference invalid groups, retain inconsistent primary or URL settings, or use invalid content transfer targets. ([#19825](https://github.com/craftcms/cms/pull/19825))
- Fixed a bug where invalid route data could be saved to project config. ([#19824](https://github.com/craftcms/cms/pull/19824))
- Fixed a bug where `CraftCms\Cms\User\UserGroups::saveGroup()` did not validate user groups, and added its `$runValidation` argument. ([#19820](https://github.com/craftcms/cms/pull/19820))
- Fixed a bug where `craft:up` could fail on installs that didn’t have a migrations table yet. ([#19796](https://github.com/craftcms/cms/pull/19796))
- Fixed an error that could occur on the control panel login page when a Craft 5 plugin’s type registration handlers, such as `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES` listeners, relied on the current user. Legacy type registration events are now triggered when the types are first needed rather than on every request, as they were in Craft 5. ([#19851](https://github.com/craftcms/cms/pull/19851))
- Fixed a bug where `craft\base\Plugin::getVersion()` returned `1.0` for Craft 5 plugins, rather than the version from their Composer manifest. ([#19850](https://github.com/craftcms/cms/pull/19850))
- Fixed a bug where Craft 5 plugins’ stored settings that didn’t have a validation rule weren’t loaded, so they reverted to their default values. ([#19850](https://github.com/craftcms/cms/pull/19850))
- Fixed an error that occurred when `Craft::getAlias()` was passed a value that wasn’t an alias, such as `null`. Non-alias values are now returned unchanged, as they were in Craft 5. ([#19853](https://github.com/craftcms/cms/pull/19853))
- Fixed a bug where legacy batched queue jobs failed on their second item when run by Laravel’s queue worker. ([#19835](https://github.com/craftcms/cms/pull/19835))
- Fixed an error that occurred when registering asset bundles that depend on `craft\web\assets\htmx\HtmxAsset`. The bundle is now deprecated and doesn’t register htmx, so plugins that rely on htmx should bundle it themselves. ([#19836](https://github.com/craftcms/cms/pull/19836))
- Fixed a bug where control panel pages that legacy URL rules routed to Craft 6 actions changed the browser’s URL to an `actions/…` URL. ([#19837](https://github.com/craftcms/cms/pull/19837))
- Fixed a bug where control panel screens routed through the Yii2 adapter’s legacy action bridge were missing jQuery, Garnish, and `cp.js`, leaving legacy controls unresponsive. ([#19840](https://github.com/craftcms/cms/pull/19840))
- Fixed a bug where registering a legacy asset bundle while a control panel screen was rendering could load jQuery after `cp.js`, causing “jQuery is not defined” errors. ([#19840](https://github.com/craftcms/cms/pull/19840))
- Fixed an error that occurred when plugin elements’ `getFieldLayout()` methods declared a `craft\models\FieldLayout` return type. `craft\models\FieldLayout` is now an alias of `CraftCms\Cms\FieldLayout\FieldLayout`. ([#19834](https://github.com/craftcms/cms/pull/19834))
- Fixed an error when plugins using the Yii2 adapter returned settings models extending `craft\base\Model`. ([#19813](https://github.com/craftcms/cms/pull/19813))
- Fixed a bug where content in `CpContainer`’s `full` slot wasn’t displayed at full width. ([#19854](https://github.com/craftcms/cms/pull/19854))
- Fixed a bug where MCP CORS preflight requests collected admin instructions without an authenticated user.

## 6.0.0-alpha.19 - 2026-10-01

> [!IMPORTANT]
> This update contains breaking changes for plugins. See [#19574](https://github.com/craftcms/cms/pull/19574), [#19563](https://github.com/craftcms/cms/pull/19563), [#19588](https://github.com/craftcms/cms/pull/19588), [#19585](https://github.com/craftcms/cms/pull/19585), and [#19650](https://github.com/craftcms/cms/pull/19650) for details.

### Control Panel UI
- Updated editable tables to use Form API controls, with cell validation messages and keyboard row actions.
- Added inline editing to element index tables. ([#19759](https://github.com/craftcms/cms/pull/19759))
- Added exports to element indexes. ([#19759](https://github.com/craftcms/cms/pull/19759))
- Control panel success, notice, and error messages now go through a single message system instead of several different ones, and are no longer shown twice or lost between pages.
- Messages now stack, newest first, and spread out on hover or focus. A “Skip to messages” link reaches them from the keyboard. They can be swiped away, errors and messages with actions stay until dismissed, and errors are announced to screen readers immediately.
- Improved element index searches to update results as users type. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Added support for controller actions and named Laravel routes in section and Matrix site settings. ([#19762](https://github.com/craftcms/cms/pull/19762))
- Rearranged and cleaned up the control panel’s page shell layout, with improved mobile support. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Control panel pages can now set `contentMaxWidth` and `centerContent` options. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Added flyout menus to the global control panel nav, for accessing sub-pages of non-selected sections. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Added close buttons to element detail tabs. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Element editor slideouts now have action menus and “Open in a new tab” buttons. ([#19748](https://github.com/craftcms/cms/pull/19748))
- Removed the legacy control panel stylesheet from Inertia pages. ([#19648](https://github.com/craftcms/cms/pull/19648))
- `ElementEditor` now supports rendering as a full page in addition to a slideout. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Improved the overall accessibility of the control panel. ([#19699](https://github.com/craftcms/cms/pull/19699))
- Improved the accessibility of element indexes. ([#19520](https://github.com/craftcms/cms/pull/19520), [#19749](https://github.com/craftcms/cms/pull/19749))
- Replaced core inline element editing inputs with Form API controls rendered by Vue, with plugin field HTML compatibility handled by the Yii adapter. ([#19590](https://github.com/craftcms/cms/pull/19590))
- Improved the styling for collapsible field groups.
- Improved the layout and dark mode styling of user edit screens. ([#19765](https://github.com/craftcms/cms/pull/19765))
- Improved the styling of control panel tables and lightswitches. ([#19766](https://github.com/craftcms/cms/pull/19766))
- Improved disabled secondary form actions with explanatory tooltips.
- The “Assets” system settings nav item is now listed under “Content”, and “Globals”, “Categories”, and “Tags” are now listed under a new “Deprecated” heading, if those concepts are supported for the project. ([#19670](https://github.com/craftcms/cms/pull/19670))
- Added reusable server-built form modals and the `formModal` element action behavior. ([#19768](https://github.com/craftcms/cms/pull/19768))
- Added optional `sidebarForm` and `metadataHtml` props to `Form.vue` pages, allowing sidebar controls to be saved alongside the main form. ([#19778](https://github.com/craftcms/cms/pull/19778))
- Added support for `CraftCms\Cms\Http\Responses\CpScreenResponse::addAltAction()` submissions on Inertia Form pages and slideouts. ([#19779](https://github.com/craftcms/cms/pull/19779))
- Added the `<craft-timeline-item>` web component. ([#19629](https://github.com/craftcms/cms/pull/19629))
- Added the `placeholder` property to `<craft-thumbnail>`, for an image to show while the thumbnail loads. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Added `CraftCms\Cms\Form\Controls\Combobox\CreateOption` for creating and selecting resources in comboboxes, including user photo volumes. ([#19777](https://github.com/craftcms/cms/pull/19777))
- Added the `--c-thumbnail-image-radius` custom property to `<craft-thumbnail>`, and its other custom properties can now be set on an ancestor. ([#19758](https://github.com/craftcms/cms/pull/19758))
- Content in `<craft-chip>`’s `prefix` slot no longer replaces its thumbnail, icon, and status; it’s shown before them. ([#19758](https://github.com/craftcms/cms/pull/19758))
- Migrated the reassign entries, replace relations, and replace references modals to the Form API. ([#19589](https://github.com/craftcms/cms/pull/19589))
- Added `CraftCms\Cms\Form\Nodes\Callout::padding()`, supported by PHP and Vue form rendering. ([#19773](https://github.com/craftcms/cms/pull/19773))
- Restored Score sorting for element index searches and preserved previous sort fields as tie-breakers. ([#19731](https://github.com/craftcms/cms/pull/19731))

### Assets
- Migrated Control Panel uploads to the native Uppy picker and shared upload sessions, including user photos.
- Added support for upload sessions, tus and direct S3 multipart transports, and an extensible JavaScript upload API. ([#19604](https://github.com/craftcms/cms/pull/19604))
- Added background uploads in the Assets index that continue during Control Panel navigation, with persistent progress, retry, cancellation, and filename-conflict controls.
- The image editor now supports Undo/Redo. ([#19600](https://github.com/craftcms/cms/pull/19600))
- Added `crop`, `fit`, `stretch`, and `letterbox` modes to `craft-thumbnail`, and removed size-dependent asset thumbnail cropping.
- Added a “Revert to original” button to the image editor. ([#19600](https://github.com/craftcms/cms/pull/19600))
- Image assets now store color data sampled from their file when it’s uploaded, replaced, or indexed, including the image’s dominant color and a grid of the average colors of its regions. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750))
- Asset edit sidebars now fill the space around image previews with a gradient between the colors of the image’s left and right edges. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750))
- Image thumbnails and previews in the control panel now show a blurred placeholder based on the image’s colors while they load. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Control panel uploads now sample image colors in the browser and send them along, so the server doesn’t need to sample the file itself. ([#19751](https://github.com/craftcms/cms/pull/19751))
- `<img>` tags returned by `CraftCms\Cms\Asset\Elements\Asset::getImg()` now have a blurred placeholder as their background, unless the image has transparent regions. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Image assets now have [BlurHash](https://blurha.sh) strings, available via `asset.blurhash` in templates and the `blurhash` field in GraphQL queries. ([#19755](https://github.com/craftcms/cms/pull/19755))
- Added the `blurhash`, `colors`, and `placeholderDataUrl` fields to assets in GraphQL queries. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750), [#19755](https://github.com/craftcms/cms/pull/19755))
- Added an optional `$mode` argument to core thumbnail APIs, defaulting to `Fit` for thumbnail HTML and `Crop` for `CraftCms\Cms\Asset\Assets::getThumbUrl()`. Implementations of `CraftCms\Cms\Component\Contracts\Thumbable` and `CraftCms\Cms\Field\Contracts\ThumbableFieldInterface`, and overrides of thumbnail layout methods and `thumbUrl()`, must update their signatures for Craft 6, including through existing Yii aliases; existing calls remain valid. The Yii Assets service wrapper and legacy thumbnail event are unchanged.
- Added `CraftCms\Cms\Asset\Elements\Asset::$colors`. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750))
- Added `CraftCms\Cms\Asset\Elements\Asset::getBlurhash()`. ([#19755](https://github.com/craftcms/cms/pull/19755))
- Added `CraftCms\Cms\Asset\Elements\Asset::getPlaceholderDataUrl()`. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Added `CraftCms\Cms\Asset\Elements\Asset::$uploadColors`. ([#19751](https://github.com/craftcms/cms/pull/19751))
- Added `CraftCms\Cms\Image\Blurhash`. ([#19755](https://github.com/craftcms/cms/pull/19755))
- Added `CraftCms\Cms\Image\ColorGrid`. ([#19755](https://github.com/craftcms/cms/pull/19755))
- Added `CraftCms\Cms\Image\Data\ImageColors`. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750))
- Added `CraftCms\Cms\Image\Images::colors()`. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750))
- Changed `users/upload-user-photo` to start an upload session using JSON file metadata instead of multipart file data.
- Removed the `assets/upload` and `assets/replace-file` multipart endpoints and legacy uploader events and overrides.

### Conditions
- Added support for nested condition groups. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Removed the “Show unpermitted entries” setting from Entries, Link, and Markdown fields, in favor of the “Viewable” condition rule in the “Selectable Entries Condition” setting. ([#19611](https://github.com/craftcms/cms/pull/19611), [#19622](https://github.com/craftcms/cms/pull/19622))
- Removed the “Show unpermitted files” and “Allowed File Types” settings from Assets, Link, and Markdown fields, in favor of “Viewable” and “File Type” condition rules in the “Selectable Assets Condition” setting. ([#19611](https://github.com/craftcms/cms/pull/19611), [#19622](https://github.com/craftcms/cms/pull/19622))
- Added a “Selectable {Type} Condition” setting to Link and Markdown fields’ Entry and Asset link types. ([#19622](https://github.com/craftcms/cms/pull/19622))
- Added `CraftCms\Cms\Condition\BaseConditionGroup`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\ConditionBuilderPayload`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\ConditionBuilderRenderer`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\ConditionBuilder`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\ConditionRulePayload`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\Contracts\ConditionComponentInterface`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\Contracts\ConditionGroupInterface`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\Contracts\ConditionInterface::createGroup()`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface::getForm()`, which replaces `getHtml()`. ([#19588](https://github.com/craftcms/cms/pull/19588))
- Added `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface::isSelectableForCondition()`. ([#19563](https://github.com/craftcms/cms/pull/19563))
- Added `CraftCms\Cms\Condition\Enums\GroupOperator`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Added `CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface`, which element condition rules that modify element queries should now implement. ([#19563](https://github.com/craftcms/cms/pull/19563))
- Added `CraftCms\Cms\Element\Conditions\ElementCondition::$forQuery`. ([#19563](https://github.com/craftcms/cms/pull/19563))
- Added `CraftCms\Cms\Element\Conditions\ElementCondition`. ([#19587](https://github.com/craftcms/cms/pull/19587))
- Removed `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface::getHtml()`. `getForm()` must be implemented instead. ([#19588](https://github.com/craftcms/cms/pull/19588))
- Removed `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface::getExclusiveQueryParams()` and `modifyQuery()`. `ElementQueryConditionRuleInterface::modifyQuery()` should be implemented instead, which now accepts the underlying query builder directly. ([#19563](https://github.com/craftcms/cms/pull/19563))
- Removed `CraftCms\Cms\Element\Conditions\ElementCondition::$queryParams`. ([#19563](https://github.com/craftcms/cms/pull/19563))

### Fields
- Added configurable Table cell types through `CraftCms\Cms\Field\Contracts\TableCellInterface` and `CraftCms\Cms\Field\TableCellTypes`.
- Replaced Table column options JSON inputs with cell settings forms, and preserved Table data when a cell type is unavailable.
- Added a “Default Row Values” setting to Table fields. ([#3621](https://github.com/craftcms/cms/issues/3621))
- Added support for disabling Matrix entries for individual sites. ([#19686](https://github.com/craftcms/cms/pull/19686))
- Restored Matrix fields’ configured Cards and Cards Grid view modes in element forms.
- Restored Matrix fields’ configured Index view mode in element forms.
- `CraftCms\Cms\Field\Contracts\FieldInterface::modifyQuery()` now accepts an `Illuminate\Database\Query\Builder` object for its `$query` argument, and has a new `CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $elementQuery` argument, and a `void` return type. ([#19562](https://github.com/craftcms/cms/pull/19562), [#19585](https://github.com/craftcms/cms/pull/19585))
- Moved legacy relation-field settings HTML and entry-title input HTML into the Yii adapter. ([#19591](https://github.com/craftcms/cms/pull/19591))
- Restored “Copy value from site” functionality. ([#19683](https://github.com/craftcms/cms/pull/19683))

### Entries & Sections
- Added the “Show the Post Date field” and “Show the Expiry Date field” entry type settings. ([#17675](https://github.com/craftcms/cms/pull/17675))
- Entry types’ Color fields now show a dropdown menu. ([#19760](https://github.com/craftcms/cms/pull/19760))

### Releases
- Added releases, allowing saved drafts of root elements to be grouped, scheduled, and published atomically.
- Added the `releaseId` element query and GraphQL argument for selecting drafts attached to a release.

### Workflows
- Added publishing workflows, which control when drafts may be applied to entries. Workflows are configured from Settings → Workflows and assigned to sections. (Craft Pro and Enterprise only.) ([#19667](https://github.com/craftcms/cms/pull/19667))
- Changes to enabled entries in sections with a workflow are now saved as drafts, which must be submitted for review and approved by each workflow stage before they can be applied. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added the “User Review” workflow stage type, which supports per-group approval requirements, carried-over approvals, change requests, and email notifications for reviewers. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added a “Workflow” element details tab, for submitting drafts for review, reviewing them, and viewing workflow activity history. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added `CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface` and `CraftCms\Cms\Workflow\Stages\WorkflowStage`, for creating custom workflow stage types. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added `CraftCms\Cms\Workflow\WorkflowStageTypes`, for registering custom workflow stage types. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added `CraftCms\Cms\Workflow\Contracts\WorkflowableInterface`, which element types can implement to support workflows. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added `CraftCms\Cms\Workflow\Workflows` and `CraftCms\Cms\Support\Facades\Workflows`. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added the `CraftCms\Cms\Workflow\Events\WorkflowTransitioning`, `WorkflowTransitioned`, and `WorkflowCommented` events. ([#19667](https://github.com/craftcms/cms/pull/19667))

### Elements
- Added the `autoEagerLoadElements` general config setting (`true` by default), which determines whether element queries should be automatically lazy eager-loaded. ([#19637](https://github.com/craftcms/cms/pull/19637))
- Added Markdown comments to element activity timelines, with support for editing, removing, structured user mentions, and email notifications.
- Added `CraftCms\Cms\Activity\Contracts\ShouldBeRetained`, allowing activity event types such as comments to opt out of activity garbage collection.
- Restored expand/collapse and drag interactions on structure element index views. ([#19691](https://github.com/craftcms/cms/pull/19691))

### Users & Auth
- Added support for sending queued Laravel notifications to `CraftCms\Cms\User\Elements\User` elements. ([#19541](https://github.com/craftcms/cms/pull/19541))
- Added the `authGuard` and `authPasswordBroker` general config settings, allowing Craft authentication to use a dedicated Laravel guard, provider, and password broker. ([#19598](https://github.com/craftcms/cms/issues/19598))

### Filesystems
- Removed Craft-managed filesystems and their control panel settings. Volumes and Craft asset transformers now reference Laravel filesystem disks configured in `config/filesystems.php`, and each defines whether its assets have public URLs. Existing filesystem references are migrated to matching disks automatically, with an actionable error if a disk isn’t configured. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Added `CraftCms\Cms\Asset\Models\Volume::$hasUrls`, which determines whether the volume’s assets have public URLs. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Added `CraftCms\Cms\Config\GeneralConfig::$uploadSessionDisk` and `getUploadSessionDisk()`, which determine the disk used to stage upload sessions. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Renamed `CraftCms\Cms\Config\GeneralConfig::$tempAssetUploadFs` to `$tempAssetUploadDisk`, and `getTempAssetUploadFs()` to `getTempAssetUploadDisk()`, which now reference a Laravel filesystem disk exclusively. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Renamed `CraftCms\Cms\Cp\SelectOptions::getFsOptions()` to `getDiskOptions()`, which now returns Laravel filesystem disk options exclusively. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Removed `CraftCms\Cms\Asset\Assets::getTempAssetUploadFs()` and `CraftCms\Cms\Asset\AssetsHelper::isTempUploadFs()`. `Assets::getTempAssetUploadDisk()` should be used instead, which now returns a Laravel filesystem disk. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Removed `CraftCms\Cms\Cp\Components\FilesystemSelect` and `CraftCms\Cms\Form\Controls\FilesystemSelect`. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Removed `CraftCms\Cms\Filesystem\Filesystems`, `FilesystemTypes`, `Contracts\FsInterface`, `Filesystems\Filesystem` (and its `DiskFilesystem`, `Local`, `MissingFs`, and `Temp` implementations), `Resources\FsResource`, `Events\FilesystemRenamed`, and `CraftCms\Cms\Support\Facades\Filesystems`. Laravel filesystem disks should be configured and referenced directly instead; Craft filesystem types registered through `craft\base\Fs` remain supported for legacy plugins via the Yii adapter. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Removed `CraftCms\Cms\Http\Controllers\Settings\FilesystemsController` and `CraftCms\Cms\Http\ViewModels\FilesystemsEditViewModel`, along with the Filesystems control panel settings page. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Removed `CraftCms\Cms\Plugin\Concerns\HasFilesystemTypes`. Plugins can no longer register custom Craft filesystem types. ([#19650](https://github.com/craftcms/cms/pull/19650))

### Plugins & Extensibility
- Added `CraftCms\Cms\Form\Controls\Text::suffix()` for display-only suffix text in PHP and Vue form controls. ([#19770](https://github.com/craftcms/cms/pull/19770))
- Added `CraftCms\Cms\Element\Data\ElementSiteSettings`, the shared base for element URI and route settings. ([#19762](https://github.com/craftcms/cms/pull/19762))
- Added support for fluent plugin settings classes. ([#19574](https://github.com/craftcms/cms/pull/19574))
- Added support for refreshable standard plugin settings forms and conditional configuration of core form nodes. ([#19545](https://github.com/craftcms/cms/pull/19545))
- Added `CraftCms\Cms\Form\Nodes\Group::expanded()`, allowing collapsible section groups to start expanded in Vue and HTML forms. ([#19772](https://github.com/craftcms/cms/pull/19772))
- Added `CraftCms\Cms\Route\CurrentElement`, a contextual attribute for injecting the matched element into controller actions and route closures. ([#19762](https://github.com/craftcms/cms/pull/19762))
- Added `CraftCms\Cms\Contracts\PluginInterface::createSettings()`, which replaces `createSettingsModel()`. ([#19574](https://github.com/craftcms/cms/pull/19574))
- Added `CraftCms\Cms\Dashboard\Widgets\Widget::component()` and `props()`, which replace `getBodyHtml()`. ([#19564](https://github.com/craftcms/cms/pull/19564))
- Added `CraftCms\Cms\Support\Url::stripCpTrigger()`. ([#19724](https://github.com/craftcms/cms/pull/19724))
- `CraftCms\Cms\Support\Url::removeParam()` now accepts an array of param names. ([#19724](https://github.com/craftcms/cms/pull/19724))
- Removed `CraftCms\Cms\Contracts\PluginInterface::createSettingsModel()`. `createSettings()` must be implemented instead. ([#19574](https://github.com/craftcms/cms/pull/19574))
- Removed `CraftCms\Cms\Dashboard\Widgets\Widget::getBodyHtml()`. `component()` and `props()` must be implemented instead. (`getBodyHtml()` remains supported through the Yii adapter.) ([#19564](https://github.com/craftcms/cms/pull/19564))
- Removed `CraftCms\Cms\Support\Url::baseUrl()`, `cpHost()`, `encodeParams()`, `host()`, `isFullUrl()`, `removeParams()`, and `rootRelativeUrl()`. (They remain available on `craft\helpers\UrlHelper` through the Yii adapter.) ([#19724](https://github.com/craftcms/cms/pull/19724))
- Added `Cp.$elementDetailsTabs`, allowing plugins to register control panel element-details tabs. ([#19646](https://github.com/craftcms/cms/pull/19646))
- Added `CraftCms\Cms\Support\Flash::all()`, `make()`, and `push()`, and a `$target` argument to `Flash::success()`, `error()`, and `notice()` for showing a message in an inline outlet rather than the default message display.
- Added the `messages` Inertia shared prop, which carries every flashed control panel message with its type, settings, and id.
- Added `useMessages()` and `useMessageOutlet()` for showing control panel messages from Vue, and the `craft-message` window event for showing them from anywhere else.
- `asSuccess()` and `asFailure()` JSON responses now include a `messages` list on control panel requests, and `asSuccess()` no longer also flashes the message to the session for JSON responses.
- `asFailure()` now flashes its message even when the response has validation errors.
- Deprecated the `flash` Inertia shared prop. `messages` should be used instead.
- Removed `useFlash()`, `useFlashMessages()`, and the `FlashMessages` Vue component. `useMessages()` should be used instead.
- Stopped loading the deprecated `XRegExp` library by default. Plugins that require it can register `craft\web\assets\xregexp\XregexpAsset`. ([#19621](https://github.com/craftcms/cms/pull/19621))

### System
- Improved performance of element queries, control panel rendering, asset transforms, date formatting, and queue status checks, and fixed related SQLite index and timezone issues.
- Added `CraftCms\Cms\ProjectConfig\ProjectConfig::getPendingChanges()`.
- Replaced the project config implementation with separate change handling, storage, and rebuild components.
- Removed HTMX.
- Base site URLs, including `url('')`, `siteUrl('')`, and homepage URLs, now respect the `addTrailingSlashesToUrls` config setting, so they no longer end with a trailing slash by default. The `siteUrl` Twig variable still always ends with a trailing slash. ([#19723](https://github.com/craftcms/cms/pull/19723))
- Fixed `CraftCms\Cms\Support\Env::parse()` to preserve unknown aliases rather than throw an exception. ([#19535](https://github.com/craftcms/cms/pull/19535))
- Fixed a bug where validating filesystem attributes could resolve `CraftCms\Cms\Filesystem\Filesystems\Filesystem::getRootUrl()`. ([#19535](https://github.com/craftcms/cms/pull/19535))
- Fixed a bug where nested Content Block fields’ content could be lost during a batched resave that included revisions. ([#19543](https://github.com/craftcms/cms/issues/19543))
- Fixed a bug where `resave` and `update-statuses` commands reported skipped revisions as root element lookup errors.
- Fixed a bug where visiting the logout path while signed out would store it as the post-login redirect, so signing in would immediately sign the user back out. ([#19551](https://github.com/craftcms/cms/pull/19551))
- Fixed a bug where submitting a form after signing in through an elevated or expired session modal could fail CSRF validation.
- Fixed a bug where authenticated control panel requests to Yii2 adapter controller actions were treated as unauthenticated. ([#19556](https://github.com/craftcms/cms/pull/19556))
- Fixed a bug where save errors using shortened field paths could fail to appear on Controls within nested Forms. ([#19565](https://github.com/craftcms/cms/pull/19565))
- Fixed a bug where failed plugin installation or uninstallation could leave project config writable. ([#19566](https://github.com/craftcms/cms/pull/19566))
- Fixed a bug where cached template resolution could ignore the current site, registered template roots, or lookup options. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where orphan cleanup could delete the wrong rows for composite foreign keys. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where deleting a site could transfer its content despite selecting the delete option. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where object templates could render through an outdated Twig environment or behave differently after caching. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where preparing Composer changes could leave `composer.json` partially modified after a failure. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where worker events or progress updates could reactivate cancelled queue jobs. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where cached search term normalization could use the wrong language. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where nested Twig namespace tags or exceptions could leave the wrong input namespace active. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed decimal rounding errors involving high-precision values, scientific notation, and negative numbers. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where generating a URL for another site could leave that site selected after an exception. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where percentage formatting could lose decimal precision during multiplication. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where nested cache collection could extend expiration deadlines or ignore expired dependencies. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where support request emails could omit errors encountered while preparing their attachments. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where parallel test cleanup could delete another worker’s files. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where command-line update listings could evaluate update criticality more than once using incomplete update information. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where preparing a control panel response could discard or leak an input namespace. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where screen rendering could generate action menu items twice, producing duplicate events or inconsistent action IDs. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where indexing large asset volumes could exceed memory or database parameter limits. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where asset folder lookups could return stale or inconsistent results after folders were saved or deleted. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where concurrent image downloads could delete each other’s temporary files, and failed downloads could leave temporary files behind. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where loading an image transform index could lose its output format. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where asset factories could create assets and their folders in different volumes. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where Money field conditions could compare incorrect units or lose precision. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where numeric conditions could ignore zero-valued bounds. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where splitting relation criteria could discard field and source site restrictions. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where cached field layouts could retain outdated custom field handles or identities. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where Link fields could lose labels and attributes during normalization or localization. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where generated field propagation could retain old element state and run during later element operations. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where validation errors could replace generated field handles and cause subsequent validation failures. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where JSON fields could misinterpret valid objects containing `__ERROR__` or `__VALUE__` keys. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where repairing a field layout UID could undo repairs to its tab and element UIDs. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where field usage metadata could lose field layout UIDs and produce incorrect links or empty groups. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where queued resaves could handle null field defaults differently from inline resaves. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where duplicating an entry could apply submitted changes to the original entry. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where the first access to lazily loaded entry authors could return no authors. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where entries deleted with their entry type or section could fail to be restored with it. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where removing supported sites from a section could leave obsolete entry site data. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where sections’ site settings tables didn’t have status switches.
- Fixed a bug where creating a section from the command line could ignore a selected existing entry type. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where element queries could select the wrong site variant when requesting unique results across sites. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where bulk duplication could save partial results after a validation failure. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where element indexes could select automatic sources outside their available source collection. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where element indexes could discard the selected source’s status criteria. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where component overrides could affect other instances of the same component. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where element query results could eager-load relations twice. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where cloned element queries could use callbacks that still read criteria from the original query. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where failed structure moves could leave locks held and block subsequent operations. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed an error that occurred when deeply duplicating descendants. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed database errors and incorrect ordering when repairing structures. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where structure repair previews could differ from the repairs that would be applied. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where user group metadata could be saved before permission elevation was checked. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where rejected user permission changes could leave some permissions updated. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where verification email delivery addresses could alter account identity or be lost when notifications were queued. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed inconsistent account eligibility checks when signing into the control panel with OAuth. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where named OAuth providers could overwrite each other’s configuration. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where failed account updates could leave in-memory account state inconsistent with persisted data. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where failed filesystem resolution could leave a previously generated disk available with outdated configuration. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed inconsistent plugin license statuses, issues, and trial information when reading cached metadata. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where nested GraphQL mutation inputs could affect how sibling inputs were normalized. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where switching GraphQL schemas could reuse types, arguments, or loaders from the previous schema. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where resetting generated GraphQL state could unnecessarily invalidate cached query results. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where removing false, zero, or empty-string project config values could leave their database rows behind.
- Fixed a bug where saving project config repeatedly could persist the same changes more than once. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed a bug where project config path processing state could leak between application instances. ([#19568](https://github.com/craftcms/cms/pull/19568))
- Fixed an error that could occur when creating relation fields. ([#19571](https://github.com/craftcms/cms/pull/19571))
- Fixed a bug where no confirmation dialog was shown when deleting entry types and custom fields. ([#19582](https://github.com/craftcms/cms/pull/19582))
- Fixed user photo asset selections not being saved, and restricted the photo selector to images in the configured volume and subfolder. ([#19603](https://github.com/craftcms/cms/pull/19603))
- Fixed a bug where field types could remain unchanged or switch to an unintended option when their combobox query was submitted with <kbd>Return</kbd>. ([#19614](https://github.com/craftcms/cms/pull/19614))
- Fixed a bug where brand icons, including the Markdown field type icon, were not displayed in combobox options. ([#19614](https://github.com/craftcms/cms/pull/19614))
- Fixed a bug where creating an entry type from an entry type select field opened an empty slideout. ([#19617](https://github.com/craftcms/cms/pull/19617))
- Fixed a bug where unpaginated Inertia admin tables could show nonfunctional pagination controls. ([#19618](https://github.com/craftcms/cms/pull/19618))
- Fixed a bug where Save and continue editing left newly created control panel items on their creation page. ([#19619](https://github.com/craftcms/cms/pull/19619))
- Fixed a bug where the “Structure” sorting option was missing from structure element indexes. ([#19620](https://github.com/craftcms/cms/pull/19620))
- Fixed a bug where Asset link type settings were ignored by Link and Markdown fields. ([#19623](https://github.com/craftcms/cms/pull/19623))
- Fixed duplicate plus icons on user management actions. ([#19627](https://github.com/craftcms/cms/pull/19627))
- Fixed a bug where Markdown fields could collapse when initialized inside hidden containers. ([#19635](https://github.com/craftcms/cms/pull/19635))
- Fixed a bug where the account navigation sidebar was missing when editing a user. ([#19643](https://github.com/craftcms/cms/pull/19643))
- Fixed a bug where saved drafts without canonical elements were missing from element indexes. ([#19649](https://github.com/craftcms/cms/pull/19649))
- Fixed a bug where Matrix Index views could show content from the wrong site.
- Fixed a bug where reopening a Matrix entry from an Index view could show its canonical content instead of the owner draft’s content.
- Fixed an error that occurred when upgrading to Craft 6. ([#19658](https://github.com/craftcms/cms/pull/19658))
- Fixed a bug where users in single-site installations could be denied access to localized elements because they lacked an unavailable site permission. ([#19666](https://github.com/craftcms/cms/pull/19666))
- Fixed a bug where unsaved drafts could not be deleted from element indexes. ([#19668](https://github.com/craftcms/cms/pull/19668))
- Fixed a bug where modifier-clicking or middle-clicking on some control panel links wasn’t opening the link in a new tab.
- Fixed a bug where modifier-clicking or middle-clicking the New Entry button didn’t open the creation page in a new tab. ([#19669](https://github.com/craftcms/cms/pull/19669))
- Fixed an error that occurred when loading the Users index if a user belonged to a user group. ([#19673](https://github.com/craftcms/cms/pull/19673))
- Fixed an error that could occur when rendering element table rows with strict Twig variables enabled. ([#19679](https://github.com/craftcms/cms/pull/19679))
- Fixed an error that occurred when moving folders directly beneath an asset volume’s root folder. ([#19680](https://github.com/craftcms/cms/pull/19680))
- Fixed a bug where folders in the Assets index couldn’t be selected, created, renamed, moved, or deleted. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where assets and folders couldn’t be moved by dragging them onto a folder in the Assets index. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where clicking a folder in the Assets index didn’t navigate into it or keep the breadcrumbs in sync. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where the Assets index search couldn’t be expanded to include subfolders. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where <kbd>Shift</kbd>+<kbd>Space</kbd> didn’t preview the selected file in the Assets index. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where the “Preview file” modal was unstyled.
- Fixed a bug where clicking an asset in the Assets index didn’t open its editor. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed a bug where the Assets index’s folder dialogs could render behind other overlays, lose their centering, or not focus their name field. ([#19682](https://github.com/craftcms/cms/pull/19682))
- Fixed multiple bugs with Table fields, including new columns moving or defaulting to Checkbox, settings rows not being draggable, and element edit forms failing when the field value was null. ([#19687](https://github.com/craftcms/cms/pull/19687))
- Fixed a bug where combobox fields rendered through the Form API displayed their label twice. ([#19694](https://github.com/craftcms/cms/pull/19694))
- Fixed a bug where visually-hidden labels, headings, and status messages were visible on some control panel pages.
- Fixed a bug where saving an element draft could send a redundant autosave request. ([#19704](https://github.com/craftcms/cms/pull/19704))
- Fixed a bug where pressing <kbd>Ctrl/Cmd</kbd> + <kbd>S</kbd> in a slideout could also save the page behind it. ([#19705](https://github.com/craftcms/cms/pull/19705))
- Fixed various bugs with Customize Sources modals. ([#19713](https://github.com/craftcms/cms/pull/19713))
- Fixed an error that could occur when saving elements, if their field layouts still referenced legacy component classes. ([#19719](https://github.com/craftcms/cms/pull/19719))
- Fixed an error that could occur when rendering field settings.
- Fixed a bug where migrations that wrote to the project config had to be defensive about its `readOnly` state. ([#19726](https://github.com/craftcms/cms/pull/19726))
- Fixed a bug where Shift-selecting a shorter range didn’t deselect items outside the new range. ([#19727](https://github.com/craftcms/cms/pull/19727))
- Fixed a bug where select inputs could lose their selected value if their options were loaded asynchronously. ([#19728](https://github.com/craftcms/cms/pull/19728))
- Fixed a bug where table rows could stop being draggable after their reorder handles were replaced. ([#19729](https://github.com/craftcms/cms/pull/19729))
- Fixed a bug where plugin-provided download element actions didn’t trigger file downloads from element indexes. ([#19730](https://github.com/craftcms/cms/pull/19730))
- Fixed a bug where asset indexing left temporary copies of remote images behind. ([#19740](https://github.com/craftcms/cms/pull/19740))
- Fixed a bug where uploaded and replaced assets’ modification dates came from their temporary files rather than the stored files.
- Fixed an accessibility issue where the Date, Time, and Time zone inputs within `<craft-input-date-time>` didn’t have individual accessible names. ([#19741](https://github.com/craftcms/cms/pull/19741))
- Fixed a bug where the Delete element action reported success when some elements couldn't be deleted. ([#19743](https://github.com/craftcms/cms/pull/19743))
- Fixed a bug where element index sort direction buttons did nothing. ([#19744](https://github.com/craftcms/cms/pull/19744))
- Fixed an error in the Yii adapter during installation or updates when `CRAFT_SITE` or `X-Craft-Site` referenced a missing site. ([#19745](https://github.com/craftcms/cms/pull/19745))
- Fixed a bug where the Edit and View bulk actions did nothing on element indexes. ([#19747](https://github.com/craftcms/cms/pull/19747))
- Fixed an error that occurred when using an icon picker. ([#19752](https://github.com/craftcms/cms/issues/19752))
- Fixed a validation error that occurred when saving an entry type with a color. ([#19753](https://github.com/craftcms/cms/issues/19753))
- Fixed a validation error that occurred when saving a Color field with no color palette, even if “Allow custom colors” was enabled.
- Fixed a bug where element index selection ranges could use a stale anchor after clearing selection or switching view modes. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Fixed a bug where element index searches could move keyboard focus after a page load. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Fixed a bug where element selector modals could display results from an outdated request. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Fixed a bug where element selector modals requested search results on every keystroke. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Fixed a bug where element chips didn’t show their status indicators, and their thumbnails weren’t sized, spaced, or centered for the chip. ([#19758](https://github.com/craftcms/cms/pull/19758))
- Fixed a bug where money inputs could be hidden within fields or submit their values under the wrong name. ([#19767](https://github.com/craftcms/cms/pull/19767))
- Fixed a bug where reactive controls in element editors didn’t refresh their field layouts, including nested form scopes. ([#19769](https://github.com/craftcms/cms/pull/19769))
- Fixed a bug where Form API field instructions displayed Markdown and inline HTML as plain text in Vue forms. ([#19771](https://github.com/craftcms/cms/pull/19771))
- Fixed a bug where `<craft-callout>` boxes didn’t span all columns in grid layouts. ([#19773](https://github.com/craftcms/cms/pull/19773))
- Fixed a bug where `<craft-input>` fields with a `maxlength` weren’t sized to fit it, which made the element index pagination’s page input overlap the “Next page” button.
- Fixed a bug where condition builders passed raw condition values to form change listeners. ([#19774](https://github.com/craftcms/cms/pull/19774))
- Fixed a bug where failed settings saves could lose input and validation errors when a successful save would redirect to another page. ([#19775](https://github.com/craftcms/cms/pull/19775))
- Fixed a bug where card designer previews could fail to render because the preview request omitted the element type. ([#19776](https://github.com/craftcms/cms/pull/19776))

## 6.0.0-alpha.18 - 2026-09-01

- Added configurable asset transformers, which can be managed from Settings → Assets → Asset Transformers and assigned to asset volumes by handle.
- Added `CraftCms\Cms\Asset\AssetTransformers` and `CraftCms\Cms\Asset\AssetTransformDrivers`.
- Added `CraftCms\Cms\Config\GeneralConfig::$defaultAssetTransformer`.
- Added a control panel notification center and `CraftCms\Cms\Cp\Notifications\CpNotification`, backed by Laravel database notifications. Deprecated announcement service compatibility remains available through `craftcms/yii2-adapter`.
- Replaced Craft’s system status with Laravel maintenance mode, including an admin-only Maintenance Mode control in General Settings.
- Removed `CraftCms\Cms\Config\GeneralConfig::$isSystemLive`, `app()->isLive()`, and the core `craft:on` and `craft:off` commands. Deprecated compatibility remains available through `craftcms/yii2-adapter`.
- Moved the `generateTransformsBeforePageLoad` setting to Craft asset transformer profiles.
- Removed per-call immediate generation arguments from the core asset transform APIs and GraphQL transform arguments.
- Removed the core image transformer registry, contracts, fallback transformer, and execution methods from `CraftCms\Cms\Image\ImageTransforms`. Legacy equivalents remain available through `craftcms/yii2-adapter`.
- Improved environment variable and alias settings fields to show suggestions after typing `$` or `@`, automatically bracing embedded environment variables.
- Improved new password fields to provide the configured password rules to supported password managers. ([#19516](https://github.com/craftcms/cms/pull/19516))
- Element edit screens now autosave at the pace of the change — a keystroke waits, a discrete change saves almost immediately.
- Submitting an element edit screen now cancels any in-flight autosave, and a failed autosave reports its HTTP status.
- Element edit screens now indicate which fields a draft has unapplied changes to.
- Fixed a bug where the control panel loaded two copies of Lit, which could break rendering within legacy HTML controls.
- Fixed a bug where field layout changes weren’t saved on entry type settings screens.
- Fixed a bug where Typecast would throw when trying to set properties that didn’t exist. ([#19492](https://github.com/craftcms/cms/pull/19492))
- Fixed a bug where POST requests to legacy action URLs weren’t getting routed properly. ([#19478](https://github.com/craftcms/cms/issues/19478))
- Fixed a JavaScript error that occurred when creating a new Dashboard widget. ([#19479](https://github.com/craftcms/cms/issues/19479))
- Fixed a bug where users without a local password could not start an elevated session using OAuth. ([#19512](https://github.com/craftcms/cms/pull/19512))
- Fixed a bug where Yii log targets configured via `config/craft/app.php` did not receive messages logged with `Craft::info()` and related methods. ([#19517](https://github.com/craftcms/cms/pull/19517))
- Moved filesystem URL settings to `CraftCms\Cms\Filesystem\Filesystems\Local`, with legacy filesystem compatibility remaining available through `craftcms/yii2-adapter`. ([#19525](https://github.com/craftcms/cms/pull/19525))

## 6.0.0-alpha.17 - 2026-08-18

- Improved template resource cache collection by replaying structured HTML stack entries without parsing rendered tags.
- Changed `CraftCms\Cms\Auth\Passkeys\Passkeys::verifyPasskey()` to return the updated credential record on success.
- Changed GraphQL AST value decoding to use `webonyx/graphql-php` while preserving Craft-specific query condition validation.
- Improved element queries to retain only explicitly supplied custom-field criteria.
- Improved `CraftCms\Cms\Form\FormResolver` performance by indexing control paths and node UIDs for membership checks.
- Added `CraftCms\Cms\Http\ResponseHeaders` and `CraftCms\Cms\Support\Facades\ResponseHeaders` for accumulating response headers within the current request scope.
- Improved job progress persistence by using an atomic upsert.
- Changed `CraftCms\Cms\Support\Json::decode()` to use exception-based JSON decoding.
- Changed `craft:db:drop-all-tables` to use Laravel’s schema API.
- Replaced the asset index lifecycle flags with `CraftCms\Cms\Asset\Enums\AssetIndexStatus` and explicit status transitions.
- Added `CraftCms\Cms\Plugin\Plugin::settingsForm()` for defining standard plugin settings pages with the control panel Form system. ([#19439](https://github.com/craftcms/cms/pull/19439))
- Removed `CraftCms\Cms\Plugin\Plugin::settingsHtml()`. `settingsForm()` should be used instead; Yii-era plugin settings HTML remains supported by `craftcms/yii2-adapter`. ([#19439](https://github.com/craftcms/cms/pull/19439))
- Safe HTML elements are now allowed within Markdown field layout elements. ([#19426](https://github.com/craftcms/cms/pull/19426))
- Added a slideout system for the Inertia/Vue control panel, which renders any `CpScreenResponse`-based screen as an in-page panel from a normal Inertia response, alongside the existing legacy `Craft.CpScreenSlideout`. ([#19354](https://github.com/craftcms/cms/pull/19354))
- Added `CraftCms\Cms\Http\Responses\CpScreenResponse::screenData()`. ([#19354](https://github.com/craftcms/cms/pull/19354))
- Removed the `Pane.vue` Vue component in favor of the `craft-pane` web component. ([#19398](https://github.com/craftcms/cms/pull/19398))
- Element edit screens now autosave when the form’s values actually differ from the server’s, rather than whenever a control reports a change.
- Improved structure mutation reliability by representing each pending change as a single immutable operation.
- Improved Project Config change event handler registration by keeping callbacks and ordering metadata together.
- Fixed a bug where Table field column handles became arrays after failed validation.
- Fixed a bug where nested or concurrent searches could overwrite another search’s parser state.
- Fixed a bug where cached user permission trees could become stale after permission changes or be modified by assignability filtering.
- Fixed inconsistent handling of forced-disabled plugin configuration values.
- Fixed a bug where `CraftCms\Cms\Edition` capability checks could report capabilities from the configured edition rather than the receiver.
- Fixed a bug where throwing validation could run the validation lifecycle twice.
- Fixed a bug where `CraftCms\Cms\Element\ElementCollection::with()` could pass incompatible element classes into eager loading.
- Fixed a bug where cache options and tags registered via `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()` were unavailable as Artisan commands.
- Fixed a JavaScript error that occurred on non-Inertial pages that rendered field layout designers. ([#19380](https://github.com/craftcms/cms/discussions/19380))
- Fixed a bug where Yii asset bundles registered with `craft\web\View::registerAssetBundle()` during plugin initialization were not included in rendered pages. ([#19393](https://github.com/craftcms/cms/pull/19393))
- Fixed a bug where legacy asset bundle dependencies could be rendered after their dependent resources when using `craftcms/yii2-adapter`. ([#19394](https://github.com/craftcms/cms/pull/19394))
- Fixed an error that occurred when `config/craft/app.web.php` or `config/craft/app.console.php` was present.
- Fixed a bug where jobs run on the sync queue could remain marked as reserved after completing. ([#19431](https://github.com/craftcms/cms/pull/19431))
- Fixed a bug where Addresses fields weren’t reading the value posted by the control panel form, so removing every address didn’t stick and blank addresses could be created. ([#19432](https://github.com/craftcms/cms/pull/19432))
- Fixed a bug where newly-added Matrix entries and addresses showed a spinner indefinitely in the Inertia/Vue element editor, rather than their fields.
- Fixed a bug where opening an element edit page with a Money field immediately created a provisional draft, before anything had been edited.
- Fixed a bug where the `jobprogress` table was missing `dateCompleted` and `dateFailed` columns for installs that were upgraded from Craft 5.
- Fixed a bug where failed queue jobs were losing their descriptions. ([#19444](https://github.com/craftcms/cms/issues/19444))
- Fixed a bug where queue job details in the Queue Manager utility included “Error” and timestamp values even if they were null.

## 6.0.0-alpha.16 - 2026-08-05

- Fixed a bug where Yii-style migrations could be required twice. ([#19376](https://github.com/craftcms/cms/pull/19376))
- Fixed a bug where the legacy `craft\base\Widget` class wasn’t fully implementing `CraftCms\Cms\Dashboard\Contracts\WidgetInterface`. ([#19375](https://github.com/craftcms/cms/pull/19375))

## 6.0.0-alpha.15 - 2026-08-04

- Added support for Markdown-based custom Dashboard widgets in the application’s `resources/widgets/` directory. ([#19319](https://github.com/craftcms/cms/pull/19319))
- Replaced `pixelandtonic/imagine` with `intervention/image` for image manipulation.
- Added support for the libvips image driver via the optional `intervention/image-driver-vips` package.
- Added BMP, HEIC, ICO, JPEG 2000, JPEG XL, and TIFF image transform formats when supported by the active image driver.
- Added support for configuring field layout field instruction positions.
- Added fluent APIs for creating and modifying `CraftCms\Cms\FieldLayout\FieldLayout`, `CraftCms\Cms\FieldLayout\FieldLayoutTab`, and field layout elements.
- Added a renderer-neutral control panel Form system with shared PHP and Vue rendering, extensible Nodes and Controls, nested and refreshable scopes, changed-only submission, and integration with configurable component settings, fields, and field layouts. ([#19384](https://github.com/craftcms/cms/pull/19384))
- Changed `craft:resave:all` to discover registered `craft:resave:*` Artisan commands directly, rather than relying on a resolving event. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Changed the My Account → Addresses page to a full Inertia/Vue page, rendering nested-element cards from data instead of server-rendered HTML. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Changed `CraftCms\Cms\Cp\FormFields::textFromConfig()` to accept an optional `CraftCms\Cms\Cp\Components\Input` instance as a second argument, so callers can build on an existing component instead of always creating a plain `Input`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Changed `CraftCms\Cms\Search\Events\SearchPerformed` to be a readonly, immutable event; its `$results` and `$scores` properties can no longer be overridden by listeners. `CraftCms\Cms\Search\Events\SearchScoresResolving` should be used to override scores instead. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Asset\AssetFileKinds`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Cp\Components\Button::action()`, for declarative click actions. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Cp\Components\Button::inherit()`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Added `CraftCms\Cms\Cp\Components\InputColor`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Components\InputPassword`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Data\NavItem::group()`. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Added `CraftCms\Cms\Cp\Enums\ButtonVariant`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Added `CraftCms\Cms\Cp\FormFields::colorFromConfig()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\FormFields::passwordFromConfig()`, `passwordHtml()`, and `passwordFieldHtml()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Html\ElementHtml::elementCardLabelHtml()`, `elementCardActionsHtml()`, `elementCardThumbHtml()`, and `elementCardThumbAlignment()`. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Cp\Settings::registerSetting()` and `registerReadOnlySetting()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Dashboard\WidgetTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Database\Commands\MigrateCommand::registerMigrator()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Element\ElementTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Element\NestedElementManager::getCardsData()` and `getIndexData()`. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Field\FieldTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Field\LinkTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Field\NestedEntryFieldTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\FieldLayout\NativeFields`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Filesystem\FilesystemTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlArguments`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlDirectives`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlMutations`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlQueries`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Image\ImageTransformers`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Image\Raster::getInterventionImage()`.
- Added `CraftCms\Cms\Plugin\Plugin::$filesystemTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlDirectives`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlMutations`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlQueries`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$linkTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$siteTemplateRoots`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getCacheOptions()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getCacheTags()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getNativeFields()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getSystemMessages()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Search\Events\SearchResultsResolving`. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Search\Events\SearchScoresResolving`. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Support\Facades\AuthMethods`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\SystemMessage\SystemMessages::register()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Twig\Variables\Cp::color()` and `password()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\User\UserPermissions::registerPermissionGroup()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Utility\UtilityTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\View\TemplateCacheCollectors`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\View\TemplateRoots`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Support\Concerns\EvaluatesClosures` and support for closure values in fluent CP component and field layout builder APIs.
- Changed `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::label()` to accept an optional label and return the field layout element when one is passed. Overrides must accept the new optional argument.
- Changed `CraftCms\Cms\Image\Raster::getTextBox()` to return a `width` and `height` array.
- Renamed the protected `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::instructions()`, `tip()`, and `warning()` methods to `instructionsText()`, `tipText()`, and `warningText()`.
- Removed `CraftCms\Cms\Asset\Events\AssetFileKindsResolving`. `CraftCms\Cms\Asset\AssetFileKinds::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Auth\Events\AuthMethodsResolving`. `CraftCms\Cms\Auth\AuthMethods::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Cp\Events\RegisterCpSettings` and `CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings`. `CraftCms\Cms\Cp\Settings::registerSetting()` and `registerReadOnlySetting()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Dashboard\Events\WidgetTypesResolving`. `CraftCms\Cms\Dashboard\WidgetTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Database\Events\MigratorsResolving`. `CraftCms\Cms\Database\Commands\MigrateCommand::registerMigrator()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Element\Events\ElementResaveCommandsResolving`. A normal Artisan command in the `craft:resave` namespace should be registered instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Element\Events\ElementTypesResolving`. `CraftCms\Cms\Element\ElementTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\FieldTypesResolving`. `CraftCms\Cms\Field\FieldTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\LinkTypesResolving`. `CraftCms\Cms\Field\LinkTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\NestedEntryFieldTypesResolving`. `CraftCms\Cms\Field\NestedEntryFieldTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving`. `CraftCms\Cms\FieldLayout\NativeFields::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Filesystem\Events\FilesystemTypesResolving`. `CraftCms\Cms\Filesystem\FilesystemTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlArgumentHandlersResolving`. `CraftCms\Cms\Gql\GqlArguments::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlDirectivesResolving`. `CraftCms\Cms\Gql\GqlDirectives::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlMutationsResolving`. `CraftCms\Cms\Gql\GqlMutations::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlQueriesResolving`. `CraftCms\Cms\Gql\GqlQueries::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlTypesResolving`. `CraftCms\Cms\Gql\GqlTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Image\Events\ImageTransformersResolving`. `CraftCms\Cms\Image\ImageTransformers::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Image\Images::MINIMUM_IMAGICK_VERSION` and `craft\services\Images::MINIMUM_IMAGICK_VERSION`.
- Removed `CraftCms\Cms\Image\Raster::getImagineImage()`.
- Removed `CraftCms\Cms\Search\Events\ScoringResults` in favor of the following new events: ([#19308](https://github.com/craftcms/cms/pull/19308))
  - `CraftCms\Cms\Search\Events\SearchResultsResolving`
  - `CraftCms\Cms\Search\Events\SearchScoresResolving`
- Removed `CraftCms\Cms\SystemMessage\Events\SystemMessagesResolving`. `CraftCms\Cms\SystemMessage\SystemMessages::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\User\Events\UserPermissionsResolving`. `CraftCms\Cms\User\UserPermissions::registerPermissionGroup()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Utility\Events\ClearCachesOptionsResolving` and `CraftCms\Cms\Utility\Events\ClearCachesTagOptionsResolving`. `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Utility\Events\UtilitiesResolving`. `CraftCms\Cms\Utility\UtilityTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\View\Events\CpTemplateRootsResolving` and `CraftCms\Cms\View\Events\SiteTemplateRootsResolving`. `CraftCms\Cms\View\TemplateRoots::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\View\Events\TemplateCacheCollectorsResolving`. `CraftCms\Cms\View\TemplateCacheCollectors::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added the `@craftcms/ui/factory` module, a jQuery-free layer of typed element factories that mirror the `src/Cp/Components` PHP builders. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `createTextInput()` and `createCopyTextPrompt()` to the `@craftcms/ui/factory` module. ([#19333](https://github.com/craftcms/cms/pull/19333))
- Added `turnOn()`, `turnOff()`, and `turnIndeterminate()` methods to the `<craft-switch>` web component. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added a `group` property to the `<craft-nav-item>` web component, for rendering a subnav as a non-collapsible semantic grouping. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Added `Garnish.CustomSelect` and `Garnish.MenuBtn` to `@craftcms/garnish`, jQuery-free TypeScript ports of the legacy floating listbox menu and menu-button classes. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Moved the `Craft.ComponentSelectInput` control panel JavaScript class out of the core bundle into a `yii2-adapter` compatibility asset, since `<craft-component-select>` is now used everywhere in core; the `componentSelect.twig` `jsClass` escape hatch still works for plugin subclasses. ([#19333](https://github.com/craftcms/cms/pull/19333))
- Moved the `Craft.AssetMover`, `Craft.AssetSelectorModal`, `Craft.BaseElementSelectInput`, `Craft.BaseElementSelectorModal`, `Craft.BaseUploader`, `Craft.Chart`, `Craft.CpModal`, `Craft.CustomizeSourcesModal`, `Craft.DataTableSorter`, `Craft.ElementActionTrigger`, `Craft.ElementDeletionManager`, `Craft.ElementTableSorter`, `Craft.EntrySelectInput`, `Craft.Grid`, `Craft.PreviewFileModal`, `Craft.Tabs`, `Craft.TagSelectInput`, `Craft.Uploader`, and `Craft.VolumeFolderSelectorModal` control panel JavaScript classes from the legacy jQuery bundle to TypeScript modules. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Changed `<craft-nav-item>` to render as a `<span>` instead of an `<a>` when it has no `href`, dropping `aria-current` in that case. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Changed element index table rows and cards so clicking anywhere on them (other than an interactive control) selects them, extending the selection range on shift-click just like clicking a row’s checkbox. ([#19351](https://github.com/craftcms/cms/pull/19351))
- Deprecated the `Craft.LightSwitch`, `Craft.InfoIcon`, `Craft.ColorInput`, `Craft.PasswordInput`, `Craft.IconPicker`, `Craft.SlidePicker`, `Craft.SlideRuleInput`, and `Craft.Tooltip` control panel JavaScript classes, along with the `.infoicon` jQuery plugin. The corresponding `@craftcms/ui` web components should be used instead. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Removed the `Craft.Accordion` and `Craft.EnvVarGenerator` control panel JavaScript classes. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Removed the `Craft.DeleteUserModal` control panel JavaScript class. It was deprecated in 5.10.0 and unused. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Fixed a bug where Blade templates rendered through Craft used path-based view names, preventing named Laravel view composers from running. ([#19177](https://github.com/craftcms/cms/issues/19177))
- Fixed a bug where the `accent` semantic color used by colorable elements (e.g. `craft-callout`, `[data-color]`) rendered red instead of blue, due to a drifted color mapping in `@craftcms/ui`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Fixed a styling issue. ([#19296](https://github.com/craftcms/cms/pull/19296))
- Fixed a bug where Yii adapter plugins could cause legacy control panel assets to be omitted. ([#19302](https://github.com/craftcms/cms/pull/19302))
- Fixed a bug where assets’ Alternative Text values could not be cleared. ([#19310](https://github.com/craftcms/cms/issues/19310))
- Fixed a bug where element deletion confirmation dialogs could display unresolved pluralization syntax. ([#19311](https://github.com/craftcms/cms/issues/19311))
- Fixed a bug where replacing an asset would fail silently. ([#19312](https://github.com/craftcms/cms/issues/19312))
- Fixed JavaScript errors that could occur throughout the control panel. ([#19313](https://github.com/craftcms/cms/issues/19313))
- Fixed a bug where `forms.checkboxField()` and `CraftCms\Cms\Cp\FormFields::checkboxFieldHtml()` rendered an empty field. ([#19338](https://github.com/craftcms/cms/pull/19338))
- Fixed a bug where Utility pages weren’t rendering, and were logging `$ is not defined` and `window.Cp.config is not a function` errors to the console. ([#19340](https://github.com/craftcms/cms/pull/19340))
- Fixed a bug where `actionClient` requests for bare action paths could corrupt the `?site=` query string on multi-site installs. ([#19342](https://github.com/craftcms/cms/pull/19342))
- `Craft.cp.announce()` now accepts live regions that are plain elements as well as jQuery collections. ([#19340](https://github.com/craftcms/cms/pull/19340))

## 6.0.0-alpha.14 - 2026-07-22

> [!IMPORTANT]
> This update contains breaking changes for plugins. See [#19263](https://github.com/craftcms/cms/pull/19263) for details.

- Plugins should no longer define `extra.laravel.providers` in `composer.json`. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed automatic plugin trait lifecycle hooks. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Added `CraftCms\Cms\Cp\Components\Button`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ButtonGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Callout::hideIcon()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Callout`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Checkbox`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\CheckboxGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\CheckboxSelect`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ChoiceGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ComponentRegistry`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Field`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\FieldGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Icon`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Input`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Lightswitch`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Radio`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\RadioGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Textarea`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\ViewComponent`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\EvaluatesClosures`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasAppearance`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasDisabled`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasId`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasSize`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasVariant`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Appearance`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Size`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Variant`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::buttonFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::buttonGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxSelectFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::lightswitchFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioGroupFieldHtml()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::textareaFromConfig()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\FormFields::textFromConfig()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Support\Facades\Template`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Added `CraftCms\Cms\Twig\Contracts\TwigRendererInterface`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Added `CraftCms\Cms\Twig\Variables\Cp::button()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::buttonGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkbox()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkboxGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkboxSelect()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::lightswitch()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::radio()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::radioGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::text()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Twig\Variables\Cp::textarea()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\ui()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\View\TemplateManager`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- `template()` and `pageTemplate()` now accept an optional template renderer name. ([#19290](https://github.com/craftcms/cms/pull/19290))
- `TemplateRendered` and `PageTemplateRendered` events now expose the final renderer name via `$rendererName`; the corresponding before events no longer expose renderer identity. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Replaced `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager`. HTML sanitizers should now be registered via `CraftCms\Cms\Support\Facades\HtmlSanitizers::extend()` rather than `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::register()`. ([#19292](https://github.com/craftcms/cms/pull/19292))
- Removed `CraftCms\Cms\Plugin\Events\PluginUnregistered`. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed `CraftCms\Cms\Plugin\Plugin::bootPlugin()`. `boot()` should be used instead. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed `CraftCms\Cms\Plugin\Plugin::registerPlugin()`. `register()` should be used instead. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Fixed a bug where the legacy `yii\web\JqueryAsset` wasn’t resolving properly. ([#19264](https://github.com/craftcms/cms/pull/19264))
- Fixed a bug where bulk entry moves could assign entries to sections that didn’t support their entry types. ([#19267](https://github.com/craftcms/cms/pull/19267))
- Fixed a bug where queued resaves ignored offset and limit criteria or accepted non-positive batch sizes. ([#19271](https://github.com/craftcms/cms/pull/19271))
- Fixed a bug where failed password and passkey login attempts incremented invalid-login counters twice. ([#19283](https://github.com/craftcms/cms/pull/19283))
- Fixed bugs that could prevent authored content from being deleted or reassigned safely when deleting users. ([#19273](https://github.com/craftcms/cms/pull/19273))
- Fixed an issue where disabled and archived user accounts could still authenticate. ([#19265](https://github.com/craftcms/cms/pull/19265))
- Fixed a bug where one-time two-factor authentication credentials could be accepted by concurrent login attempts. ([#19282](https://github.com/craftcms/cms/pull/19282))
- Fixed issues with `craft:users:set-password` password validation, exit statuses, and session invalidation. ([#19272](https://github.com/craftcms/cms/pull/19272))
- Fixed a bug where selected GraphQL mutations could be mistaken for cacheable queries. ([#19284](https://github.com/craftcms/cms/pull/19284))
- Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerability.
- Fixed a bug where two-factor authentication could lose login state or verify the wrong user during impersonation. ([#19274](https://github.com/craftcms/cms/pull/19274))
- Fixed a bug where new Matrix blocks weren’t getting created. ([#19161](https://github.com/craftcms/cms/issues/19161))

## 6.0.0-alpha.13 - 2026-07-16

- Updated logout routes to require CSRF-protected POST requests. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/ff6f20717a48e7ede5fbbe47487e7bb8ef71da78))
- Updated control panel configuration serialization to use `Illuminate\Support\Js::from()`. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/f844ae1cc3b02659ff68eb320ff7d2190a2a01da))
- Updated the project testing guidelines to emphasize behavior-focused tests. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/63b452d4b82d066566ad653ea39f6c1ad949eaa3))
- Updated queue connection retry windows to exceed Craft’s maximum job timeout. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/21bff956e964679a3f9951545bdf059473e0e5e8))
- Updated core queue jobs to resolve their dependencies through Laravel’s service container. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/dced32fc03120f27bc82ca61a9d960b95786ee03))
- Updated automatic garbage collection to run as a unique queue job. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/a35f815f3d9096311fc891b33c31427af865d902))
- Updated two-factor authentication recovery codes to be encrypted at rest. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fb560d68a1d4f3a4e02b360c5bcacde27780fa05))
- Improved field reference cleanup performance. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/303054c48d45f33d43c6781664729fd01a6130f3))
- Improved numeric element reference resolution performance. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/08e882820aa0681e55c346f57102dfb1792e142c))
- Improved element merge performance by avoiding per-relation and per-structure queries. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/202c2f6782ac59d15cf8cbda32ab4304807b0407))
- Improved search indexing memory use when indexing many elements. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/117c2c5f222eec8acb2371937928a364a52c0fb0))
- Improved asset indexing performance when finding missing and empty folders. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fe4974ab449f2e0402b333cf77282d04f4ee5558))
- Prevented duplicate image transform jobs from being queued for the same transform. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/059ccbed6ccd41c12dea1a5eb806f281e5878290))
- Removed duplicate control panel icon alias registrations. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/e8acf264f2bad6fd4c7c0f3de742ad85f89cdbbb))
- Fixed a bug where GraphQL asset mutations weren’t executing HTTP requests for remote asset URLs. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/35e54924d7404ce21a591ece04a970afc5a3ae80))
- Fixed a bug where failed relation writes could leave partial relation changes persisted. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/4f466d0730e266ea347a069807be6bc7a61e3afc))
- Fixed a bug where element merge replacement jobs could be dispatched before their database transaction was committed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/a50f8cb6fe6f037d7fc95a3b7305fb09a57759ff))
- Fixed a bug where section project-config jobs could be dispatched before their database transaction was committed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/3b54bc28253cade054a10a879252a8d102571a33))
- Fixed a bug where passkey login attempts weren’t rate limited. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/e6f7deca8eaba4c056b27358bb9b0b89b96e0cc5))
- Fixed a bug where relation localization could not be retried safely after a failed run. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/915b8196d920cc1d27d352e1fe773a921a258e46))
- Fixed a bug where project config mutex cleanup could release a lock owned by another process. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/845e2795c5158dfe1249d7543331e2a163e9dc3f))
- Fixed a bug where nested structure operations could release their mutex lock before the outer operation completed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/9f5157ae9745a4677117cdfeb0051a82b8af916b))
- Fixed a bug where two-factor authentication verification attempts weren’t rate limited. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/f4f11c8ef465fa981ee32d2c1ed84ea8b8e8d13e))
- Fixed a bug where user photo uploads could exceed the configured maximum upload size. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fc3fa25f5e7c9108b9e465b1d93ea5d116cf3e08))
- Fixed a bug where unsafe filenames could be used for Craft support attachments. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/2872b891eca9d243901e186b6bba76150fc0d16e))
- Fixed a bug where a failed legacy field type migration could leave project config events muted. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/0b22858254f9d6ef54e1f4aaca4de055a1c7f388))
- Fixed a bug where TemplateGlobals were being resolved every time a Blade component rendered. ([#19257](https://github.com/craftcms/cms/pull/19257))
- Fixed a bug where Craft updates could fail with a 503 response when the action URL contained query parameters.

## 6.0.0-alpha.12 - 2026-07-15

- Added `Illuminate\Contracts\Translation\HasLocalePreference` support to user elements, allowing Laravel notifications to use users’ Language preferences. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Login attempts are now rate limited.
- Updated core asset I/O to resolve Craft filesystem definitions and configured storage targets through Laravel filesystem disks.
- Updated elevated session prompts to use the modern control panel frontend while preserving the legacy JavaScript APIs.
- Fixed a bug where site routes weren’t being registered for each localized site value.
- Fixed a bug where POST requests to the `loginPath` weren’t being handled properly. ([#19220](https://github.com/craftcms/cms/pull/19220))
- Fixed a bug where users were redirected to the previous page on logout. ([#19220](https://github.com/craftcms/cms/pull/19220))
- Fixed a bug where requests to the `loginPath`, `setPasswordPath`, and `verifyEmailPath` were getting redirected to the control panel. ([#19229](https://github.com/craftcms/cms/pull/19229))
- Fixed a bug where Laravel translation fallbacks weren’t applied when `translationDebugOutput` was enabled. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where custom plugin settings `FormRequest` classes could persist unvalidated settings. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where failed Craft API responses weren’t processing response headers. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where Craft plugin service providers could be skipped when the plugin’s Composer metadata also defined Laravel package discovery settings. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where event-registered user permissions weren’t getting saved. ([#19232](https://github.com/craftcms/cms/issues/19232))
- Fixed lifecycle leaks where asset, GraphQL, route token, user, and user permission state could persist in long-running application processes. ([#19242](https://github.com/craftcms/cms/pull/19242))
- Fixed [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerabilities.
- Fixed a [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerability.
- Fixed a [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) XSS vulnerability.

## 6.0.0-alpha.11 - 2026-07-07

- Users can now connect their accounts to one or more Socialite providers. ([#19202](https://github.com/craftcms/cms/pull/19202))
- The login page now lists Socialite providers. ([#19202](https://github.com/craftcms/cms/pull/19202))
- Fixed a bug where some control panel resources and pages weren’t loading properly. ([#19214](https://github.com/craftcms/cms/issues/19214))

## 6.0.0-alpha.10 - 2026-07-03

- It’s now possible to load Blade views within Twig templates. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Craft now registers several Blade directives, bringing near feature parity with Twig templating. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Added SQLite database support. ([#19149](https://github.com/craftcms/cms/pull/19149))
- Control panel resources are now provided by a `craftcms/cms-assets` package. ([#19162](https://github.com/craftcms/cms/pull/19162))
- Renamed `CraftCms\Cms\Twig\PageLifecycle` to `CraftCms\Cms\View\PageLifecycle`. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Renamed `CraftCms\Cms\Twig\TemplateResolver` to `CraftCms\Cms\View\TemplateResolver`. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Fixed a bug where anonymous homepage and fallback site-template requests could bypass offline-site access enforcement. ([#19151](https://github.com/craftcms/cms/pull/19151))
- Fixed a bug where the login page would show a CSRF token mismatch on http requests
- Fixed an error that could occur during Craft 6 upgrades when the `migrations` table was missing its `track` column. ([#19168](https://github.com/craftcms/cms/pull/19168))
- Fixed an error that occurred when saving Users source settings after selecting the Groups table column. ([#19184](https://github.com/craftcms/cms/pull/19184))
- Fixed a bug where database backups weren’t using the `--single-transaction` or `--column-statistics=0` flags on MySQL.
- Fixed a bug where Content Block fields’ nested fields weren’t available as table columns on element indexes.

## 6.0.0-alpha.9 - 2026-06-23

- Added `CraftCms\Yii2Adapter\Database\DeprecatedTable`.
- Added `CraftCms\Cms\Translation\Formatter::asRelativeTime()`. ([#19146](https://github.com/craftcms/cms/pull/19146))
- Updated `webonyx/graphql-php` to 15.33.1. ([#18757](https://github.com/craftcms/cms/pull/18757))
- `craft\elements\Category::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\CategoryQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- `craft\elements\GlobalSet::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\GlobalSetQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- `craft\elements\Tag::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\TagQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a “This password does not use the Bcrypt algorithm” error that could occur when logging in with a user whose password was set in an earlier version of Craft.
- Fixed a “File name is not a string” error that could occur when an error was encountered when rendering a string template. ([#19122](https://github.com/craftcms/cms/pull/19122))
- Fixed a bug where parsed site names would get saved to the project config. ([#19123](https://github.com/craftcms/cms/issues/19123))
- Fixed a bug where plugin `$styles`, `$scripts`, and `$publishables` weren’t published automatically when the plugin was installed or enabled. ([#19137](https://github.com/craftcms/cms/pull/19137))
- Fixed several issues that occurred when Craft was configured with a custom (or no) `cpTrigger`. ([#19127](https://github.com/craftcms/cms/pull/19127))
- Fixed a bug where Craft wasn’t applying the Settings → General timezone to PHP’s default timezone. ([#19138](https://github.com/craftcms/cms/pull/19138))
- Fixed a bug where entry queries weren’t fetching structure data by default.
- Fixed a bug where top-level structure elements were always repositioned to the end of the structure on save.
- Fixed a bug where the Settings index page didn’t include “Globals”, “Categories”, or “Tags” links, when the concepts were supported. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed errors that occurred when editing global sets, category groups, and tag groups. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a bug where it wasn’t possible to create new categories. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed an error that occurred when editing a category. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a bug where users’ Language preference field could be set to Arabic by default when the browser’s preferred language included a territory ID (e.g. `en-US`).
- Fixed a bug where preview tokens weren’t taking the `previewTokenDuration` config setting into account.
- Fixed a bug where success/failure notifications weren’t being shown after deleting elements. ([#19028](https://github.com/craftcms/cms/pull/19028))

## 6.0.0-alpha.8 - 2026-06-17

- Added `CraftCms\Cms\Twig\AllowableInSandbox`.
- Fixed a bug where Blade templates weren’t loading for text/Markdown mail. ([#19106](https://github.com/craftcms/cms/pull/19106))
- Fixed a bug where it wasn’t possible to change the primary site, or edit site statuses. ([#19109](https://github.com/craftcms/cms/issues/19109))

## 6.0.0-alpha.7 - 2026-06-16

- Added a new core Markdown field ([#18960](https://github.com/craftcms/cms/pull/18960))
- Added a way for fields to track references and register a deletion blocker for them ([#19014](https://github.com/craftcms/cms/pull/19014))
- Added `CraftCms\Cms\Validation\Events\ValidationRulesResolving::$ruleset`.
- Relaxed the allowed types in the `ValidationRulesResolving` event to include any implementation `ValidatesWithRuleset` or a `Illuminate\Http\Request` object.
- Renamed `CraftCms\Cms\Validation\Events\ValidationRulesResolving::$component` to `$subject`.
- Relocated `CraftCms\Cms\Element\Validation\Events\ValidationRulesResolving` to `CraftCms\Cms\Validation\Events\` to reflect its broader applicability to components and rulesets.
- Fixed errors that could occur when Craft user elements were expected but the authenticated user was resolved as a Laravel user model. ([#19051](https://github.com/craftcms/cms/pull/19051))
- Fixed a bug where the `craft:install` command would hang if run within a production environment.
- Fixed an error that could occur when `CraftCms\Cms\Support\DateTimeHelper::toDateTime()` returned a `DateTimeInterface` implementation other than `DateTime`. ([#19079](https://github.com/craftcms/cms/pull/19079))
- Fixed a bug where “Replace relation” action buttons weren’t working.
- Fixed a “Invalid URL” JavaScript error in the control panel. ([#19041](https://github.com/craftcms/cms/pull/19041))
- Fixed an error that could occur during Craft 6 upgrades when legacy relational or Matrix field settings included `showCardsInGrid`. ([#19047](https://github.com/craftcms/cms/pull/19047))
- Fixed a bug where queue job progress labels weren’t getting translated.
- Fixed a bug where the control panel sidebar and Queue Manager were showing completed jobs.
- Fixed a bug where `CraftCms\Yii2Adapter\Mixins\ValidateMixin::addErrors()` had incorrect arguments. ([#19065](https://github.com/craftcms/cms/pull/19065))
- Fixed a bug where plugin templates were not being loaded correctly
- Fixed a bug where query string params were getting registered as variables in Twig templates. ([#19090](https://github.com/craftcms/cms/discussions/19090))
- Fixed a bug where parsed site URLs would get saved to the project config. ([#19092](https://github.com/craftcms/cms/issues/19092))

## 6.0.0-alpha.6 - 2026-06-03

- Improved the accessibility of the Login page. ([#19025](https://github.com/craftcms/cms/pull/19025))
- Added `CraftCms\Cms\User\Contracts\CraftUser` and `CraftUserTrait`. ([#19009](https://github.com/craftcms/cms/pull/19009))
- Removed `CraftCms\Cms\Auth\UserProvider`; the Craft guard now defaults to Laravel’s Eloquent provider using `CraftCms\Cms\User\Models\User`. ([#19009](https://github.com/craftcms/cms/pull/19009))
- Added `\CraftCms\Cms\craftUser()`/`\CraftCms\Cms\craftUser()` and `request()->craftUser()` as Craft-safe ways to access the authenticated user. ([#19009](https://github.com/craftcms/cms/pull/19009))
- `Element::getIterator()` no longer includes custom field values. ([#19004](https://github.com/craftcms/cms/issues/19004))
- Fixed a bug where checking the elevated session timeout could overwrite newer session data, which could prevent passkeys from being created.
- Fixed a bug where legacy plugin-defined `actions.php` routes could collide between plugins. ([#18994](https://github.com/craftcms/cms/pull/18994))
- Fixed a bug where JavaScript and CSS registered by utility pages weren’t executed when navigating between utility pages, and weren’t cleaned up when navigating away. ([#18978](https://github.com/craftcms/cms/issues/18978))
- Fixed a bug where custom element authorization methods weren’t respected by Laravel element policies. ([#18983](https://github.com/craftcms/cms/pull/18983))
- Fixed a bug where removing all permissions from a user wouldn’t save. ([#18995](https://github.com/craftcms/cms/pull/18995))
- Fixed a bug where Single sections had Max Authors settings. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixed a bug where Channel and Structure sections didn’t have Max Authors settings. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixed a bug where sections’ Min Authors settings were defaulting to `1` when blank. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixes a bug where the “View entry” permission was listed twice for Single sections, causing a SQL error when both were selected. ([#19002](https://github.com/craftcms/cms/pull/19002))
- Fixes a bug where user group handles weren’t getting auto-generated. ([#19002](https://github.com/craftcms/cms/pull/19002))
- Fixed a JavaScript error that could occur in the control panel when a custom element was registered more than once.
- Fixed a bug where control panel action menu items could trigger their action twice when clicked.
- Fixed a bug where legacy control panel JavaScript wasn’t loaded and initialized on all control panel pages.
- Fixed a styling issue with user avatars.

## 6.0.0-alpha.5 - 2026-05-27

- Improved emoji shortcode handling performance for strings without shortcode delimiters.
- Improved element query performance by caching element source table column listings in memory.
- Improved nested entry type resolution by avoiding unnecessary owner element queries.
- Added Laravel event dispatching to Craft’s `Yiisoft\Translator\Translator` instance, enabling `Yiisoft\Translator\Event\MissingTranslationEvent` listeners. ([#18952](https://github.com/craftcms/cms/pull/18952))
- The `loginPath` config setting is now `false` by default.
- Renamed the `PluginsLoaded` event to `PluginsRegistered`. ([#18973](https://github.com/craftcms/cms/pull/18973))
- Updated Twig to 3.27. ([#18980](https://github.com/craftcms/cms/pull/18980))
- Fixed some errors that could occur when running Craft through Laravel Octane ([#18921](https://github.com/craftcms/cms/pull/18921))
- Fixed an error that occurred when rendering the database update screen outside control panel template mode.
- Fixed an error that occurred when Redis was configured as the session driver.
- Fixed a bug where legacy control panel URL rules couldn’t route directly to templates. ([#18972](https://github.com/craftcms/cms/pull/18972))
- Fixed an error that could occur when request context was dehydrated after a matched element route was resolved.
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could skip setters that used a same-name private backing property.
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could attempt to assign read-only, private-set, protected-set, or setterless virtual properties.
- Fixed a bug where publishable Craft assets were registered during web requests.
- Fixed a bug where eager-loading didn’t treat address, content block, and entry queries as nested element queries.
- Fixed a bug where lazy eager-loading nested element fields could reuse owner criteria and return the wrong elements.
- Fixed an error that occurred when Updates were cached and deserialized.
- Fixed an error that prevented link fields from saving.
- Fixed a bug where Money fields could throw an error during element validation when the field value was falsy.
- Fixed a bug where `CraftCms\Cms\Validation\Contracts\Validatable::prepareForValidation()` wasn’t called consistently, and plain `Validatable` classes without a configured ruleset couldn’t be validated. ([#18944](https://github.com/craftcms/cms/pull/18944))
- Fixed a bug where invalid element query filters could return all results. ([#18937](https://github.com/craftcms/cms/pull/18937))
- Fixed an error that occurred when uploading assets to fields with dynamic default upload locations. ([#18949](https://github.com/craftcms/cms/pull/18949))
- Fixed a bug where Craft could look for the license key in `config/license.key` instead of `config/craft/license.key`.
- Fixed a styling issue that occurred when editable table cells had a `code` class. ([#18900](https://github.com/craftcms/cms/issues/18900))

## 6.0.0-alpha.4 - 2026-05-19

- Added support for plugins to register Laravel scheduled tasks that run via `php artisan schedule:run`.
- Updated `yiisoft/html` to 4.1.0. ([#18920](https://github.com/craftcms/cms/pull/18920))
- Updated `elvanto/litemoji` to 5.2.0. ([#18917](https://github.com/craftcms/cms/pull/18917))
- Updated `pragmarx/google2fa` to 9.0.0. ([#18919](https://github.com/craftcms/cms/pull/18919))
- Fixed an error that occurred when opening element selector modals with string `sources` values. ([#18915](https://github.com/craftcms/cms/pull/18915))
- Added “Elements” and “Deprecations” panels to Laravel Debugbar. ([#18897](https://github.com/craftcms/cms/pull/18897))
- Fixed a bug where legacy redirect responses were not being returned as a redirect ([#18893](https://github.com/craftcms/cms/pull/18893))
- Fixed an error that could occur when saving filesystems with null transient settings. ([#18909](https://github.com/craftcms/cms/pull/18909))
- Fixed a bug where plugin routes were not being registered with the `web` middleware.
- Fixed an error that could occur when storing image transform indexes. ([#18899](https://github.com/craftcms/cms/pull/18899))
- Fixed an error when `loginPath` or `logoutPath` was set to `false` in `GeneralConfig`. ([#18894](https://github.com/craftcms/cms/issue/18894))
- Fixed a bug where plugin-registered Twig variables weren’t available via the `craft` template variable. ([#18903](https://github.com/craftcms/cms/pull/18903))
- Fixed an error that occurred when using the legacy cache service with a new dependency object ([#18904](https://github.com/craftcms/cms/pull/18904))
- Fixed a bug where clearing submitted values could retain the previous value. ([#18905](https://github.com/craftcms/cms/issues/18905))
- Fixed a bug where a legacy Yii action controller would result in a 404 when returning `null` as the response ([#18907](https://github.com/craftcms/cms/pull/18907))
- Fixed an error that could occur because `ol` and `ul` were not normalizing the attributes ([#18907](https://github.com/craftcms/cms/pull/18907))
- Fixed an error that occurred when trying to upload an asset through a legacy filesystem plugin ([#18908](https://github.com/craftcms/cms/pull/18908))

## 6.0.0-alpha.3 - 2026-05-15

- Added the `compiledTemplatesPath` config setting. ([#18861](https://github.com/craftcms/cms/pull/18861))
- Added a missing migration that adds `minAuthors` to the section table ([#18875](https://github.com/craftcms/cms/pull/18875))
- Fixed a bug where the `cpTrigger` would be appended twice to the URL after running migrations from the control panel. ([#18858](https://github.com/craftcms/cms/pull/18858))
- Fixed an error that occurred when rendering element indexes with blank source headings. ([#18891](https://github.com/craftcms/cms/pull/18891))
- Fixed an error that occurred when uninstalling plugins. ([#18862](https://github.com/craftcms/cms/pull/18862))
- Fixed an error that could occur when control panel HTML values were passed as `Stringable` objects. ([#18883](https://github.com/craftcms/cms/pull/18883))
- Fixed a bug where plugin package config files could affect plugin settings before being published. ([#18885](https://github.com/craftcms/cms/pull/18885))
- Fixed a bug where the control panel would continuously poll for queue job info, even if there were no active jobs. ([#18853](https://github.com/craftcms/cms/issues/18853))
- Fixed a bug where legacy redirect responses were not being handled. ([#18860](https://github.com/craftcms/cms/pull/18860))
- Fixed a bug where email addresses couldn’t be saved when applying unpublished user drafts. ([#18882](https://github.com/craftcms/cms/pull/18882))
- Fixed a bug where the Updates utility wasn’t showing available updates. ([#18884](https://github.com/craftcms/cms/pull/18884))

## 6.0.0-alpha.2 - 2026-05-13

- Added support for SQLite backups and restores. ([#18803](https://github.com/craftcms/cms/pull/18803))
- Added support for Symfony-style array config files in `config/craft/sanitizers/`. ([#18808](https://github.com/craftcms/cms/pull/18808))
- Added support for configuring the system time zone during installation. ([#18794](https://github.com/craftcms/cms/pull/18794))
- Added the legacy `paginate` Twig variable back.
- Added CP access permission checks to control panel action routes.
- The `craftAsset()` Twig function now resolves to Vite versioned assets. ([#18801](https://github.com/craftcms/cms/pull/18801))
- Renamed the `|money` Twig filter’s `formatLocale` argument to `locale`.
- Deprecated the `csrfTokenName`, `enableCsrfCookie`, and `enableCsrfProtection` general config settings. ([#18806](https://github.com/craftcms/cms/pull/18806))
- Removed support for the Debug Toolbar. [Laravel Debugbar](https://laraveldebugbar.com) can be used instead. ([#18812](https://github.com/craftcms/cms/pull/18812))
- Improved control panel icon loading performance.
- Fixed a PHP error that occurred when saving a Structure section with a Max Levels value. ([#18809](https://github.com/craftcms/cms/issues/18809))
- Fixed a bug where plugin settings pages were missing registered scripts and styles. ([#18815](https://github.com/craftcms/cms/pull/18815))
- Fixed a PHP error that occurred when saving an entry type. ([#18816](https://github.com/craftcms/cms/pull/18816))
- Fixed an issue with Typecast where typed setters wouldn’t have precedence over private properties.
- Fixed a bug where control panel templates failed to load on Windows due to mismatched directory separators or drive-letter casing in `CraftCms\Cms\View\TwigEngine`. ([#18804](https://github.com/craftcms/cms/issues/18804))
- Fixed a bug where Craft’s Vite hot file configuration could override the host application’s Vite hot file. ([#18810](https://github.com/craftcms/cms/issues/18810))
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could give private properties precedence over typed setters.
- Fixed a bug where `runQueueAutomatically` wasn’t being respected. ([#18817](https://github.com/craftcms/cms/pull/18817))
- Fixed a bug where `CraftCms\Cms\Validation\Rules\EnvValueRule` could parse boolean values incorrectly.
- Fixed a bug where Blade templates weren’t resolving correctly.
- Fixed a bug where console command aliases weren’t prefixed with `craft:`.
- Fixed a bug where element duplication included IDs.
- Fixed a bug where legacy action requests didn’t resolve correctly when using `DefaultController`.
- Fixed a bug where legacy controller CSRF validation exclusions weren’t always respected.
- Fixed a bug where legacy plugin settings weren’t saved correctly.
- Fixed a bug where legacy redirects and streamed responses weren’t forwarded to Laravel correctly.
- Fixed a bug where preview requests could write template caches.
- Fixed a bug where private templates couldn’t be used as section templates.
- Fixed a bug where the legacy `Application::EVENT_AFTER_REQUEST` event wasn’t triggered.
- Fixed a bug where the password reset email throttle applied to control panel requests.
- Fixed a bug where the Support widget could render unescaped HTML.
- Fixed a bug where Twig macros were allowed in sandboxed templates.
- Fixed a bug where user passkeys weren’t persisting.
- Fixed an error that occurred when validation errors stored in the session were plain arrays.
- Fixed compatibility issues with legacy element queries.
- Fixed compatibility issues with legacy fallback routes.
- Fixed legacy model behavior when unsafe config keys were provided.
- Fixed permissions enforcement when duplicating Matrix blocks.
- Fixed permissions enforcement when saving user fields through generic element save endpoints.
- Fixed a bug where a `yii\base\InvalidConfigException` would be thrown when a Yii2-based plugin registered an asset bundle. ([#18818](https://github.com/craftcms/cms/issues/18818))
- Fixed a bug where using `{{ successMessageInput() }}` would not decrypt the resulting message for the flash message.
- Fixed a bug where a missing widget from an uninstalled plugin would throw instead of mapping to a MissingWidget.
- Fixed a bug where an address’ ownership ids could be overridden unintentionally.
- Fixed a bug where `getHasSsoIdentity()` would return `false` when Socialite was not installed but the user had an SSO identity.
- Fixed a bug where the site’s offline status was not being enforced on matched element routes.
- Fixed a user photo validation issue with file extensions.
- Fixed a bug where legacy controllers could return `null` but were not considered handled.
- Improved performance of the dashboard by reducing the amount of queries for widgets
- Fixed a bug where criteria added to clones of executed element queries could be ignored. ([#18826](https://github.com/craftcms/cms/pull/18826))
- Fixed a bug where Yii2 behaviors registered from plugins weren’t getting attached at the right time. ([#18824](https://github.com/craftcms/cms/issues/18824))
- Fixed an error that occurred when running `craft:install` in environments where Laravel Prompts can only render tasks statically. ([#18830](https://github.com/craftcms/cms/pull/18830))
- Fix legacy model array access for null properties ([#18843](https://github.com/craftcms/cms/pull/18843))
- Fix Twig access to Laravel error bags ([#18841](https://github.com/craftcms/cms/pull/18841))
- Fixed a bug where singles were throwing an exception ([#18845](https://github.com/craftcms/cms/issue/18845))
- Fixed a bug where plugin settings were not saving consistently ([#18849](https://github.com/craftcms/cms/pull/18849))
- Fixed a bug where deprecated string-based `orderBy()` arguments could cause element queries to throw an unknown column exception. ([#18852](https://github.com/craftcms/cms/pull/18852))
- Fixed a bug where Laravel HTTP exceptions weren’t passed to legacy error handler event listeners. ([#18854](https://github.com/craftcms/cms/pull/18854))
- Fixed a bug where FieldLayouts were being flashed to the session as arrays. ([#18847](https://github.com/craftcms/cms/pull/18847))

## 6.0.0-alpha.1 - 2026-05-06

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Deprecated support for categories, global sets, and tags. ([#18009](https://github.com/craftcms/cms/pull/18009))

### Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\DateTimeHelper`.
- Added `CraftCms\Cms\Support\File`.
- Added `CraftCms\Cms\Support\Facades\Path`.
- Added `CraftCms\Cms\Support\Facades\Markdown`.
- Added `CraftCms\Cms\Support\Path`.
- Added `CraftCms\Cms\Support\Str`.
- Added `CraftCms\Cms\Support\Url`.
- Added `CraftCms\Cms\action_url()`, `CraftCms\Cms\cp_url()`, and `CraftCms\Cms\site_url()` helper functions.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\ConfigHelper`. `CraftCms\Cms\Support\Config` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper`. `CraftCms\Cms\Support\DateTimeHelper` should be used instead.
- Deprecated `craft\helpers\Diff`. `CraftCms\Cms\Support\Diff` should be used instead.
- Deprecated `craft\helpers\ElementHelper`. `CraftCms\Cms\Element\ElementHelper` should be used for core element helper APIs, `CraftCms\Cms\Element\ElementSources` for source lookup, `CraftCms\Cms\Element\ElementAttributeRenderer` for attribute rendering, `CraftCms\Cms\Element\Drafts` for provisional draft helpers, `CraftCms\Cms\Field\Enums\TranslationMethod` for translation helpers, and `Illuminate\Support\Facades\Context` with `CraftCms\Cms\Element\Drafts::CONTEXT_PREVIEW_USER_ID` for preview-user context.
- Deprecated `craft\helpers\Html`. `CraftCms\Cms\Support\Html` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` should be used for HTML sanitization, and `CraftCms\Cms\Support\Str` should be used for UTF-8 cleanup instead.
- Deprecated `craft\helpers\HtmlPurifier::process()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::sanitize()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::cleanUtf8()`.
- Deprecated `craft\helpers\HtmlPurifier::convertToUtf8()`. `CraftCms\Cms\Support\Str::convertToUtf8()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::configure()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::defaults()` or a custom sanitizer registration should be used instead.
- Deprecated `config/craft/htmlpurifier/*.json` sanitizer config files. Sanitizers should be registered on `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` instead.
- Deprecated `craft\services\Path`. `CraftCms\Cms\Support\Path` should be used instead.
- Deprecated `craft\helpers\SessionHelper`. `Illuminate\Support\Facades\Session` should be used instead.
- Deprecated `craft\helpers\Sequence`. `CraftCms\Cms\Support\Sequence` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
- Deprecated `craft\helpers\App::env()`, `CraftCms\Cms\Support\Env::get()` should be used instead.
- Deprecated `craft\markdown\Markdown`, `craft\markdown\GithubMarkdown`, `craft\markdown\MarkdownExtra`, and `craft\markdown\PreEncodedMarkdown`. `CraftCms\Cms\Support\Facades\Markdown` should be used instead.
- Deprecated `craft\helpers\DateRange`. `CraftCms\Cms\Shared\Enums\DateRangeType` and `CraftCms\Cms\Shared\Enums\DateRangePeriod` should be used instead.
- Deprecated `craft\helpers\Cp`. One of the following classes should be used instead:
  - `CraftCms\Cms\Cp\Alerts`
  - `CraftCms\Cms\Cp\FormFields`
  - `CraftCms\Cms\Cp\Html\ContentHtml`
  - `CraftCms\Cms\Cp\Html\ElementHtml`
  - `CraftCms\Cms\Cp\Html\ElementIndexHtml`
  - `CraftCms\Cms\Cp\Html\MenuHtml`
  - `CraftCms\Cms\Cp\Html\PreviewHtml`
  - `CraftCms\Cms\Cp\Html\StatusHtml`
  - `CraftCms\Cms\Cp\Icons`
  - `CraftCms\Cms\Cp\RequestedSite`
- Deprecated `craft\helpers\Json`. `CraftCms\Cms\Support\Json` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.
- Deprecated `craft\enums\Color`. `CraftCms\Cms\Support\Enums\Color` should be used instead.
- Deprecated `craft\enums\AttributeStatus`. `CraftCms\Cms\Element\Enums\AttributeStatus` should be used instead.
- Deprecated `craft\enums\CmsEdition`. `CraftCms\Cms\Edition` should be used instead.
- Deprecated `craft\enums\ElementIndexViewMode`. `CraftCms\Cms\Element\Enums\ElementIndexViewMode` should be used instead.
- Deprecated `craft\enums\LicenseKeyStatus`. `CraftCms\Cms\Support\Enums\LicenseKeyStatus` should be used instead.
- Deprecated `craft\enums\MenuItemType`. `CraftCms\Cms\Element\Enums\MenuItemType` should be used instead.
- Deprecated `craft\enums\PropagationMethod`. `CraftCms\Cms\Element\Enums\PropagationMethod` should be used instead.
- Deprecated `craft\enums\TimePeriod`. `CraftCms\Cms\Support\Enums\TimePeriod` should be used instead.
- Deprecated `craft\services\Gc`. `CraftCms\Cms\GarbageCollection\GarbageCollection` should be used instead.
- Deprecated `craft\services\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\helpers\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\nameparsing\CustomLanguage`. `CraftCms\Cms\Shared\Nameparser\CustomLanguage` should be used instead.
- Deprecated `craft\helpers\App`. The following classes/methods should be used instead:
  - #### General helpers
  - `App:devMode()` -> `app()->hasDebugModeEnabled()`
  - `App:parseBooleanEnv()` --> `\CraftCms\Cms\Support\Env::parseBoolean()`
  - `App:normalizeValue()` --> `\CraftCms\Cms\normalizeValue()`
  - `App:maxPowerCaptain()` --> `\CraftCms\Cms\maxPowerCaptain()`
  - `App:silence()` --> `\CraftCms\Cms\silence()`
  - `App:backtrace()` --> `\CraftCms\Cms\backtraceAsString()`
  - #### Env
  - `App:env()` --> `\CraftCms\Cms\Support\Env::get()`
  - `App:parseEnv()` --> `\CraftCms\Cms\Support\Env::parse()`
  - #### PHP
  - `App:phpVersion()` --> `\CraftCms\Cms\Support\PHP::version()`
  - `App:extensionVersion()` --> `\CraftCms\Cms\Support\PHP::extensionVersion()`
  - `App:phpConfigValueAsBool()` --> `\CraftCms\Cms\Support\PHP::configValueAsBool()`
  - `App:phpConfigValueInBytes()` --> `\CraftCms\Cms\Support\PHP::configValueInBytes()`
  - `App:phpSizeToBytes()` --> `\CraftCms\Cms\Support\PHP::sizeToBytes()`
  - `App:phpConfigValueAsPaths()` --> `\CraftCms\Cms\Support\PHP::configValueAsPaths()`
  - `App:normalizePhpPaths()` --> `\CraftCms\Cms\Support\PHP::normalizePaths()`
  - `App:isPathAllowed()` --> `\CraftCms\Cms\Support\PHP::isPathAllowed()`
  - `App:phpExecutable()` --> `\CraftCms\Cms\Support\PHP::executable()`
  - `App:testIniSet()` --> `\CraftCms\Cms\Support\PHP::testIniSet()`
  - `App:checkForValidIconv()` --> `\CraftCms\Cms\Support\PHP::checkForValidIconv()`
  - `App:supportsIdn()` --> `\CraftCms\Cms\Support\PHP::supportsIdn()`
  - #### License
  - `App:licenseKey()` --> `app(\CraftCms\Cms\License\License::class)->key()`
  - `App:licensingIssues()` --> `app(\CraftCms\Cms\License\License::class)->issues()`
  - `App:licenseShunCookieName()` --> `app(\CraftCms\Cms\License\License::class)->shunCookieName()`
  - `App:licensingIssuesHash()` --> `app(\CraftCms\Cms\License\License::class)->issuesHash()`
  -
- Deprecated `Craft::createGuzzleClient()`. `CraftCms\Cms\Support\Facades\Http::create()` should be used instead.
- Deprecated `craft\helpers\FileHelper`. `CraftCms\Cms\Support\File` should be used instead.
- Deprecated `craft\helpers\UrlHelper`. `CraftCms\Cms\Support\Url` should be used instead.

#### Deprecator
- Added `CraftCms\Cms\Support\Facades\Deprecator`.
- Added `CraftCms\Cms\Deprecator\Commands\ClearDeprecations`.
- Removed `craft\console\controllers\ClearDeprecationsController.php`.
- Deprecated `craft\services\Deprecator`. `CraftCms\Cms\Deprecator\Deprecator` should be used instead.
- Deprecated `craft\models\DeprecationError`. `CraftCms\Cms\Deprecator\Models\DeprecationError` should be used instead.
- Deprecated `craft\errors\DeprecationException`. `CraftCms\Cms\Deprecator\Exceptions\DeprecationException` should be used instead.

#### Console commands
- Added `php craft twig:cache` - Precompile Twig views
- Added `php craft twig:clear` - Clear precompiled Twig views
- `craft\console\controllers\EnvController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\Env\EnvRemoveCommand` => `php craft env:remove`
  - `CraftCms\Cms\Console\Commands\Env\EnvSetCommand` => `php craft env:set`
  - `CraftCms\Cms\Console\Commands\Env\EnvShowCommand` => `php craft env:show`
- `craft\console\controllers\IndexAssetsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand` => `php craft index-assets:cleanup`
  - `CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand` => `php craft index-assets:all`
  - `CraftCms\Cms\Asset\Commands\IndexOneAssetCommand` => `php craft index-assets:one`
- `craft\console\controllers\BaseSystemStatusController`, `craft\console\controllers\OnController`, and `craft\console\controllers\OffController` have been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\System\OnCommand` => `php craft on`
  - `CraftCms\Cms\Console\Commands\System\OffCommand` => `php craft off`
- `craft\console\controllers\ElementsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Element\Commands\DeleteCommand` => `php craft elements:delete`
  - `CraftCms\Cms\Element\Commands\DeleteAllOfTypeCommand` => `php craft elements:delete-all-of-type`
  - `CraftCms\Cms\Element\Commands\RestoreCommand` => `php craft elements:restore`
- `craft\console\controllers\UpdateStatusesController` has been removed in favor of the class below:
  - `CraftCms\Cms\Entry\Commands\UpdateStatusesCommand` => `php craft update-statuses`
- `craft\console\controllers\utils\FixElementUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixElementUidsCommand` => `php craft utils:fix-element-uids`
- `craft\console\controllers\utils\FixFieldLayoutUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixFieldLayoutUidsCommand` => `php craft utils:fix-field-layout-uids`
- `craft\console\controllers\utils\PruneOrphanedEntriesController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneOrphanedEntriesCommand` => `php craft utils:prune-orphaned-entries`
- `craft\console\controllers\utils\PruneProvisionalDraftsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneProvisionalDraftsCommand` => `php craft utils:prune-provisional-drafts`
- `craft\console\controllers\utils\PruneRevisionsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneRevisionsCommand` => `php craft utils:prune-revisions`
- `craft\console\controllers\utils\RepairController::actionProjectConfig()` has been removed in favor of the class below:
  - `CraftCms\Cms\ProjectConfig\Commands\RepairCommand` => `php craft project-config:repair`

#### Mutex

Craft’s Mutex classes have been deprecated. [Laravel’s atomic locking](https://laravel.com/docs/12.x/cache#atomic-locks) should be used instead.

- Deprecated `craft\mutex\Mutex`
- Deprecated `craft\mutex\MutexTrait`
- Deprecated `Craft::$app->getMutex()`

#### Components
- Deprecated `craft\base\ComponentInterface`. `CraftCms\Cms\Component\Contracts\ComponentInterface` should be used instead.
- Deprecated `craft\base\ConfigurableComponentInterface`. `CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface` should be used instead.
- Deprecated `craft\base\SavableComponentInterface`. `CraftCms\Cms\Component\Contracts\SavableComponentInterface` should be used instead.

#### Dashboard & Widgets

##### Controllers
- Removed `craft\controllers\DashboardController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Dashboard\DashboardController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController`

##### Deprecations
- Deprecated `Craft::$app->getDashboard()`. `app(\CraftCms\Cms\Dashboard\Dashboard::class)` should be used instead.
- Deprecated `craft\services\Dashboard`. `CraftCms\Cms\Dashboard\Dashboard` should be used instead.
- Deprecated `craft\base\Widget`. `CraftCms\Cms\Dashboard\Widgets\Widget` should be used instead.
- Deprecated `craft\base\WidgetInterface`. `CraftCms\Cms\Dashboard\Contracts\WidgetInterface` should be used instead.
- Deprecated `craft\base\WidgetTrait`.
- Deprecated `craft\widgets\CraftSupport`. `CraftCms\Cms\Dashboard\Widgets\CraftSupport` should be used instead.
- Deprecated `craft\widgets\Feed`. `CraftCms\Cms\Dashboard\Widgets\Feed` should be used instead.
- Deprecated `craft\widgets\MissingWidget`. `CraftCms\Cms\Dashboard\Widgets\MissingWidget` should be used instead.
- Deprecated `craft\widgets\MyDrafts`. `CraftCms\Cms\Dashboard\Widgets\MyDrafts` should be used instead.
- Deprecated `craft\widgets\NewUsers`. `CraftCms\Cms\Dashboard\Widgets\NewUsers` should be used instead.
- Deprecated `craft\widgets\QuickPost`. `CraftCms\Cms\Dashboard\Widgets\QuickPost` should be used instead.
- Deprecated `craft\widgets\RecentEntries`. `CraftCms\Cms\Dashboard\Widgets\RecentEntries` should be used instead.
- Deprecated `craft\widgets\Updates`. `CraftCms\Cms\Dashboard\Widgets\Updates` should be used instead.
- Deprecated `craft\records\Widget`. `CraftCms\Cms\Dashboard\Models\Widget` should be used instead.

##### Events

- Deprecated `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES`. `CraftCms\Cms\Dashboard\Events\WidgetTypesResolving` should be used instead.
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
  - `craft\services\Dashboard::EVENT_BEFORE_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleting`
  - `craft\services\Dashboard::EVENT_AFTER_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleted`

### Address

- Added `CraftCms\Cms\Support\Facades\Addresses`.

### Assets

- Added `CraftCms\Cms\Asset\AssetsHelper`.
- Added `CraftCms\Cms\Support\Facades\Assets`.
- Added `CraftCms\Cms\Support\Facades\AssetIndexer` facade.
- Added `CraftCms\Cms\Support\Facades\Folders`.
- Deprecated `craft\helpers\Assets`. `CraftCms\Cms\Asset\AssetsHelper` should be used instead.
- Deprecated `craft\services\Assets`. `CraftCms\Cms\Asset\Assets` and `CraftCms\Cms\Asset\Folders` should be used instead.
- Deprecated `\craft\records\Asset`. `\CraftCms\Cms\Asset\Models\Asset` should be used instead.
- Deprecated `\craft\records\AssetIndexData`. `\CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `\craft\records\AssetIndexingSession`. `\CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `\craft\records\Volume`. `\CraftCms\Cms\Asset\Models\Volume` should be used instead.
- Deprecated `\craft\records\VolumeFolder`. `\CraftCms\Cms\Asset\Models\VolumeFolder` should be used instead.
- Deprecated `\craft\controllers\AssetIndexesController`. `\CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController` should be used instead.
- Deprecated `craft\services\AssetIndexer`. `CraftCms\Cms\Asset\AssetIndexer` should be used instead.
- Deprecated `craft\models\AssetIndexData`. `CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `craft\models\AssetIndexingSession`. `CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `craft\errors\AssetException`. `CraftCms\Cms\Asset\Exceptions\AssetException` should be used instead.
- Deprecated `craft\errors\AssetDisallowedExtensionException`. `CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException` should be used instead.
- Deprecated `craft\errors\AssetNotIndexableException`. `CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException` should be used instead.
- Deprecated `craft\errors\FileException`. `CraftCms\Cms\Asset\Exceptions\FileException` should be used instead.
- Deprecated `craft\errors\ImageException`. `CraftCms\Cms\Asset\Exceptions\ImageException` should be used instead.
- Deprecated `craft\errors\ImageTransformException`. `CraftCms\Cms\Asset\Exceptions\ImageTransformException` should be used instead.
- Deprecated `craft\errors\MissingAssetException`. `CraftCms\Cms\Asset\Exceptions\MissingAssetException` should be used instead.
- Deprecated `craft\errors\MissingVolumeFolderException`. `CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException` should be used instead.
- Deprecated `craft\errors\VolumeException`. `CraftCms\Cms\Asset\Exceptions\VolumeException` should be used instead.

#### Events

- Added `CraftCms\Cms\Asset\Events\AssetFileKindsResolving`.
- Added `CraftCms\Cms\Asset\Events\SetAssetFilename`.
- Deprecated `craft\events\SetAssetFilenameEvent`. `CraftCms\Cms\Asset\Events\SetAssetFilename` should be used instead.
- Deprecated `craft\events\RegisterAssetFileKindsEvent`. `CraftCms\Cms\Asset\Events\AssetFileKindsResolving` should be used instead.
- Deprecated `craft\events\ReplaceAssetEvent` in favor of the following new events:
  - `craft\services\Assets::EVENT_BEFORE_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplacing`
  - `craft\services\Assets::EVENT_AFTER_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplaced`
- Deprecated `craft\events\DefineAssetThumbUrlEvent`. `CraftCms\Cms\Asset\Events\ThumbUrlResolving` should be used instead.
- Deprecated `craft\events\AssetPreviewEvent`. `CraftCms\Cms\Asset\Events\PreviewHandlerResolving` should be used instead.

### Auth

- Refactored the authentication system to use Laravel’s authentication system.
- Added `CraftCms\Cms\Auth\Events\SettingPassword`.
- Added `CraftCms\Cms\User\Notifications\ResetPasswordNotification`.
- Deprecated `craft\services\Auth`. `CraftCms\Cms\Auth\Auth` should be used instead.
- Deprecated `craft\web\User`. `auth()->user()` or `CraftCms\Cms\User\Elements\User` methods should be used instead.
- Deprecated `craft\events\AuthenticateUserEvent`. `CraftCms\Cms\Auth\Events\UserAuthenticating` should be used instead.
- Deprecated `\craft\records\Authenticator`. `\CraftCms\Cms\Auth\Models\Authenticator` should be used instead.
- Deprecated `\craft\records\RecoveryCodes`. `\CraftCms\Cms\Auth\Models\RecoveryCodes` should be used instead.
- Deprecated `\craft\records\SsoIdentity`. `\CraftCms\Cms\Auth\Models\SsoIdentity` should be used instead.
- Deprecated `\craft\records\WebAuthn`. `\CraftCms\Cms\Auth\Models\WebAuthn` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::authorize`. `CraftCms\Cms\Auth\SessionAuth::authorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::deauthorize`. `CraftCms\Cms\Auth\SessionAuth::deauthorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::checkAuthorization`. `CraftCms\Cms\Auth\SessionAuth::checkAuthorization` should be used instead.
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker()->tokenExists($user, $code)` should be used instead.
- Deprecated the `elevatedSessionDuration` general config setting. The `auth.password_timeout` config value should be used instead. To disable password confirmation (elevated sessions), you now set this value to `-1` instead of `0`.
  - Elevated sessions now work through [Laravel’s password confirmation](https://laravel.com/docs/12.x/authentication#password-confirmation) system.
- Removed `craft\controllers\AuthController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Users\AuthMethodController`
  - `CraftCms\Cms\Http\Controllers\Users\PasskeysController`
  - `CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController`
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.

#### Authorization

Craft 6 now uses [Laravel’s authorization system](https://laravel.com/docs/12.x/authorization) for element authorization checks.

##### Added

- Added `CraftCms\Cms\Auth\Events\ElementAuthorizing` event for customizing element authorization.
- Added `CraftCms\Cms\Element\Policies\ElementPolicy` base policy for element authorization.
- Added element-specific authorization policies:
  - `CraftCms\Cms\Address\Policies\AddressPolicy`
  - `CraftCms\Cms\Asset\Policies\AssetPolicy`
  - `CraftCms\Cms\Entry\Policies\EntryPolicy`
  - `CraftCms\Cms\User\Policies\UserPolicy`
  - `CraftCms\Cms\Field\Policies\ContentBlockPolicy`

#### Passkeys

- Added `CraftCms\Cms\Auth\Passkeys\Passkeys`.
- Deprecated `craft\services\Auth` passkey methods. The following should be used instead:
  - `Auth::hasPasskeys()` -> `app(Passkeys::class)->hasPasskeys()`
  - `Auth::getPasskeys()` -> `app(Passkeys::class)->getPasskeys()`
  - `Auth::getPasskeyCreationOptions()` -> `app(Passkeys::class)->getPasskeyCreationOptions()`
  - `Auth::verifyPasskeyCreationResponse()` -> `app(Passkeys::class)->verifyPasskeyCreationResponse()`
  - `Auth::getPasskeyRequestOptions()` -> `app(Passkeys::class)->getPasskeyRequestOptions()`
  - `Auth::verifyPasskey()` -> `app(Passkeys::class)->verifyPasskey()`
  - `Auth::deletePasskey()` -> `app(Passkeys::class)->deletePasskey()`
- Deprecated `craft\auth\passkeys\CredentialRepository`. `CraftCms\Cms\Auth\Passkeys\CredentialRepository` should be used instead.
- Deprecated `craft\auth\passkeys\WebauthnServer`. `CraftCms\Cms\Auth\Passkeys\WebauthnServer` should be used instead.

### Conditions

#### Added

- Added `CraftCms\Cms\Support\Facades\Conditions`.

#### Controllers

- Removed `craft\controllers\ConditionsController`. `CraftCms\Cms\Http\Controllers\ConditionsController` should be used instead.

#### Deprecations

##### Service

- Deprecated `craft\services\Conditions`. `CraftCms\Cms\Condition\Conditions` should be used instead.

##### Base Conditions

- Deprecated `craft\base\conditions\ConditionInterface`. `CraftCms\Cms\Condition\Contracts\ConditionInterface` should be used instead.
- Deprecated `craft\base\conditions\ConditionRuleInterface`. `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface` should be used instead.
- Deprecated `craft\base\conditions\BaseCondition`. `CraftCms\Cms\Condition\BaseCondition` should be used instead.
- Deprecated `craft\base\conditions\BaseConditionRule`. `CraftCms\Cms\Condition\BaseConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseTextConditionRule`. `CraftCms\Cms\Condition\BaseTextConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseNumberConditionRule`. `CraftCms\Cms\Condition\BaseNumberConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseSelectConditionRule`. `CraftCms\Cms\Condition\BaseSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseMultiSelectConditionRule`. `CraftCms\Cms\Condition\BaseMultiSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseLightswitchConditionRule`. `CraftCms\Cms\Condition\BaseLightswitchConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseDateRangeConditionRule`. `CraftCms\Cms\Condition\BaseDateRangeConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseElementSelectConditionRule`. `CraftCms\Cms\Condition\BaseElementSelectConditionRule` should be used instead.

##### Elements

- Deprecated `craft\elements\conditions\ElementCondition`. `CraftCms\Cms\Element\Conditions\ElementCondition` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionRuleInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface` should be used instead.
- Deprecated `craft\elements\conditions\HintableConditionRuleTrait`. `CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait` should be used instead.
- Deprecated `craft\elements\conditions\TitleConditionRule`. `CraftCms\Cms\Element\Conditions\TitleConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SlugConditionRule`. `CraftCms\Cms\Element\Conditions\SlugConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\UriConditionRule`. `CraftCms\Cms\Element\Conditions\UriConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\IdConditionRule`. `CraftCms\Cms\Element\Conditions\IdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\StatusConditionRule`. `CraftCms\Cms\Element\Conditions\StatusConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasUrlConditionRule`. `CraftCms\Cms\Element\Conditions\HasUrlConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasDescendantsRule`. `CraftCms\Cms\Element\Conditions\HasDescendantsRule` should be used instead.
- Deprecated `craft\elements\conditions\LevelConditionRule`. `CraftCms\Cms\Element\Conditions\LevelConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateCreatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateCreatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateUpdatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteConditionRule`. `CraftCms\Cms\Element\Conditions\SiteConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteGroupConditionRule`. `CraftCms\Cms\Element\Conditions\SiteGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\LanguageConditionRule`. `CraftCms\Cms\Element\Conditions\LanguageConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\RelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\RelatedToConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\NotRelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule` should be used instead.

##### Entries

- Deprecated `craft\elements\conditions\entries\EntryCondition`. `CraftCms\Cms\Entry\Conditions\EntryCondition` should be used instead.
- Deprecated `craft\elements\conditions\entries\PostDateConditionRule`. `CraftCms\Cms\Entry\Conditions\PostDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ExpiryDateConditionRule`. `CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SectionConditionRule`. `CraftCms\Cms\Entry\Conditions\SectionConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\TypeConditionRule`. `CraftCms\Cms\Entry\Conditions\TypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorGroupConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ViewableConditionRule`. `CraftCms\Cms\Entry\Conditions\ViewableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SavableConditionRule`. `CraftCms\Cms\Entry\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\FieldConditionRule`. `CraftCms\Cms\Entry\Conditions\FieldConditionRule` should be used instead.

##### Users

- Deprecated `craft\elements\conditions\users\UserCondition`. `CraftCms\Cms\User\Conditions\UserCondition` should be used instead.
- Deprecated `craft\elements\conditions\users\UsernameConditionRule`. `CraftCms\Cms\User\Conditions\UsernameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\EmailConditionRule`. `CraftCms\Cms\User\Conditions\EmailConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\FirstNameConditionRule`. `CraftCms\Cms\User\Conditions\FirstNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastNameConditionRule`. `CraftCms\Cms\User\Conditions\LastNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\GroupConditionRule`. `CraftCms\Cms\User\Conditions\GroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AdminConditionRule`. `CraftCms\Cms\User\Conditions\AdminConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\CredentialedConditionRule`. `CraftCms\Cms\User\Conditions\CredentialedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastLoginDateConditionRule`. `CraftCms\Cms\User\Conditions\LastLoginDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AffiliatedSiteConditionRule`. `CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule` should be used instead.

##### Assets

- Deprecated `craft\elements\conditions\assets\AssetCondition`. `CraftCms\Cms\Asset\Conditions\AssetCondition` should be used instead.
- Deprecated `craft\elements\conditions\assets\VolumeConditionRule`. `CraftCms\Cms\Asset\Conditions\VolumeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FilenameConditionRule`. `CraftCms\Cms\Asset\Conditions\FilenameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileTypeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileTypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileSizeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileSizeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HeightConditionRule`. `CraftCms\Cms\Asset\Conditions\HeightConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\WidthConditionRule`. `CraftCms\Cms\Asset\Conditions\WidthConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\DateModifiedConditionRule`. `CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HasAltConditionRule`. `CraftCms\Cms\Asset\Conditions\HasAltConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\UploaderConditionRule`. `CraftCms\Cms\Asset\Conditions\UploaderConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\SavableConditionRule`. `CraftCms\Cms\Asset\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\ViewableConditionRule`. `CraftCms\Cms\Asset\Conditions\ViewableConditionRule` should be used instead.

##### Addresses

- Deprecated `craft\elements\conditions\addresses\AddressCondition`. `CraftCms\Cms\Address\Conditions\AddressCondition` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FullNameConditionRule`. `CraftCms\Cms\Address\Conditions\FullNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationTaxIdConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\LocalityConditionRule`. `CraftCms\Cms\Address\Conditions\LocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\DependentLocalityConditionRule`. `CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\PostalCodeConditionRule`. `CraftCms\Cms\Address\Conditions\PostalCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\SortingCodeConditionRule`. `CraftCms\Cms\Address\Conditions\SortingCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\CountryConditionRule`. `CraftCms\Cms\Address\Conditions\CountryConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AdministrativeAreaConditionRule`. `CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine1ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine1ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine2ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine3ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FieldConditionRule`. `CraftCms\Cms\Address\Conditions\FieldConditionRule` should be used instead.

##### Fields

- Deprecated `craft\fields\conditions\FieldConditionRuleInterface`. `CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface` should be used instead.
- Deprecated `craft\fields\conditions\FieldConditionRuleTrait`. `CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait` should be used instead.
- Deprecated `craft\fields\conditions\GeneratedFieldConditionRule`. `CraftCms\Cms\Field\Conditions\GeneratedFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\TextFieldConditionRule`. `CraftCms\Cms\Field\Conditions\TextFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\NumberFieldConditionRule`. `CraftCms\Cms\Field\Conditions\NumberFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\MoneyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\OptionsFieldConditionRule`. `CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\RelationalFieldConditionRule`. `CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LightswitchFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\EmptyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\DateFieldConditionRule`. `CraftCms\Cms\Field\Conditions\DateFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\CountryFieldConditionRule`. `CraftCms\Cms\Field\Conditions\CountryFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LinkFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LinkFieldConditionRule` should be used instead.

##### Events

- Deprecated `craft\events\RegisterConditionRulesEvent`. `CraftCms\Cms\Condition\Events\ConditionRulesResolving` should be used instead.

### Drafts

- Deprecated `craft\services\Drafts`. `CraftCms\Cms\Element\Drafts` should be used instead.
- Deprecated `craft\events\DraftEvent`. One of the events extending `CraftCms\Cms\Element\Events\DraftEvent` should be used instead.
- Deprecated `craft\behaviors\DraftBehavior`. `CraftCms\Cms\Element\Concerns\Draftable` should be used instead.

### Elements

- Added `CraftCms\Cms\Element\ElementCaches` and `CraftCms\Cms\Support\Facades\ElementCaches`.
  - Deprecated `craft\services\Elements::getIsCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::isCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::startCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::startCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::collectCacheTags()`. `CraftCms\Cms\Element\ElementCaches::collectCacheTags()` should be used instead.
  - Deprecated `craft\services\Elements::setCacheExpiryDate()`. `CraftCms\Cms\Element\ElementCaches::setCacheExpiryDate()` should be used instead.
  - Deprecated `craft\services\Elements::collectCacheInfoForElement()`. `CraftCms\Cms\Element\ElementCaches::collectCacheInfoForElement()` should be used instead.
  - Deprecated `craft\services\Elements::stopCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::stopCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateAllCaches()`. `CraftCms\Cms\Element\ElementCaches::invalidateAll()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateCachesForElementType()`. `CraftCms\Cms\Element\ElementCaches::invalidateForElementType()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateCachesForElement()`. `CraftCms\Cms\Element\ElementCaches::invalidateForElement()` should be used instead.
- Added `CraftCms\Cms\Element\BulkOp\BulkOps`, `CraftCms\Cms\Element\BulkOp\BulkOpDeferrals`, and `CraftCms\Cms\Support\Facades\BulkOps`.
  - Deprecated `craft\services\Elements::getBulkOpKeys()`. `CraftCms\Cms\Element\BulkOp\BulkOps::activeKeys()` should be used instead.
  - Deprecated `craft\services\Elements::beginBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::start()` should be used instead.
  - Deprecated `craft\services\Elements::resumeBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::resume()` should be used instead.
  - Deprecated `craft\services\Elements::endBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::end()` should be used instead.
  - Deprecated `craft\services\Elements::trackElementInBulkOps()`. `CraftCms\Cms\Element\BulkOp\BulkOps::trackElement()` should be used instead.
  - Deprecated `craft\services\Elements::ensureBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::ensure()` should be used instead.
  - Deprecated `craft\events\BulkOpEvent::defer()`. `CraftCms\Cms\Element\BulkOp\BulkOps::defer()` should be used instead.
- Added `CraftCms\Cms\Element\ElementActivity`, `CraftCms\Cms\Element\Data\ElementActivity`, `CraftCms\Cms\Element\Enums\ElementActivityType`, and `CraftCms\Cms\Support\Facades\ElementActivity`.
  - Deprecated `craft\services\Elements::getRecentActivity()`. `CraftCms\Cms\Element\ElementActivity::getRecentActivity()` should be used instead.
  - Deprecated `craft\services\Elements::trackActivity()`. `CraftCms\Cms\Element\ElementActivity::trackActivity()` should be used instead.
- Added `CraftCms\Cms\Element\Actions\ElementAction`, `CraftCms\Cms\Element\ElementActions`, `CraftCms\Cms\Element\Contracts\DeleteActionInterface`, `CraftCms\Cms\Element\Contracts\ElementActionInterface`, `CraftCms\Cms\Element\Events\ElementActionPerformed`, `CraftCms\Cms\Element\Events\ElementActionPerforming`, `CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController`, and `CraftCms\Cms\Support\Facades\ElementActions`.
- Added Laravel-native element action classes under `CraftCms\Cms\Element\Actions`, `CraftCms\Cms\Asset\Actions`, `CraftCms\Cms\Entry\Actions`, and `CraftCms\Cms\User\Actions`.
- Added `CraftCms\Cms\Element\ElementExporters`, `CraftCms\Cms\Element\Contracts\ElementExporterInterface`, `CraftCms\Cms\Element\Exporters\ElementExporter`, `CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController`, and `CraftCms\Cms\Support\Facades\ElementExporters`.
- Added Laravel-native element exporter classes under `CraftCms\Cms\Element\Exporters`.
- Deprecated `craft\errors\InvalidTypeException`. `CraftCms\Cms\Element\Exceptions\InvalidTypeException` should be used instead.
- Deprecated `craft\errors\UnsupportedSiteException`. `CraftCms\Cms\Element\Exceptions\UnsupportedSiteException` should be used instead.
- Deprecated `craft\base\ElementAction`, `craft\base\ElementActionInterface`, `craft\elements\actions\DeleteActionInterface`, and the legacy `craft\elements\actions\*` classes. The corresponding `CraftCms\Cms\Element\Actions\*`, `CraftCms\Cms\Asset\Actions\*`, `CraftCms\Cms\Entry\Actions\*`, and `CraftCms\Cms\User\Actions\*` classes should be used instead.
- Deprecated `craft\base\ElementExporter`, `craft\base\ElementExporterInterface`, and the legacy `craft\elements\exporters\*` classes. The corresponding `CraftCms\Cms\Element\Exporters\*` classes should be used instead.

#### Validation

Craft 6 introduces a new validation system that uses Laravel’s Validator instead of Yii2’s model validation.

##### Added

- Added `CraftCms\Cms\Validation\Contracts\Validatable` interface for classes that support Laravel-style validation.
- Added `CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset` interface for classes that use a `Ruleset` class to define validation rules.
- Added `CraftCms\Cms\Validation\Ruleset` abstract class for defining validation rules, messages, and preparation logic.
- Added `CraftCms\Cms\Validation\Attributes\Ruleset` PHP attribute for specifying a component’s ruleset class.
- Added `CraftCms\Cms\Validation\Concerns\Validates` trait for simple validation support.
- Added `CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset` trait for ruleset-based validation.
- Added `CraftCms\Cms\Validation\Concerns\HasScenarios` trait for scenario-based validation filtering.
- Added `CraftCms\Cms\Validation\Concerns\InteractsWithValidator` trait providing common validator interactions.
- Added `CraftCms\Cms\Element\Validation\ElementRules` abstract class for defining element-specific validation rules.
- Added `CraftCms\Cms\Element\Validation\Events\ValidationRulesResolving` event for plugins to modify element validation rules.
- Added `CraftCms\Cms\Element\Validation\Rules\ElementUriRule` for validating element URIs.
- Added element-specific ruleset classes:
  - `CraftCms\Cms\Address\Validation\AddressRules`
  - `CraftCms\Cms\Asset\Validation\AssetRules`
  - `CraftCms\Cms\Entry\Validation\EntryRules`
  - `CraftCms\Cms\User\Validation\UserRules`
  - `CraftCms\Cms\Field\Elements\ContentBlockRules`
- Added `CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule` for validating asset locations.
- Added `CraftCms\Cms\User\Validation\Rules\UserPasswordRule` for validating user passwords.
- Added `CraftCms\Cms\User\Validation\Rules\UsernameRule` for validating usernames.
- Added `CraftCms\Cms\Validation\Rules\UniqueCaseInsensitiveRule` for case-insensitive unique validation.
- Added `CraftCms\Cms\Validation\Rules\DisallowMb4` for disallowing 4-byte UTF-8 characters.
- Added `CraftCms\Cms\Validation\Rules\MoneyRule` for validating money values.

##### Changed

- `FieldInterface::getElementValidationRules()` has been replaced by `FieldInterface::getElementRules()` which returns rules in Laravel’s validation format.
- Added `FieldInterface::prepareForElementValidation()` for preparing field values before validation.
- Validation rules are now defined as Laravel-style arrays (e.g., `['required', 'string', 'max:255']`).

##### Deprecations

- Deprecated `craft\base\Model::hasErrors()`. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.
- Deprecated `craft\base\Model::getErrors()`. Use `->errors()->get($attribute)` or `->errors()->getMessages()` instead.
- Deprecated `craft\base\Model::addErrors()`. Use `->errors()->add($attribute, $message)` instead.
- Deprecated `craft\base\Model::clearErrors()`. Use `->errors()->forget()` instead.
- Deprecated `CraftCms\Cms\Component\Concerns\ValidatableComponent`. Use `CraftCms\Cms\Validation\Concerns\Validates` instead.
- Deprecated `CraftCms\Cms\Component\Contracts\ValidatableComponentInterface`. Use `CraftCms\Cms\Validation\Contracts\Validatable` instead.
- Deprecated `\craft\records\ContentBlock`. `\CraftCms\Cms\Element\Models\ContentBlock` should be used instead.
- Deprecated `\craft\records\Draft`. `\CraftCms\Cms\Element\Models\Draft` should be used instead.
- Deprecated `\craft\records\Element`. `\CraftCms\Cms\Element\Models\Element` should be used instead.
- Deprecated `\craft\records\Element_SiteSettings`. `\CraftCms\Cms\Element\Models\ElementSiteSettings` should be used instead.
- Deprecated `\craft\records\Revision`. `\CraftCms\Cms\Element\Models\Revision` should be used instead.

### ElementSources

- Deprecated `craft\services\ElementSources`. `CraftCms\Cms\Element\ElementSources` should be used instead.
- Deprecated `craft\events\DefineSourceSortOptionsEvent`. `CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving` should be used instead.
- Deprecated `craft\events\DefineSourceTableAttributesEvent`. `CraftCms\Cms\Element\Events\ElementSourceTableAttributesResolving` should be used instead.

### Element Queries

- Deprecated `craft\elements\db\ElementRelationParamParser`. `CraftCms\Cms\Database\ElementRelationParamFilter` should be used instead.
- Deprecated `craft\elements\db\NestedElementQueryInterface`. `CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface` should be used instead.
- Deprecated `craft\elements\db\NestedElementQueryTrait`. `CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements` should be used instead.
- Deprecated `craft\elements\db\OrderByPlaceholderExpression`. `CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression` should be used instead.
- Deprecated `\craft\elements\db\AddressQuery`. `\CraftCms\Cms\Element\Queries\AddressQuery` should be used instead.
- Deprecated `\craft\elements\db\AssetQuery` `\CraftCms\Cms\Element\Queries\AssetQuery` should be used instead.
- Deprecated `\craft\elements\db\ContentBlockQuery` `\CraftCms\Cms\Element\Queries\ContentBlockQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQuery` `\CraftCms\Cms\Element\Queries\ElementQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQueryInterface`
- Deprecated `\craft\elements\db\EntryQuery` `\CraftCms\Cms\Element\Queries\EntryQuery` should be used instead.
- Deprecated `\craft\elements\db\UserQuery` `\CraftCms\Cms\Element\Queries\UserQuery` should be used instead.

### Entries & Entry Types

- Updated entry type table pagination to return Laravel-style pagination metadata and use the `pageTrigger` query parameter.
- Deprecated `craft\services\Entries`. `CraftCms\Cms\Entry\Entries` and `CraftCms\Cms\Entry\EntryTypes` should be used instead.
- Deprecated `craft\models\EntryType`. `CraftCms\Cms\Entry\Data\EntryType` should be used instead.
- Deprecated `craft\records\EntryType`. `CraftCms\Cms\Entry\Models\EntryType` should be used instead.
- Deprecated `craft\records\Entry`. `CraftCms\Cms\Entry\Models\Entry` should be used instead.
- Deprecated `craft\errors\EntryTypeNotFoundException`. `CraftCms\Cms\Entry\Exceptions\EntryTypeNotFoundException` should be used instead.
- Deprecated `craft\events\EntryTypeEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Section\Events\DeletingEntryType`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE` => `CraftCms\Cms\Entry\Events\ApplyingEntryTypeDelete`
  - `craft\services\Entries::EVENT_AFTER_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaving`
  - `craft\services\Entries::EVENT_AFTER_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaved`
- Removed `craft\controllers\EntriesController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Entries\CreateEntryController`
  - `CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController`
  - `CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController`
  - `CraftCms\Cms\Http\Controllers\Entries\StoreEntryController`
- Removed `craft\controllers\EntryTypesController` in favor of `CraftCms\Cms\Http\Controllers\EntryTypesController`
- Removed `craft\console\controllers\EntryTypesController` in favor of:
  - `CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand`

### Component

- Added `CraftCms\Cms\Component\Component` base class, replacing Yii2’s `BaseObject`/`Component` with config hydration, magic getters/setters, and `Arrayable` support.
- Added `CraftCms\Cms\Component\Exceptions\InvalidCallException`, replacing `yii\base\InvalidCallException`.
- Added `CraftCms\Cms\Component\Exceptions\UnknownPropertyException`, replacing `yii\base\UnknownPropertyException`.

### Field Layouts

#### Added

- Added `CraftCms\Cms\FieldLayout\FieldLayoutForm`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormTab`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormElement`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutServiceProvider`.
- Added `CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout` trait.

#### Deprecations
- Deprecated `craft\models\FieldLayout`. `CraftCms\Cms\FieldLayout\FieldLayout` should be used instead.
- Deprecated `craft\models\FieldLayoutTab`. `CraftCms\Cms\FieldLayout\FieldLayoutTab` should be used instead.
- Deprecated `craft\base\FieldLayoutComponent`. `CraftCms\Cms\FieldLayout\FieldLayoutComponent` should be used instead.
- Deprecated `craft\base\FieldLayoutElement`. `CraftCms\Cms\FieldLayout\FieldLayoutElement` should be used instead.
- Deprecated `craft\base\FieldLayoutProviderInterface`. `CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface` should be used instead.
- Deprecated `craft\records\FieldLayout`. `CraftCms\Cms\FieldLayout\Models\FieldLayout` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseNativeField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseUiElement`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement` should be used instead.
- Deprecated `craft\fieldlayoutelements\CustomField`. `CraftCms\Cms\FieldLayout\LayoutElements\CustomField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Heading`. `CraftCms\Cms\FieldLayout\LayoutElements\Heading` should be used instead.
- Deprecated `craft\fieldlayoutelements\HorizontalRule`. `CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule` should be used instead.
- Deprecated `craft\fieldlayoutelements\Html`. `CraftCms\Cms\FieldLayout\LayoutElements\Html` should be used instead.
- Deprecated `craft\fieldlayoutelements\LineBreak`. `CraftCms\Cms\FieldLayout\LayoutElements\LineBreak` should be used instead.
- Deprecated `craft\fieldlayoutelements\Markdown`. `CraftCms\Cms\FieldLayout\LayoutElements\Markdown` should be used instead.
- Deprecated `craft\fieldlayoutelements\Template`. `CraftCms\Cms\FieldLayout\LayoutElements\Template` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextField` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextareaField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextareaField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Tip`. `CraftCms\Cms\FieldLayout\LayoutElements\Tip` should be used instead.
- Deprecated `craft\fieldlayoutelements\TitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\TitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\AddressField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\AddressField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\CountryCodeField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\CountryCodeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LabelField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LabelField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LatLongField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LatLongField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationTaxIdField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationTaxIdField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AssetTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AltField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AltField` should be used instead.
- Deprecated `craft\fieldlayoutelements\entries\EntryTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\UsernameField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\UsernameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\EmailField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\AffiliatedSiteField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\AffiliatedSiteField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\PhotoField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\PhotoField` should be used instead.
- Deprecated `craft\events\CreateFieldLayoutFormEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutFormCreating` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutCustomFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutCustomFieldsResolving` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutElementsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutUIElementsResolving` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving` should be used instead.
- Deprecated `craft\events\DefineShowFieldLayoutComponentInFormEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving` should be used instead.
- Deprecated `craft\events\DefineFieldActionsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentActionMenuItemsResolving` should be used instead.

### Fields

- Updated field index pagination to return Laravel-style pagination metadata and use the configured `pageTrigger` query parameter.
- Removed `craft\controllers\FieldsController` in favor of `CraftCms\Cms\Http\Controllers\FieldsController`.
- Removed `craft\controllers\MatrixController`. `CraftCms\Cms\Http\Controllers\MatrixController` should be used instead.
- Removed `craft\controllers\RelationalFieldsController`. `CraftCms\Cms\Http\Controllers\RelationalFieldsController` should be used instead.
- Deprecated `craft\errors\InvalidFieldException`. `CraftCms\Cms\Field\Exceptions\InvalidFieldException` should be used instead.
- Deprecated `craft\fields\data\ColorData`. `CraftCms\Cms\Field\Data\ColorData` should be used instead.
- Deprecated `craft\fields\data\IconData`. `CraftCms\Cms\Field\Data\IconData` should be used instead.
- Deprecated `craft\fields\data\JsonData`. `CraftCms\Cms\Field\Data\JsonData` should be used instead.
- Deprecated `craft\fields\data\LinkData`. `CraftCms\Cms\Field\Data\LinkData` should be used instead.
- Deprecated `craft\fields\data\MultiOptionsFieldData`. `CraftCms\Cms\Field\Data\MultiOptionsFieldData` should be used instead.
- Deprecated `craft\fields\data\OptionData`. `CraftCms\Cms\Field\Data\OptionData` should be used instead.
- Deprecated `craft\fields\data\SingleOptionFieldData`. `CraftCms\Cms\Field\Data\SingleOptionFieldData` should be used instead.
- Deprecated `craft\fields\linktypes\Asset`. `CraftCms\Cms\Field\LinkTypes\Asset` should be used instead.
- Deprecated `craft\fields\linktypes\BaseElementLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseElementLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseTextLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseTextLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\Category`. `CraftCms\Cms\Field\LinkTypes\Category` should be used instead.
- Deprecated `craft\fields\linktypes\Email`. `CraftCms\Cms\Field\LinkTypes\Email` should be used instead.
- Deprecated `craft\fields\linktypes\Entry`. `CraftCms\Cms\Field\LinkTypes\Entry` should be used instead.
- Deprecated `craft\fields\linktypes\Phone`. `CraftCms\Cms\Field\LinkTypes\Phone` should be used instead.
- Deprecated `craft\fields\linktypes\Sms`. `CraftCms\Cms\Field\LinkTypes\Sms` should be used instead.
- Deprecated `craft\fields\linktypes\Url`. `CraftCms\Cms\Field\LinkTypes\Url` should be used instead.
- Deprecated `craft\fields\Addresses`. `CraftCms\Cms\Field\Addresses` should be used instead.
- Deprecated `craft\fields\Assets`. `CraftCms\Cms\Field\Assets` should be used instead.
- Deprecated `craft\fields\BaseOptionsField`. `CraftCms\Cms\Field\BaseOptionsField` should be used instead.
- Deprecated `craft\fields\BaseRelationField`. `CraftCms\Cms\Field\BaseRelationField` should be used instead.
- Deprecated `craft\fields\ButtonGroup`. `CraftCms\Cms\Field\ButtonGroup` should be used instead.
- Deprecated `craft\fields\Categories`. `CraftCms\Cms\Field\Categories` should be used instead.
- Deprecated `craft\fields\Checkboxes`. `CraftCms\Cms\Field\Checkboxes` should be used instead.
- Deprecated `craft\fields\Color`. `CraftCms\Cms\Field\Color` should be used instead.
- Deprecated `craft\fields\ContentBlock`. `CraftCms\Cms\Field\ContentBlock` should be used instead.
- Deprecated `craft\fields\Country`. `CraftCms\Cms\Field\Country` should be used instead.
- Deprecated `craft\fields\Date`. `CraftCms\Cms\Field\Date` should be used instead.
- Deprecated `craft\fields\Dropdown`. `CraftCms\Cms\Field\Dropdown` should be used instead.
- Deprecated `craft\fields\Email`. `CraftCms\Cms\Field\Email` should be used instead.
- Deprecated `craft\fields\Entries`. `CraftCms\Cms\Field\Entries` should be used instead.
- Deprecated `craft\fields\Icon`. `CraftCms\Cms\Field\Icon` should be used instead.
- Deprecated `craft\fields\Json`. `CraftCms\Cms\Field\Json` should be used instead.
- Deprecated `craft\fields\Lightswitch`. `CraftCms\Cms\Field\Lightswitch` should be used instead.
- Deprecated `craft\fields\Link`. `CraftCms\Cms\Field\Link` should be used instead.
- Deprecated `craft\fields\Matrix`. `CraftCms\Cms\Field\Matrix` should be used instead.
- Deprecated `craft\fields\MissingField`. `CraftCms\Cms\Field\MissingField` should be used instead.
- Deprecated `craft\fields\Money`. `CraftCms\Cms\Field\Money` should be used instead.
- Deprecated `craft\fields\MultiSelect`. `CraftCms\Cms\Field\MultiSelect` should be used instead.
- Deprecated `craft\fields\Number`. `CraftCms\Cms\Field\Number` should be used instead.
- Deprecated `craft\fields\PlainText`. `CraftCms\Cms\Field\PlainText` should be used instead.
- Deprecated `craft\fields\RadioButtons`. `CraftCms\Cms\Field\RadioButtons` should be used instead.
- Deprecated `craft\fields\Range`. `CraftCms\Cms\Field\Range` should be used instead.
- Deprecated `craft\fields\Table`. `CraftCms\Cms\Field\Table` should be used instead.
- Deprecated `craft\fields\Tags`. `CraftCms\Cms\Field\Tags` should be used instead.
- Deprecated `craft\fields\Time`. `CraftCms\Cms\Field\Time` should be used instead.
- Deprecated `craft\fields\Url`. `CraftCms\Cms\Field\Url` should be used instead.
- Deprecated `craft\fields\Users`. `CraftCms\Cms\Field\Users` should be used instead.
- Deprecated `craft\services\Fields`. `CraftCms\Cms\Field\Fields` should be used instead.

### Filesystems

- Deprecated `craft\errors\InvalidSubpathException`. `CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException` should be used instead.

### GQL

- Deprecated `\craft\records\GqlSchema`. `\CraftCms\Cms\Gql\Models\GqlSchema` should be used instead.
- Deprecated `\craft\records\GqlToken`. `\CraftCms\Cms\Gql\Models\GqlToken` should be used instead.

### HTTP

- Deprecated the `errorTemplatePrefix` general config setting. Configure [Laravel’s custom error pages](https://laravel.com/docs/13.x/errors#custom-http-error-pages) instead.
- Deprecated `craft\filters\BasicHttpAuthLogin`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthStatic`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthTrait`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\Cors`. Use Laravel’s CORS settings instead. (see https://laravel.com/docs/12.x/routing#cors)
- Deprecated `craft\filters\Headers`. Use Laravel middleware instead. (see https://laravel.com/docs/middleware)
- Deprecated `craft\filters\ConditionalFilterTrait`.
- Deprecated `craft\filters\SiteFilterTrait`.
- Deprecated `craft\filters\UtilityAccess`.
- Deprecated `craft\controllers\AppController::actionLicensingIssues()`. `CraftCms\Cms\Http\Middleware\EnforceLicenses` should be used instead.
- Removed `craft\controllers\AppController::actionHealthCheck()`. `CraftCms\Cms\Http\Controllers\App\HealthCheckController` should be used instead.
- Removed `craft\controllers\AppController::actionGetCpAlerts()` and `actionShunCpAlert()`. `CraftCms\Cms\Http\Controllers\App\CpAlertsController` should be used instead.
- Removed `craft\controllers\AppController::actionIconPickerOptions()`. Use `CraftCms\Cms\Http\Controllers\IconController::pickerOptions()` instead.
- Removed `craft\controllers\AppController::actionSetLicenseShunCookie()`. `CraftCms\Cms\Http\Controllers\App\LicensesController::setShunCookie()` should be used instead.
- Removed `craft\controllers\AppController::actionGetPluginLicenseInfo()` and `actionUpdatePluginLicense()`. `CraftCms\Cms\Http\Controllers\App\PluginsController` should be used instead.
- Removed `craft\controllers\AppController::actionBrokenImage()`. `CraftCms\Cms\Http\Middleware\ShowBrokenImage` should be used instead.
- Removed `craft\controllers\AppController::actionRenderElements()` and `actionRenderComponents()`. `CraftCms\Cms\Http\Controllers\App\RenderController` should be used instead.
- Removed `craft\controllers\NotFoundController`. Laravel’s exception handling should be used instead.
- Removed the header-setting logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\SetHeaders` middleware handles this functionality.
- Removed the licensing issues screen logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\EnforceLicenses` middleware handles this functionality.
- Removed `craft\controllers\AppController::actionTryEdition()` and `actionSwitchToLicensedEdition()` in favor of `CraftCms\Cms\Http\Controllers\EditionController`.

### Mail

- Added `CraftCms\Cms\Email\Commands\SendTestMailCommand`.
- Added `CraftCms\Cms\Email\Mailables\CraftMailable`, a base mailable class that automatically applies project config email settings (from, replyTo, mailer) with site-specific overrides.
- Added `CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable`.
- Deprecated `Craft::$app->getMailer()`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\mail\Mailer`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\helpers\MailerHelper`. Laravel mail configuration and drivers should be used instead.
- Deprecated the `testToEmailAddress` general config setting. `Illuminate\Support\Facades\Mail::alwaysTo()` should be used instead.
- Deprecated `craft\mail\Mailer::$template`, `craft\mail\Mailer::$siteOverrides`, `craft\models\MailSettings::$template`, and `craft\models\MailSettings::$siteOverrides`. Laravel mailable views and environment-specific Laravel mailers should be used instead.
- Removed legacy `projectConfig.email` mail settings and mail transport adapter configuration in favor of Laravel’s `mail` config and drivers.

### Migrations

Craft and Yii’s migrations have been removed in favor of [Laravel migrations](https://laravel.com/docs/12.x/migrations).

The `php craft fields:merge` and `php craft entry-types:merge` commands will now generate Laravel migrations.

- Deprecated `craft\db\Migration`. `CraftCms\Cms\Database\Migration` should be used instead.
- Deprecated `craft\db\MigrationManager`
- Removed `craft\helpers\MigrationHelper` as it was deprecated since 4.0.0.
- Removed `craft\console\controllers\InstallController` in favor of:
  - `CraftCms\Cms\Console\Commands\InstallCommand`
  - `CraftCms\Cms\Console\Commands\InstallCheckCommand`
- Removed `craft\console\controllers\MigrateController` in favor of:
  - `CraftCms\Cms\Database\Commands\MigrateCommand`
- Removed `craft\console\controllers\UpController` in favor of:
  - `CraftCms\Cms\Console\Commands\UpCommand`

### Plugins

#### Added
- The base `CraftCms\Cms\Plugin\Plugin` class is now a [Laravel ServiceProvider](https://laravel.com/docs/12.x/providers) which provides a new way to register components for your plugins.

#### Deprecations

- Deprecated `craft\services\Plugins`. `CraftCms\Cms\Plugin\Plugins` should be used instead.
- Deprecated `craft\base\Plugin`. `CraftCms\Cms\Plugin\Plugin` should be used instead.
- Deprecated `craft\base\PluginTrait`.
- Deprecated `craft\base\PluginInterface`. `CraftCms\Cms\Plugin\Contracts\PluginInterface` should be used instead.
- Deprecated `craft\errors\InvalidPluginException`. `CraftCms\Cms\Plugin\Exceptions\InvalidPluginException` should be used instead.
- Deprecated `craft\errors\InvalidLicenseKeyException`. `CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException` should be used instead.

#### Controllers
- Removed `craft\controllers\PluginsController`. Use `CraftCms\Cms\Http\Controllers\PluginsController` instead.

#### Commands
- Removed `craft\console\controllers\PluginController` in favor of:
  - `CraftCms\Cms\Plugin\Commands\DisableCommand` -> `php craft plugin:disable`
  - `CraftCms\Cms\Plugin\Commands\EnableCommand` -> `php craft plugin:enable`
  - `CraftCms\Cms\Plugin\Commands\InstallCommand` -> `php craft plugin:install`
  - `CraftCms\Cms\Plugin\Commands\UninstallCommand` -> `php craft plugin:uninstall`
  - `CraftCms\Cms\Plugin\Commands\ListCommand` -> `php craft plugin:list`

#### Events
- Deprecated `craft\events\PluginEvent` in favor of the following new events:
  - `craft\base\Plugin::EVENT_BEFORE_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\base\Plugin::EVENT_AFTER_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\services\Plugins::EVENT_BEFORE_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginDisabling`;
  - `craft\services\Plugins::EVENT_BEFORE_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginEnabling`;
  - `craft\services\Plugins::EVENT_BEFORE_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginInstalling`;
  - `craft\services\Plugins::EVENT_BEFORE_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\PluginsLoading`;
  - `craft\services\Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\SavingPluginSettings`;
  - `craft\services\Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalling`;
  - `craft\services\Plugins::EVENT_AFTER_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginDisabled`;
  - `craft\services\Plugins::EVENT_AFTER_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginEnabled`;
  - `craft\services\Plugins::EVENT_AFTER_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginInstalled`;
  - `craft\services\Plugins::EVENT_AFTER_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\PluginsLoaded`;
  - `craft\services\Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\PluginSettingsSaved`;
  - `craft\services\Plugins::EVENT_AFTER_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalled`;

### Request

- Added `Request::isPreview()` macro for detecting preview requests via `x-craft-preview` or `x-craft-live-preview` parameters.
- Added `Request::isCpRequest()`, `Request::isSiteRequest()`, `Request::isActionRequest()`, `Request::actionSegments()`, `Request::actionSegmentsToRoute()`, `Request::duplicateWithUri()`, `Request::getToken()`, and `Request::getSigned()` macros.
- Updated paginated requests to resolve the current page from the configured `pageTrigger` query parameter rather than path-style pagination segments.

### Security

- Added `CraftCms\Cms\Support\Security`.
- Added `CraftCms\Cms\Support\Facades\Security`.
- Added `CraftCms\Cms\Http\Middleware\AddLogContext`.
- Deprecated `Craft::$app->getSecurity()` in favor of Laravel’s Hash and Crypt facades, or `CraftCms\Cms\Support\Facades\Security`.
- Deprecated the `blowfishHashCost` general config setting in favor of Laravel’s `hashing.bcrypt.rounds` config or the `BCRYPT_ROUNDS` environment variable.

### Updates

The `craft\services\Updates` internal service has been removed. `CraftCms\Cms\Update\Updates` should be used instead.

Moved the following controllers:
- `craft\controllers\ConfigSyncController` => `CraftCms\Cms\Http\Controllers\ConfigSyncController`
- `craft\controllers\InstallController` => `CraftCms\Cms\Http\Controllers\InstallController`
- `craft\controllers\MigrateController` => `CraftCms\Cms\Http\Controllers\MigrateController`
- `craft\controllers\PluginStoreController` => `CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController`
- `craft\controllers\PluginStore\InstallController` => `CraftCms\Cms\Http\Controllers\PluginStore\InstallController`
- `craft\controllers\PluginStore\RemoveController` => `CraftCms\Cms\Http\Controllers\PluginStore\RemoveController`
- `craft\controllers\UpdaterController` => `CraftCms\Cms\Http\Controllers\Updates\UpdaterController`
- `craft\controllers\UpdatesController` => `CraftCms\Cms\Http\Controllers\Updates\UpdatesController`
- `craft\console\controllers\UpdateController` in favor of these commands:
  - `CraftCms\Cms\Update\Commands\UpdateCommand`
  - `CraftCms\Cms\Update\Commands\ComposerInstallCommand`
  - `CraftCms\Cms\Update\Commands\InfoCommand`

##### Deprecations & removals
- Deprecated `craft\helpers\Install`. `CraftCms\Cms\Site\Concerns\SiteDefaults` should be used instead.
- Deprecated `craft\helpers\Update`. The only method was `checkPhpConstraint` which is now available on `CraftCms\Cms\Support\PHP::checkConstraint()`
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Update\Events\CriticalUpdateReleasedEvent`
- Removed `craft\models\Update`. `CraftCms\Cms\Update\Data\Update` should be used instead.
- Removed `craft\models\UpdateRelease`. `CraftCms\Cms\Update\Data\UpdateRelease` should be used instead.
- Removed `craft\models\Updates`. `CraftCms\Cms\Update\Data\Updates` should be used instead.

#### Users

- Removed `craft\console\controllers\UsersController` in favor of the following commands (signatures are the same):
  - `CraftCms\Cms\User\Commands\ActivationUrlCommand`
  - `CraftCms\Cms\User\Commands\CreateCommand`
  - `CraftCms\Cms\User\Commands\DeleteCommand`
  - `CraftCms\Cms\User\Commands\ImpersonateCommand`
  - `CraftCms\Cms\User\Commands\ListAdminsCommand`
  - `CraftCms\Cms\User\Commands\LogoutAllCommand`
  - `CraftCms\Cms\User\Commands\PasswordResetUrlCommand`
  - `CraftCms\Cms\User\Commands\Remove2faCommand`
  - `CraftCms\Cms\User\Commands\SetPasswordCommand`
  - `CraftCms\Cms\User\Commands\UnlockCommand`

### Project Config

- Deprecated `craft\services\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfig` should be used instead.
- Removed `craft\controllers\ProjectConfigController` in favor of `CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController`
- Removed `craft\console\controllers\PcController` & `craft\console\controllers\ProjectConfigController` in favor of the following commands:
  - `CraftCms\Cms\ProjectConfig\Commands\ApplyCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\DiffCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\ExportCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\GetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RebuildCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RemoveCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\SetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\TouchCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\WriteCommand`
  - All commands can be called using either `php craft project-config` or `php craft pc`
- Deprecated `craft\events\ConfigEvent` in favor of the following events:
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemAdding`
  - `CraftCms\Cms\ProjectConfig\Events\ItemAdded`
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemUpdated`
  - `CraftCms\Cms\ProjectConfig\Events\ItemUpdated`
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemRemoved`
  - `CraftCms\Cms\ProjectConfig\Events\ItemRemoved`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_APPLY_CHANGES`
  - Added `CraftCms\Cms\ProjectConfig\Events\ChangesApplied`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_WRITE_YAML_FILES`
- Added `CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten`
- Deprecated `craft\services\ProjectConfig::EVENT_REBUILD`
  - Added `CraftCms\Cms\ProjectConfig\Events\ProjectConfigRebuilt`
- Removed `craft\errors\BusyResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException`
- Removed `craft\errors\StaleResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException`
- Added `CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException`
- Removed `craft\models\ProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ProjectConfigData`
- Removed `craft\models\ReadOnlyProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData`
- Deprecated `craft\helpers\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfigHelper` should be used instead.

### Revisions

- Deprecated `craft\services\Revisions`. `CraftCms\Cms\Element\Revisions` should be used instead.
- Deprecated `craft\events\RevisionEvent`. One of the events extending `CraftCms\Cms\Element\Events\RevisionEvent` should be used instead.
- Deprecated `craft\behaviors\RevisionBehavior`. `CraftCms\Cms\Element\Concerns\Revisionable` should be used instead.

### Routes

- Deprecated `craft\services\Routes`. `CraftCms\Cms\Route\Routes` should be used instead.
- Using routes in `config/routes.php` is no longer supported. Register routes using [Laravel’s routing](https://laravel.com/docs/12.x/routing) instead.

### Search

- Added `CraftCms\Cms\Support\Facades\Search`.
- Deprecated `craft\services\Search`. `CraftCms\Cms\Search\Search` should be used instead.
- Deprecated `Craft::$app->getSearch()`. `CraftCms\Cms\Support\Facades\Search` or `app(CraftCms\Cms\Search\Search::class)` should be used instead.
- Deprecated `craft\search\SearchQuery`. `CraftCms\Cms\Search\SearchQuery` should be used instead.
- Deprecated `craft\search\SearchQueryTerm`. `CraftCms\Cms\Search\SearchQueryTerm` should be used instead.
- Deprecated `craft\search\SearchQueryTermGroup`. `CraftCms\Cms\Search\SearchQueryTermGroup` should be used instead.
- Deprecated `craft\events\SearchEvent` in favor of the following new events:
  - `craft\services\Search::EVENT_BEFORE_SEARCH` => `CraftCms\Cms\Search\Events\SearchStarting`
  - `craft\services\Search::EVENT_AFTER_SEARCH` => `CraftCms\Cms\Search\Events\SearchPerformed`
  - `craft\services\Search::EVENT_BEFORE_SCORE_RESULTS` => `CraftCms\Cms\Search\Events\ScoringResults`
- Deprecated `craft\events\IndexKeywordsEvent`. `CraftCms\Cms\Search\Events\KeywordsIndexing` should be used instead.

### Sections

- Updated section index pagination to return Laravel-style pagination metadata and use the configured `pageTrigger` query parameter.
- Deprecated the section related methods in `craft\services\Entries`. `CraftCms\Cms\Section\Sections` should be used instead.
- Deprecated `craft\models\Section`. `CraftCms\Cms\Section\Data\Section` should be used instead.
- Deprecated `craft\records\Section`. `CraftCms\Cms\Section\Models\Section` should be used instead.
- Deprecated `craft\models\Section_SiteSettings`. `CraftCms\Cms\Section\Data\SectionSiteSettings` should be used instead.
- Deprecated `craft\records\Section_SiteSettings`. `CraftCms\Cms\Section\Models\SectionSiteSettings` should be used instead.
- Deprecated `craft\events\SectionEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_SECTION` => `CraftCms\Cms\Section\Events\SectionDeleting`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_SECTION_DELETE` => `CraftCms\Cms\Section\Events\SectionDeletionApplying`
  - `craft\services\Entries::EVENT_AFTER_DELETE_SECTION` => `CraftCms\Cms\Section\Events\SectionDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SectionSaving`
  - `craft\services\Entries::EVENT_AFTER_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SectionSaved`
- Removed `craft\controllers\SectionsController` in favor of `CraftCms\Cms\Http\Controllers\SectionsController`
- Removed `craft\console\controllers\SectionsController` in favor of:
  - `CraftCms\Cms\Section\Commands\CreateCommand`
  - `CraftCms\Cms\Section\Commands\DeleteCommand`
- Added `CraftCms\Cms\Section\Enums\DefaultPlacement`
- Added `CraftCms\Cms\Section\Enums\SectionType`
- Deprecated `craft\errors\SectionNotFoundException`. `CraftCms\Cms\Section\Exceptions\SectionNotFoundException` should be used instead.

### Sites

- Deprecated `craft\services\Sites`. `CraftCms\Cms\Site\Sites` should be used instead.
- Deprecated `craft\models\Site`. `CraftCms\Cms\Site\Data\Site` should be used instead.
- Deprecated `craft\models\SiteGroup`. `CraftCms\Cms\Site\Data\SiteGroup` should be used instead.
- Deprecated `craft\records\Site`. `CraftCms\Cms\Site\Models\Site` should be used instead.
- Deprecated `craft\records\SiteGroup`. `CraftCms\Cms\Site\Models\SiteGroup` should be used instead.
- Deprecated `craft\events\SiteEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\events\DeleteSiteEvent`. One of `CraftCms\Cms\Site\Events\SiteDeleting` or `CraftCms\Cms\Site\Events\SiteDeleted` should be used instead.
- Deprecated `craft\events\ReorderSitesEvent`. One of `CraftCms\Cms\Site\Events\SitesReordering` or `CraftCms\Cms\Site\Events\SitesReordered` should be used instead.
- Deprecated `craft\events\SiteGroupEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\errors\SiteNotFoundException`. `CraftCms\Cms\Site\Exceptions\SiteNotFoundException` should be used instead.
- Deprecated `craft\errors\SiteGroupNotFoundException`.

- Removed `craft\controllers\SitesController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\SitesController`
  - `CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController`

### Structures

- Deprecated `craft\services\Structures`. `CraftCms\Cms\Structure\Structures` should be used instead.
- Deprecated `craft\models\Structure`. `CraftCms\Cms\Structure\Data\Structure` should be used instead.
- Deprecated `craft\records\Structure`. `CraftCms\Cms\Structure\Models\Structure` should be used instead.
- Deprecated `craft\records\StructureElement`. `CraftCms\Cms\Structure\Models\StructureElement` should be used instead.
- Replaced `craft\controllers\StructuresController`. `CraftCms\Cms\Http\Controllers\StructuresController`.
- Replaced structure related commands in `craft\console\controllers\RepairController` with:
  - `\CraftCms\Cms\Structure\Commands\RepairCategoryGroupStructureCommand`
  - `\CraftCms\Cms\Structure\Commands\RepairSectionStructureCommand`

### System Messages

- Deprecated `craft\services\SystemMessages`. `CraftCms\Cms\SystemMessage\SystemMessages` should be used instead.
- Deprecated `craft\models\SystemMessage` and `craft\records\SystemMessage`. `CraftCms\Cms\SystemMessage\Models\SystemMessage` should be used instead.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`

### Tokens

- Deprecated `craft\services\Tokens`. `CraftCms\Cms\RouteToken\RouteTokens` should be used instead.
- Deprecated `craft\records\Token`. `CraftCms\Cms\RouteToken\Models\RouteToken` should be used instead.

### Twig

- Updated Twig `{% paginate %}` queries to use Laravel paginators and generate query-string pagination URLs based on the `pageTrigger` general config setting.
- Added `CraftCms\Cms\Twig\Twig` service for managing Twig environments, replacing the Twig management logic previously in `craft\web\View`.
- Added `CraftCms\Cms\View\TemplateManager` for rendering templates, replacing the rendering logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Twig\PageLifecycle` for managing the page rendering lifecycle (head/body placeholder replacement), replacing the page lifecycle logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Support\Facades\Twig` facade, resolving to `CraftCms\Cms\Twig\Twig`.
- Added `CraftCms\Cms\Twig\Environment`, moved from `craft\web\twig\Environment`.
- Added `CraftCms\Cms\Twig\TemplateResolver`.
- Added `CraftCms\Cms\Twig\TemplateLoader`.
- Added `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException`.
- Added helper functions in the `CraftCms\Cms` namespace: `template()`, `sandboxedTemplate()`, `pageTemplate()`, `renderString()`, `renderSandboxedString()`, `renderObjectTemplate()`, `renderSandboxedObjectTemplate()`.
- Added `|sanitize` Twig filter for sanitizing HTML with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers`.
- Deprecated `craft\web\View::getTwig()`. `CraftCms\Cms\Twig\Twig::get()` should be used instead.
- Deprecated `craft\web\View::setTwig()`. `CraftCms\Cms\Twig\Twig::set()` should be used instead.
- Deprecated `craft\web\View::createTwig()`. `CraftCms\Cms\Twig\Twig::create()` should be used instead.
- Deprecated `craft\web\View::registerCpTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerSiteTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::renderTemplate()`. `CraftCms\Cms\View\TemplateManager::renderTemplate()` or the `template()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedTemplate()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedTemplate()` or the `sandboxedTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderPageTemplate()`. `CraftCms\Cms\View\TemplateManager::renderPageTemplate()` or the `pageTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderString()`. `CraftCms\Cms\View\TemplateManager::renderTwigString()` or the `renderString()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedString()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedString()` or the `renderSandboxedString()` helper should be used instead.
- Deprecated `craft\web\View::renderObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::renderObjectTemplate()` or the `renderObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedObjectTemplate()` or the `renderSandboxedObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::normalizeObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::normalizeObjectTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingTemplate()`. `CraftCms\Cms\View\TemplateManager::isRenderingTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingPageTemplate()`. `CraftCms\Cms\View\TemplateManager::isRenderingPageTemplate()` should be used instead.
- Deprecated `craft\web\twig\Environment`. `CraftCms\Cms\Twig\Environment` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_CREATE_TWIG`. `CraftCms\Cms\Twig\Events\TwigCreated` should be used instead.
- Deprecated `craft\web\View::doesTemplateExist()`. `CraftCms\Cms\Twig\TemplateResolver::doesTemplateExist()` should be used instead.
- Deprecated `craft\web\View::resolveTemplate()`. `CraftCms\Cms\Twig\TemplateResolver::resolveTemplate()` should be used instead.
- Deprecated `craft\web\twig\TemplateLoader`. `CraftCms\Cms\Twig\TemplateLoader` should be used instead.
- Deprecated `craft\web\twig\TemplateLoaderException`. `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException` should be used instead.

#### Events

- Added `CraftCms\Cms\Twig\Events\TwigCreated`, dispatched when a Twig environment is created.
- Added `CraftCms\Cms\Twig\Events\TemplateRendering`, dispatched before a template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\TemplateRendered`, dispatched after a template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\PageTemplateRendering`, dispatched before a page template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\PageTemplateRendered`, dispatched after a page template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\PageStarting`, dispatched when page rendering begins.
- Added `CraftCms\Cms\Twig\Events\PageEnded`, dispatched when page rendering ends. Has nullable `headHtml`, `bodyBeginHtml`, and `bodyEndHtml` properties for overriding `HtmlStack` output.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\TemplateRendering` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\TemplateRendered` should be used instead.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\PageTemplateRendering` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\PageTemplateRendered` should be used instead.

### Translations

- Deprecated `craft\i18n\FormatConverter`. `CraftCms\Cms\Translation\FormatConverter` should be used instead.
- Deprecated `craft\i18n\Formatter`. `CraftCms\Cms\Translation\Formatter` should be used instead.
- Deprecated `craft\i18n\I18N`. `CraftCms\Cms\Translation\I18N` should be used instead.
- Deprecated `craft\i18n\Locale`. `CraftCms\Cms\Translation\Locale` should be used instead.
- Deprecated `craft\i18n\MessageFormatter`.
- Deprecated `craft\i18n\PhpMessageSource`.
- Deprecated `craft\i18n\Translation`. `CraftCms\Cms\Support\Facades\I18N` should be used instead.
- Deprecated `Craft::t`. `CraftCms\Cms\t` should be used instead.

### Users

- `CraftCms\Cms\User\Elements\User` now implements `Illuminate\Contracts\Auth\Authenticatable`, `Illuminate\Contracts\Auth\Access\Authorizable`, `Illuminate\Contracts\Auth\CanResetPassword`, and `Illuminate\Contracts\Auth\MustVerifyEmail`.
- Added `CraftCms\Cms\User\Notifications\VerifyEmailNotification`.
- `Users::purgeExpiredPendingUsers()` now joins the `password_reset_tokens` table to find expired pending users.
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker()->tokenExists($user, $code)` should be used instead.
- Removed `craft\controllers\UsersController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Users\ActivateController`.
  - `CraftCms\Cms\Http\Controllers\Users\PasswordController`.
  - `CraftCms\Cms\Http\Controllers\Users\SaveUserController`.
- Removed `\craft\controllers\UserSettingsController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\Users\UserGroupsController`
  - `CraftCms\Cms\Http\Controllers\Settings\Users\UserSettingsController`
- Deprecated `UserGroupEvent` in favor of:
  - `CraftCms\Cms\User\Events\UserGroupSaving`
  - `CraftCms\Cms\User\Events\UserGroupSaved`
  - `CraftCms\Cms\User\Events\UserGroupDeletionApplying`
  - `CraftCms\Cms\User\Events\UserGroupDeleting`
  - `CraftCms\Cms\User\Events\UserGroupDeleted`
- Deprecated `\craft\exceptions\UserGroupNotFoundException`.
- Deprecated `\craft\services\UserGroups`. `CraftCms\Cms\User\UserGroups` should be used instead.
- Deprecated `\craft\models\UserGroup`. `CraftCms\Cms\User\Data\UserGroup` should be used instead.
- Deprecated `\craft\records\User`. `\CraftCms\Cms\User\Models\User` should be used instead.
- Deprecated `\craft\records\UserGroup`. `\CraftCms\Cms\User\Models\UserGroup` should be used instead.
- Deprecated `\craft\records\UserPermission`. `\CraftCms\Cms\User\Models\UserPermission` should be used instead.
- Deprecated `craft\services\UserPermissions`. `CraftCms\Cms\User\UserPermissions` should be used instead.
- Deprecated `craft.app.userPermissions`. `craft.userPermissions` should be used instead.
- Deprecated `craft\events\DefineEditUserScreensEvent`. `CraftCms\Cms\User\Events\EditUserScreensResolving` should be used instead.

### View

- Added `CraftCms\Cms\View\TwigEngine`.
- Added `CraftCms\Cms\View\HtmlStack`.
- Added `CraftCms\Cms\Support\Facades\HtmlStack`.
- Added `CraftCms\Cms\View\Enums\Position` enum.
- Added `CraftCms\Cms\View\InputNamespace`.
- Added `CraftCms\Cms\Support\Facades\InputNamespace`.
- Added `CraftCms\Cms\View\TemplateHooks`.
- Added `CraftCms\Cms\Support\Facades\TemplateHooks`.
- Added `CraftCms\Cms\View\DeltaRegistry`.
- Added `CraftCms\Cms\Support\Facades\DeltaRegistry`.
- Added `CraftCms\Cms\View\TemplateMode` enum.
- Added `CraftCms\Cms\View\Events\CpTemplateRootsResolving`.
- Added `CraftCms\Cms\View\Events\SiteTemplateRootsResolving`.
- Added `CraftCms\Cms\View\TemplateCaches`.
- Added `CraftCms\Cms\View\CacheCollectors\DependencyCollector`.
- Added `CraftCms\Cms\View\CacheCollectors\ResourceCollector`.
- Added `CraftCms\Cms\View\Contracts\CacheCollectorInterface`.
- Added `CraftCms\Cms\View\Data\TemplateCacheContext`.
- Added `CraftCms\Cms\View\Events\TemplateCacheCollectorsResolving`.
- Deprecated `craft\services\TemplateCaches`. `CraftCms\Cms\View\TemplateCaches` should be used instead.
- Deprecated `craft\web\View::registerJs()`. `CraftCms\Cms\View\HtmlStack::js()` should be used instead.
- Deprecated `craft\web\View::registerJsWithVars()`. `CraftCms\Cms\View\HtmlStack::jsWithVars()` should be used instead.
- Deprecated `craft\web\View::registerJsFile()`. `CraftCms\Cms\View\HtmlStack::jsFile()` should be used instead.
- Deprecated `craft\web\View::registerCss()`. `CraftCms\Cms\View\HtmlStack::css()` should be used instead.
- Deprecated `craft\web\View::registerCssFile()`. `CraftCms\Cms\View\HtmlStack::cssFile()` should be used instead.
- Deprecated `craft\web\View::registerScript()`. `CraftCms\Cms\View\HtmlStack::script()` should be used instead.
- Deprecated `craft\web\View::registerScriptWithVars()`. `CraftCms\Cms\View\HtmlStack::scriptWithVars()` should be used instead.
- Deprecated `craft\web\View::registerHtml()`. `CraftCms\Cms\View\HtmlStack::html()` should be used instead.
- Deprecated `craft\web\View::registerMetaTag()`. `CraftCms\Cms\View\HtmlStack::metaTag()` should be used instead.
- Deprecated `craft\web\View::registerLinkTag()`. `CraftCms\Cms\View\HtmlStack::linkTag()` should be used instead.
- Deprecated `craft\web\View::registerTranslations()`. `CraftCms\Cms\View\HtmlStack::translations()` should be used instead.
- Deprecated `craft\web\View::registerJsImport()`. `CraftCms\Cms\View\HtmlStack::jsImport()` should be used instead.
- Deprecated `craft\web\View::registerIcons()`. `CraftCms\Cms\View\HtmlStack::icons()` should be used instead.
- Deprecated `craft\web\View::startJsBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsBuffer()` should be used instead.
- Deprecated `craft\web\View::startScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::startScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::clearScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::clearScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::startHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::clearHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::clearHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::startMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::startMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::clearMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::clearMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::getNamespace()`. `CraftCms\Cms\View\InputNamespace::get()` should be used instead.
- Deprecated `craft\web\View::setNamespace()`. `CraftCms\Cms\View\InputNamespace::set()` should be used instead.
- Deprecated `craft\web\View::namespaceInputs()`. `CraftCms\Cms\View\InputNamespace::namespaceInputs()` should be used instead.
- Deprecated `craft\web\View::namespaceInputName()`. `CraftCms\Cms\View\InputNamespace::namespaceInputName()` should be used instead.
- Deprecated `craft\web\View::namespaceInputId()`. `CraftCms\Cms\View\InputNamespace::namespaceInputId()` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_CP`. `CraftCms\Cms\View\TemplateMode::Cp` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_SITE`. `CraftCms\Cms\View\TemplateMode::Site` should be used instead.
- Deprecated `craft\web\View::getTemplateMode()`. `CraftCms\Cms\View\TemplateMode::get()` should be used instead.
- Deprecated `craft\web\View::setTemplateMode()`. `CraftCms\Cms\View\TemplateMode::set()` should be used instead.
- Deprecated `craft\web\View::getTemplatesPath()`. `CraftCms\Cms\View\TemplateMode::templatesPath()` should be used instead.
- Deprecated `craft\web\View::getCpTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::getSiteTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\CpTemplateRootsResolving` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\SiteTemplateRootsResolving` should be used instead.
- Deprecated `craft\web\View::registerDeltaName()`. `CraftCms\Cms\View\DeltaRegistry::registerName()` should be used instead.
- Deprecated `craft\web\View::getDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getNames()` should be used instead.
- Deprecated `craft\web\View::getModifiedDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getModifiedNames()` should be used instead.
- Deprecated `craft\web\View::setInitialDeltaValue()`. `CraftCms\Cms\View\DeltaRegistry::setInitialValue()` should be used instead.
- Deprecated `craft\web\View::getInitialDeltaValues()`. `CraftCms\Cms\View\DeltaRegistry::getInitialValues()` should be used instead.
- Deprecated `craft\web\View::getIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::isActive()` should be used instead.
- Deprecated `craft\web\View::setIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::setActive()` should be used instead.
- Deprecated `craft\web\View::hook()`. `CraftCms\Cms\View\TemplateHooks::register()` should be used instead.
- Deprecated `craft\web\View::invokeHook()`. `CraftCms\Cms\View\TemplateHooks::invoke()` should be used instead.
