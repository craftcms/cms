---
name: translations
description: Translate and approve Craft CMS control panel strings in Crowdin. Use when asked to translate new strings for a release branch (such as 5.12 or 6.x), sync or pull translations from Crowdin, fix or standardize a translated term across languages, recover lost translations, or review translation quality in the "Craft CMS" Crowdin project.
---

# Translations

Craft's control panel strings live in `src/translations/en/app.php` on 5.x branches and `resources/translations/en/app.php` on 6.x. Translations are managed in the **Craft CMS** Crowdin project, then exported back to Git.

## How the pieces fit

- **Crowdin project:** `craft-cms`, ID `366365`. `crowdin.yml` maps `/en/app.php` to `/<lang>/app.php` and exports only approved translations.
- **Branches:** each Git branch that gets translated has a matching Crowdin branch with its own copy of `app.php`. Long-lived branches have stable IDs: 4.x is branch 1954 (file 1960), 5.x is branch 1948 (file 1952), and 6.x is branch 2078 (file 2082). Short-lived release branches such as `5.12` get their own branch and file, which are **deleted** after the release branch is merged back into `5.x`. Look up current IDs with the branch and file list endpoints; don't hardcode the release-branch ones.
- **Sync:** the GitHub integration uploads source strings from Git and exports translations to `t9n/<branch>` Git branches (e.g. `t9n/5.x`, `t9n/5.12`). The Crowdin API/MCP can't trigger that sync; ask the user to run it from the Crowdin integration page if it's needed.
- **Duplicates:** a string that already exists on another branch is a *duplicate* (`isDuplicate: true`, `masterStringId`). Its translation is shared with the master string, which is often on a different branch (frequently 6.x). Translate the **master** string, not the duplicate; the duplicate picks up the master's translation.

## Languages

Translate into every project target language **except** `cy` (Welsh), `en-GB`, `hr` (Croatian), `id` (Indonesian) and `ky` (Kyrgyz). Crowdin language IDs differ from the folder names in some cases: `es-ES` → `es`, `pt-PT` → `pt`, `sv-SE` → `sv`, `zh-CN` → `zh`.

## Finding the strings to translate

1. List the strings in the target branch's file (`limit: 500`, paginate, include `isDuplicate` and `masterStringId`).
2. For "new in X.Y" work, diff the keys of the branch's `en/app.php` against the previous release, or compare against strings that already have translations. A string with no translation in any target language is almost always new.
3. Replace each duplicate with its master string ID, and note which file the master lives in.
4. Skip raw translation keys that aren't UI text, such as `updater/Index` or `validation.required`.

Before translating, read how a string is used: `git grep -n "<source text>" origin/<branch> -- src resources` (fetch the branch first). Many strings are fragments, column labels or screen-reader announcements, and the right translation depends on context.

## Writing translations

- **Reuse the project's existing terms.** Before inventing a term, list the language's existing approved translations for the same English word (Entry, Asset, Handle, Slug, Section, Volume, Draft, Control Panel, and so on) and match them, along with register (formal or informal) and quote style.
- **Fix misleading existing terms** when you spot them, in every string that uses them, rather than spreading them further. Examples already fixed: da "Snegl" (slug), da "Kodetagget"/nb "Kodeord" (handle), nb "Lenke" (slug), fr "Identificateur" for slug, zh 链接 for slug, sv "Hantera" (handle), he כניסה (entry).
- **ICU messages:** keep every placeholder exactly as written. Plural/select translations must keep the source's selectors (`=1`, `other`, and so on). You may add the language's own categories (`one`, `few`, `many`), but don't remove the source ones. Crowdin rejects messages that don't parse.
- **Placeholders whose gender or case is unknown** (`{type}`, `{item}`, actor names): a neutral workaround such as "this element ({type})" or "zatwierdził(a)" is acceptable.
- **System/product names** in `{system}`, `{client}` and so on are plain names. Don't hyphenate them onto nouns or inflect them.

### Settled terminology decisions

- **Content "Release"** (6.x releases feature) means a release as in "ship". Use one term per language, distinct from the queue-job "release" (let go of a job) and from "publish" and "Edition". Current terms: de/nl/sv Release, fr lancement, es lanzamiento, pt lançamento, it rilascio, da udgivelse, nb utgivelse, pl wydanie, cs vydání, sk vydanie, ru Релиз, uk Реліз, el Κυκλοφορία, is Útgáfa, hu Kiadás, tr Sürüm, ja リリース, ko 릴리스, zh 发行版, fa ریلیز, th รีลีส, he גרסה, ar الإصدار.
- **Keyboard key names** (Alt, Control, Command, Option, Shift, Return, Enter, Escape, Tab, …) stay in English, as printed on the physical key.
- **"The Quick Brown Fox"** is placeholder text (a sample entry title in card previews), like lorem ipsum. A native pangram or a literal translation is fine; a person's name isn't.
- **"Bridged index" / "Bridged screen"** are dev-mode badges for legacy Craft 5-era screens rendered inside the 6.x Inertia shell (`src/Http/Responses/BridgedScreen.php`). Translate them as "legacy index/screen", not with a bridge metaphor.
- **OAuth/API strings:** "Scopes" are OAuth scopes, "Personal" means a personal access token, "App" is a scope category, and "Extend" is the verb (extensibility). "It…" on the consent screen refers to the client app.

## Writing to Crowdin efficiently

- Add translations in bulk with the translation batch operation (`{op: "add", path: "/-", value: {stringId, languageId, text}}`), then approve the returned translation IDs with the approval batch operation (`{op: "add", path: "/-", value: {translationId}}`). One batch of ~150 operations works fine.
- To change an existing approved translation, use the translation batch `replace` op on `/<translationId>` with `{text}`. It edits in place and keeps the approval. Deleting and re-adding also works, but then you have to approve again.
- **Make every write idempotent.** Before each batch, re-list existing translations for those string IDs and skip any that already have one, so a retry doesn't create duplicates.
- **After a 502 or timeout**, don't resend blindly: list what was saved, then add only what's missing.
- When code runs through an MCP `code_exec`-style tool, each run is limited to about 50 tool calls, so batch writes and paginate listings with `limit: 500`.
- For many languages, split the work across parallel workers by language family (de/de-CH, fr/fr-CA, es/pt/it, da/nb/sv, pl/cs/sk, ru/uk, ar/he/fa, ja/zh, ko/th, …). Give each worker the string IDs, the excluded languages, and the terminology above, and tell it to write only to its own languages.

## Verifying

Check progress per file with the file progress endpoint: for each target language, `translated` and `approved` should equal `total`, apart from known exceptions such as raw keys. To find specific gaps, list the file's non-duplicate strings and compare them against `approvedOnly` translations per language.

## Recovering lost translations

When a release branch is merged and its Crowdin branch is deleted, translations that only existed on that branch's strings are lost. The new strings reappear untranslated on the parent branch. The last export is still on the `t9n/<branch>` Git branch: read `<lang>/app.php` from the branch's translations folder (e.g. `git show origin/t9n/5.12:src/translations/de/app.php`, or `resources/translations` on 6.x), map each key to the parent branch's string ID, then batch-add and approve. To avoid this, finish translating a release branch before it's merged, and check the parent branch right after the merge.
