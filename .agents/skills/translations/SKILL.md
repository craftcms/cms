---
name: translations
description: Translate Craft CMS control panel strings by editing the translation files in the same pull request. Use when adding or changing translatable `app` strings, when asked to translate new strings for a branch (such as 5.13 or 6.x), to fix or standardize a translated term across languages, or to review translation quality.
---

# Translations

Craft's control panel strings live in `resources/translations/en/app.php`. Each language has its own `resources/translations/<lang>/app.php` with the same keys. Translate strings by editing those files in the pull request that adds or changes the strings, so they ship translated.

## How translations reach other branches

[craftcms/translation-sync](https://github.com/craftcms/translation-sync) keeps the translation files in sync across the branches listed in `translation-sync.yml` on the `translations` branch. That branch holds a catalog with one file per language. The sync runs daily, after each push to `translations`, and when someone runs the Translation sync workflow by hand. When it runs, it:

- copies translations edited on a branch into the catalog,
- opens or updates a `t9n/<branch>` pull request for each branch whose files differ from the catalog.

A key has one translation per language on every branch, and the most recent edit wins. So translate a string once, on the branch your pull request targets, and let the sync carry it to the other branches. Don't copy translations to other branches by hand.

## Languages

Translate into every language folder in `resources/translations` except `en`. For `en-GB`, copy the English text and change American spellings to British ones (licence, colour, authorise).

Leave out a string you can't translate rather than adding it with English text. Every entry counts as translated, and Craft falls back to the English text for a missing key.

## Finding the strings to translate

Run the check script from the repository root. With `--missing`, it lists each language's untranslated keys:

```sh
php .agents/skills/translations/scripts/check.php resources/translations --missing
```

Skip raw translation keys that aren't UI text, such as `updater/Index` or `account_activation_body`.

Before translating, read how a string is used: `git grep -n "<source text>" -- src resources`. Many strings are fragments, column labels or screen-reader announcements, and the right translation depends on context.

## Writing translations

- **Reuse the existing terms.** Before inventing a term, look up how the language's `app.php` already translates the same English word (Entry, Asset, Handle, Slug, Section, Volume, Draft, Control Panel, and so on) and match it, along with register (formal or informal) and quote style.
- **Fix misleading existing terms** when you spot them, in every string that uses them, rather than spreading them further. Examples already fixed: da "Snegl" (slug), da "Kodetagget"/nb "Kodeord" (handle), nb "Lenke" (slug), fr "Identificateur" for slug, zh 链接 for slug, sv "Hantera" (handle), he כניסה (entry).
- **ICU messages:** keep every placeholder exactly as written. Plural/select translations must keep the source's selectors (`=1`, `other`, and so on). You may add the language's own categories (`one`, `few`, `many`), but don't remove the source ones.
- **Placeholders whose gender or case is unknown** (`{type}`, `{item}`, actor names): a neutral workaround such as "this element ({type})" or "zatwierdził(a)" is acceptable.
- **System/product names** in `{system}`, `{client}` and so on are plain names. Don't hyphenate them onto nouns or inflect them.
- **File format:** add each key in the same position as in `en/app.php`, and escape `'` as `\'`.

### Settled terminology decisions

- **Content "Release"** (6.x releases feature) means a release as in "ship". Use one term per language, distinct from the queue-job "release" (let go of a job) and from "publish" and "Edition". Current terms: de/nl/sv Release, fr lancement, es lanzamiento, pt lançamento, it rilascio, da udgivelse, nb utgivelse, pl wydanie, cs vydání, sk vydanie, ru Релиз, uk Реліз, el Κυκλοφορία, is Útgáfa, hu Kiadás, tr Sürüm, ja リリース, ko 릴리스, zh 发行版, fa ریلیز, th รีลีส, he גרסה, ar الإصدار.
- **Keyboard key names** (Alt, Control, Command, Option, Shift, Return, Enter, Escape, Tab, …) stay in English, as printed on the physical key.
- **"The Quick Brown Fox"** is placeholder text (a sample entry title in card previews), like lorem ipsum. A native pangram or a literal translation is fine; a person's name isn't.
- **"Bridged index" / "Bridged screen"** are dev-mode badges for legacy Craft 5-era screens rendered inside the 6.x Inertia shell (`src/Http/Responses/BridgedScreen.php`). Translate them as "legacy index/screen", not with a bridge metaphor.
- **OAuth/API strings:** "Scopes" are OAuth scopes, "Personal" means a personal access token, "App" is a scope category, and "Extend" is the verb (extensibility). "It…" on the consent screen refers to the client app.
## Translating many languages

Split the work across parallel workers by language family (de/de-CH, fr/fr-CA, es/pt/it, da/nb/nn/sv, pl/cs/sk, ru/uk, ar/he/fa, ja/zh, ko/th, …). Give each worker the keys, the terminology above, and the files it owns, and tell it to edit only its own languages' files.

## Verifying

Run the check script without `--missing`. It reports keys that aren't in `en/app.php`, messages that aren't valid ICU, and placeholders a translation dropped, and exits with 1 when it finds any. Run `php -l` on each file you edited.
