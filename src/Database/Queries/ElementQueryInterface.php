<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use craft\base\ElementInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @phpstan-require-extends Builder
 */
interface ElementQueryInterface extends Builder
{
    /**
     * Execute the query and get the first result.
     */
    public function one(array|string $columns = ['*']): ?ElementInterface;

    /**
     * Execute the query as a "select" statement.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ElementInterface>|array<int, ElementInterface>
     */
    public function all(array|string $columns = ['*']): Collection|array;

    /**
     * Causes the query results to be returned in reverse order.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in reverse #}
     * {% set {elements-var} = {twig-method}
     *   .inReverse()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in reverse
     * ${elements-var} = {php-method}
     *     ->inReverse()
     *     ->all();
     * ```
     *
     * @param  bool  $value  The property value
     * @return static self reference
     */
    public function inReverse(bool $value = true): static;

    /**
     * Causes the query to return provisional drafts for the matching elements,
     * when they exist for the current user.
     *
     * @param  bool  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function withProvisionalDrafts(bool $value = true): static;

    /**
     * Narrows the query results to only drafts {elements}.
     *
     * ---
     *
     * ```twig
     * {# Fetch a draft {element} #}
     * {% set {elements-var} = {twig-method}
     *   .drafts()
     *   .id(123)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch a draft {element}
     * ${elements-var} = {element-class}::find()
     *     ->drafts()
     *     ->id(123)
     *     ->one();
     * ```
     *
     * @param  bool|null  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function drafts(?bool $value = true): static;

    /**
     * Narrows the query results based on the {elements}’ draft’s ID (from the `drafts` table).
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | for the draft with an ID of 1.
     *
     * ---
     *
     * ```twig
     * {# Fetch a draft #}
     * {% set {elements-var} = {twig-method}
     *   .draftId(10)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch a draft
     * ${elements-var} = {php-method}
     *     ->draftId(10)
     *     ->all();
     * ```
     *
     * @param  int|null  $value  The property value
     * @return static self reference
     */
    public function draftId(?int $value = null): static;

    /**
     * Narrows the query results to only drafts of a given {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | for the {element} with an ID of 1.
     * | `[1, 2]` | for the {elements} with an ID of 1 or 2.
     * | a [[{element-class}]] object | for the {element} represented by the object.
     * | an array of [[{element-class}]] objects | for the {elements} represented by the objects.
     * | `'*'` | for any {element}
     * | `false` | that aren’t associated with a published {element}
     *
     * ---
     *
     * ```twig
     * {# Fetch drafts of the {element} #}
     * {% set {elements-var} = {twig-method}
     *   .draftOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch drafts of the {element}
     * ${elements-var} = {php-method}
     *     ->draftOf(${myElement})
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function draftOf(mixed $value): static;

    /**
     * Narrows the query results to only drafts created by a given user.
     *
     * Possible values include:
     *
     * | Value | Fetches drafts…
     * | - | -
     * | `1` | created by the user with an ID of 1.
     * | a [[User]] object | created by the user represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch drafts by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .draftCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch drafts by the current user
     * ${elements-var} = {php-method}
     *     ->draftCreator(Craft::$app->user->identity)
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function draftCreator(mixed $value): static;

    /**
     * Narrows the query results to only provisional drafts.
     *
     * ---
     *
     * ```twig
     * {# Fetch provisional drafts created by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .provisionalDrafts()
     *   .draftCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch provisional drafts created by the current user
     * ${elements-var} = {php-method}
     *     ->provisionalDrafts()
     *     ->draftCreator(Craft::$app->user->identity)
     *     ->all();
     * ```
     *
     * @param  bool|null  $value  The property value
     * @return static self reference
     */
    public function provisionalDrafts(?bool $value = true): static;

    /**
     * Narrows the query results to only canonical elements, including elements
     * that reference another canonical element via `canonicalId` so long as they
     * aren’t a draft.
     *
     * Unpublished drafts can be included as well if `drafts(null)` and
     * `draftOf(false)` are also passed.
     *
     * @param  bool  $value  The property value
     * @return static self reference
     */
    public function canonicalsOnly(bool $value = true): static;

    /**
     * Narrows the query results to only unpublished drafts which have been saved after initial creation.
     *
     * ---
     *
     * ```twig
     * {# Fetch saved, unpublished draft {elements} #}
     * {% set {elements-var} = {twig-method}
     *   .draftOf(false)
     *   .savedDraftsOnly()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch saved, unpublished draft {elements}
     * ${elements-var} = {element-class}::find()
     *     ->draftOf(false)
     *     ->savedDraftsOnly()
     *     ->all();
     * ```
     *
     * @param  bool  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function savedDraftsOnly(bool $value = true): static;

    /**
     * Narrows the query results to only revision {elements}.
     *
     * ---
     *
     * ```twig
     * {# Fetch a revision {element} #}
     * {% set {elements-var} = {twig-method}
     *   .revisions()
     *   .id(123)
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch a revision {element}
     * ${elements-var} = {element-class}::find()
     *     ->revisions()
     *     ->id(123)
     *     ->one();
     * ```
     *
     * @param  bool|null  $value  The property value (defaults to true)
     * @return static self reference
     */
    public function revisions(?bool $value = true): static;

    /**
     * Narrows the query results based on the {elements}’ revision’s ID (from the `revisions` table).
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | for the revision with an ID of 1.
     *
     * ---
     *
     * ```twig
     * {# Fetch a revision #}
     * {% set {elements-var} = {twig-method}
     *   .revisionId(10)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch a revision
     * ${elements-var} = {php-method}
     *     ->revisionIf(10)
     *     ->all();
     * ```
     *
     * @param  int|null  $value  The property value
     * @return static self reference
     */
    public function revisionId(?int $value = null): static;

    /**
     * Narrows the query results to only revisions of a given {element}.
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | for the {element} with an ID of 1.
     * | a [[{element-class}]] object | for the {element} represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch revisions of the {element} #}
     * {% set {elements-var} = {twig-method}
     *   .revisionOf({myElement})
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch revisions of the {element}
     * ${elements-var} = {php-method}
     *     ->revisionOf(${myElement})
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function revisionOf(mixed $value): static;

    /**
     * Narrows the query results to only revisions created by a given user.
     *
     * Possible values include:
     *
     * | Value | Fetches revisions…
     * | - | -
     * | `1` | created by the user with an ID of 1.
     * | a [[User]] object | created by the user represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch revisions by the current user #}
     * {% set {elements-var} = {twig-method}
     *   .revisionCreator(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch revisions by the current user
     * ${elements-var} = {php-method}
     *     ->revisionCreator(Craft::$app->user->identity)
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     * @return static self reference
     */
    public function revisionCreator(mixed $value): static;
}
