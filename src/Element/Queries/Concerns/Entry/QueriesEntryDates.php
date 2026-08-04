<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Entry;

use CraftCms\Cms\Element\Queries\EntryQuery;

/**
 * @internal
 */
trait QueriesEntryDates
{
    /**
     * @var mixed The Post Date that the resulting entries must have.
     *            ---
     *            ```php
     *            // fetch entries written in 2018
     *            $entries = \CraftCms\Cms\Entry\Elements\Entry::find()
     *            ->postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries written in 2018 #}
     *            {% set entries = entries()
     *            .postDate(['and', '>= 2018-01-01', '< 2019-01-01'])
     *            .all() %}
     *            ```
     *
     * @used-by postDate()
     */
    public mixed $postDate = null;

    /**
     * @var mixed The maximum Post Date that resulting entries can have.
     *            ---
     *            ```php
     *            // fetch entries written before 4/4/2018
     *            $entries = \CraftCms\Cms\Entry\Elements\Entry::find()
     *            ->before('2018-04-04')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries written before 4/4/2018 #}
     *            {% set entries = entries()
     *            .before('2018-04-04')
     *            .all() %}
     *            ```
     *
     * @used-by before()
     */
    public mixed $before = null;

    /**
     * @var mixed The minimum Post Date that resulting entries can have.
     *            ---
     *            ```php
     *            // fetch entries written in the last 7 days
     *            $entries = \CraftCms\Cms\Entry\Elements\Entry::find()
     *            ->after((new \DateTime())->modify('-7 days'))
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch entries written in the last 7 days #}
     *            {% set entries = entries()
     *            .after(now|date_modify('-7 days'))
     *            .all() %}
     *            ```
     *
     * @used-by after()
     */
    public mixed $after = null;

    /**
     * @var mixed The Expiry Date that the resulting entries must have.
     *
     * @used-by expiryDate()
     */
    public mixed $expiryDate = null;

    protected function initQueriesEntryDates(): void
    {
        $this->beforeQuery(function (EntryQuery $query) {
            if ($query->postDate) {
                $query->whereDateParam('entries.postDate', $query->postDate);
            } else {
                if ($query->before) {
                    $query->whereDateParam('entries.postDate', $query->before, '<');
                }
                if ($query->after) {
                    $query->whereDateParam('entries.postDate', $query->after, '>=');
                }
            }

            if ($query->expiryDate) {
                $query->whereDateParam('entries.expiryDate', $query->expiryDate);
            }
        });
    }

    /**
     * Narrows the query results based on the entries’ post dates.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'>= 2018-04-01'` | that were posted on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were posted before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were posted between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted last month #}
     * {% set start = date('first day of last month')|atom %}
     * {% set end = date('first day of this month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .postDate(['and', ">= #{start}", "< #{end}"])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted last month
     * $start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
     * $end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->postDate(['and', ">= {$start}", "< {$end}"])
     *     ->all();
     * ```
     *
     * @uses $postDate
     */
    public function postDate(mixed $value): static
    {
        $this->postDate = $value;

        return $this;
    }

    /**
     * Narrows the query results to only entries that were posted before a certain date.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'2018-04-01'` | that were posted before 2018-04-01.
     * | a [[\DateTime|DateTime]] object | that were posted before the date represented by the object.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted before midnight of specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted before this month #}
     * {% set firstDayOfMonth = date('first day of this month') %}
     *
     * {% set {elements-var} = {twig-method}
     *   .before(firstDayOfMonth)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted before this month
     * $firstDayOfMonth = new \DateTime('first day of this month');
     *
     * ${elements-var} = {php-method}
     *     ->before($firstDayOfMonth)
     *     ->all();
     * ```
     *
     * @uses $before
     */
    public function before(mixed $value): static
    {
        $this->before = $value;

        return $this;
    }

    /**
     * Narrows the query results to only entries that were posted on or after a certain date.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `'2018-04-01'` | that were posted on or after 2018-04-01.
     * | a [[\DateTime|DateTime]] object | that were posted on or after the date represented by the object.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were posted on or after midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries posted this month #}
     * {% set firstDayOfMonth = date('first day of this month') %}
     *
     * {% set {elements-var} = {twig-method}
     *   .after(firstDayOfMonth)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries posted this month
     * $firstDayOfMonth = new \DateTime('first day of this month');
     *
     * ${elements-var} = {php-method}
     *     ->after($firstDayOfMonth)
     *     ->all();
     * ```
     *
     * @uses $after
     */
    public function after(mixed $value): static
    {
        $this->after = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the entries’ expiry dates.
     *
     * Possible values include:
     *
     * | Value | Fetches entries…
     * | - | -
     * | `':empty:'` | that don’t have an expiry date.
     * | `':notempty:'` | that have an expiry date.
     * | `'>= 2020-04-01'` | that will expire on or after 2020-04-01.
     * | `'< 2020-05-01'` | that will expire before 2020-05-01
     * | `['and', '>= 2020-04-04', '< 2020-05-01']` | that will expire between 2020-04-01 and 2020-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that expire at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch entries expiring this month #}
     * {% set nextMonth = date('first day of next month')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .expiryDate("< #{nextMonth}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch entries expiring this month
     * $nextMonth = (new \DateTime('first day of next month'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->expiryDate("< {$nextMonth}")
     *     ->all();
     * ```
     *
     * @uses $expiryDate
     */
    public function expiryDate(mixed $value): static
    {
        $this->expiryDate = $value;

        return $this;
    }
}
