<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\User;

use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\Support\Query;

/**
 * @internal
 */
trait QueriesUserProperties
{
    /**
     * @var mixed The username that the resulting users must have.
     *
     * @used-by username()
     */
    public mixed $username = null;

    /**
     * @var mixed The email address that the resulting users must have.
     *
     * @used-by email()
     */
    public mixed $email = null;

    /**
     * @var mixed The full name that the resulting users must have.
     *
     * @used-by fullName()
     */
    public mixed $fullName = null;

    /**
     * @var mixed The first name that the resulting users must have.
     *
     * @used-by firstName()
     */
    public mixed $firstName = null;

    /**
     * @var mixed The last name that the resulting users must have.
     *
     * @used-by lastName()
     */
    public mixed $lastName = null;

    /**
     * @var mixed The date that the resulting users must have last logged in.
     *
     * @used-by lastLoginDate()
     */
    public mixed $lastLoginDate = null;

    /**
     * @var bool|null Whether to only return users that have (or don’t have) user photos.
     *
     * @used-by hasPhoto()
     */
    public ?bool $hasPhoto = null;

    protected function initQueriesUserProperties(): void
    {
        $this->beforeQuery(function (UserQuery $userQuery) {
            if ($userQuery->lastLoginDate) {
                $userQuery->subQuery->whereDateParam('users.lastLoginDate', $this->lastLoginDate);
            }

            if (is_bool($userQuery->hasPhoto)) {
                $userQuery->when(
                    $userQuery->hasPhoto,
                    fn (UserQuery $q) => $q->subQuery->whereNotNull('users.photoId'),
                    fn (UserQuery $q) => $q->subQuery->whereNull('users.photoId'),
                );
            }

            foreach (['username', 'email', 'fullName', 'firstName', 'lastName'] as $property) {
                if (! $userQuery->$property) {
                    continue;
                }

                if (is_string($userQuery->$property)) {
                    $userQuery->$property = Query::escapeCommas($userQuery->$property);
                }

                $userQuery->subQuery->whereParam(
                    column: "users.$property",
                    param: $userQuery->$property,
                    caseInsensitive: true,
                );
            }
        });
    }

    /**
     * Narrows the query results based on the users’ usernames.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'foo'` | with a username of `foo`.
     * | `'not foo'` | not with a username of `foo`.
     *
     * ---
     *
     * ```twig
     * {# Get the requested username #}
     * {% set requestedUsername = craft.app.request.getSegment(2) %}
     *
     * {# Fetch that user #}
     * {% set {element-var} = {twig-method}
     *   .username(requestedUsername|literal)
     *   .one() %}
     * ```
     *
     * ```php
     * // Get the requested username
     * $requestedUsername = \Craft::$app->request->getSegment(2);
     *
     * // Fetch that user
     * ${element-var} = {php-method}
     *     ->username(\craft\helpers\Db::escapeParam($requestedUsername))
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $username
     */
    public function username(mixed $value): self
    {
        $this->username = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the users’ full names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Jane Doe'` | with a full name of `Jane Doe`.
     * | `'not Jane Doe'` | not with a full name of `Jane Doe`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Jane Doe's #}
     * {% set {elements-var} = {twig-method}
     *   .fullName('Jane Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Jane Doe's
     * ${elements-var} = {php-method}
     *     ->fullName('JaneDoe')
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $fullName
     */
    public function fullName(mixed $value): self
    {
        $this->fullName = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the users’ first names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Jane'` | with a first name of `Jane`.
     * | `'not Jane'` | not with a first name of `Jane`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Jane's #}
     * {% set {elements-var} = {twig-method}
     *   .firstName('Jane')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Jane's
     * ${elements-var} = {php-method}
     *     ->firstName('Jane')
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $firstName
     */
    public function firstName(mixed $value): self
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the users’ last names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Doe'` | with a last name of `Doe`.
     * | `'not Doe'` | not with a last name of `Doe`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Doe's #}
     * {% set {elements-var} = {twig-method}
     *   .lastName('Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Doe's
     * ${elements-var} = {php-method}
     *     ->lastName('Doe')
     *     ->one();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $lastName
     */
    public function lastName(mixed $value): self
    {
        $this->lastName = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the users’ email addresses.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'me@domain.tld'` | with an email of `me@domain.tld`.
     * | `'not me@domain.tld'` | not with an email of `me@domain.tld`.
     *
     * | `'*@domain.tld'` | with an email that ends with `@domain.tld`.
     *
     * ---
     *
     * ```twig
     * {# Fetch users with a .co.uk domain on their email address #}
     * {% set {elements-var} = {twig-method}
     *   .email('*.co.uk')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users with a .co.uk domain on their email address
     * ${elements-var} = {php-method}
     *     ->email('*.co.uk')
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $email
     */
    public function email(mixed $value): self
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the users’ last login dates.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'>= 2018-04-01'` | that last logged in on or after 2018-04-01.
     * | `'< 2018-05-01'` | that last logged in before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that last logged in between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that last logged in at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch users that logged in recently #}
     * {% set aWeekAgo = date('7 days ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .lastLoginDate(">= #{aWeekAgo}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users that logged in recently
     * $aWeekAgo = (new \DateTime('7 days ago'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->lastLoginDate(">= {$aWeekAgo}")
     *     ->all();
     * ```
     *
     * @param  mixed  $value  The property value
     *
     * @uses $lastLoginDate
     */
    public function lastLoginDate(mixed $value): self
    {
        $this->lastLoginDate = $value;

        return $this;
    }

    /**
     * Narrows the query results to only users that have (or don’t have) a user photo.
     *
     * ---
     *
     * ```twig
     * {# Fetch users with photos #}
     * {% set {elements-var} = {twig-method}
     *   .hasPhoto()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users without photos
     * ${elements-var} = {element-class}::find()
     *     ->hasPhoto()
     *     ->all();
     * ```
     *
     * @uses $hasPhoto
     */
    public function hasPhoto(bool $value = true): self
    {
        $this->hasPhoto = $value;

        return $this;
    }
}
