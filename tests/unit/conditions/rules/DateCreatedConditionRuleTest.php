<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace crafttests\unit\conditions\rules;

use Codeception\Test\Unit;
use Craft;
use craft\elements\conditions\DateCreatedConditionRule;
use craft\elements\Entry;
use craft\helpers\DateRange;
use craft\helpers\DateTimeHelper;
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Unit tests for DateCreatedConditionRule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class DateCreatedConditionRuleTest extends TestCase
{
    /**
     * @param array $config
     * @param array|null $expected
     * @return void
     * @dataProvider setAttributesDataProvider
     */
    public function testSetAttributes(array $config, ?array $expected = null): void
    {
        $expected = $expected ?? $config;
        $ruleConfig = array_merge([
            'class' => DateCreatedConditionRule::class,
        ], $config);
        $rule = Craft::$app->getConditions()->createConditionRule($ruleConfig);

        foreach ($expected as $attribute => $value) {
            self::assertEquals($value, $rule->$attribute);
        }
    }

    public function setAttributesDataProvider(): array
    {
        return [
            [
                ['startDate' => DateTimeHelper::now()->sub(new DateInterval('P1D')), 'endDate' => DateTimeHelper::now()->add(new DateInterval('P1D'))],
                ['startDate' => DateTimeHelper::now()->sub(new DateInterval('P1D'))->format(DateTime::ATOM), 'endDate' => DateTimeHelper::now()->add(new DateInterval('P1D'))->format(DateTime::ATOM)],
            ],
            [
                ['rangeType' => 'before', 'periodValue' => 99, 'periodType' => 'hours'],
            ],
        ];
    }

    /**
     * @param array $config
     * @param array $expected
     * @return void
     * @dataProvider queryParamValueDataProvider
     */
    public function testQueryParamValue(array $config, array $expected): void
    {
        /** @var DateCreatedConditionRule $rule */
        $rule = Craft::$app->getConditions()->createConditionRule(array_merge([
            'class' => DateCreatedConditionRule::class,
        ], $config));

        $entryQuery = Entry::find();
        $rule->modifyQuery($entryQuery);

        if ($rule->rangeType !== DateRange::TYPE_RANGE) {
            if (count($expected) === 1) {
                $expected = $expected[0]();
            } else {
                $startDate = $expected[0](); // StartDate
                $endDate = $expected[1](); // EndDate
                $expected = [
                    'and',
                    $startDate instanceof DateTime ? '>= ' . $startDate->format(DateTime::ATOM) : $startDate,
                    $endDate instanceof DateTime ? '< ' . $endDate->format(DateTime::ATOM) : $endDate,
                ];
            }
        }

        self::assertEquals($expected, $entryQuery->dateCreated);
    }

    /**
     * @return array
     */
    public function queryParamValueDataProvider(): array
    {
        $startDate = DateTimeHelper::now()->sub(new DateInterval('P1D'));
        $endDate = DateTimeHelper::now()->add(new DateInterval('P1D'));

        return [
            'start-and-end' => [
                ['rangeType' => DateRange::TYPE_RANGE, 'startDate' => $startDate, 'endDate' => $endDate],
                ['and', '>= ' . $startDate->format(DateTime::ATOM), '< ' . $endDate->format(DateTime::ATOM)],
            ],
            'today' => [
                ['rangeType' => DateRange::TYPE_TODAY],
                [
                    fn() => DateTimeHelper::today(),
                    fn() => DateTimeHelper::tomorrow(),
                ],
            ],
            'thisMonth' => [
                ['rangeType' => DateRange::TYPE_THIS_MONTH],
                [
                    fn() => DateTimeHelper::thisMonth(),
                    fn() => DateTimeHelper::nextMonth(),
                ],
            ],
            'thisYear' => [
                ['rangeType' => DateRange::TYPE_THIS_YEAR],
                [
                    fn() => DateTimeHelper::thisYear(),
                    fn() => DateTimeHelper::nextYear(),
                ],
            ],
            'past7Days' => [
                ['rangeType' => DateRange::TYPE_PAST_7_DAYS],
                [
                    fn() => DateTimeHelper::today()->modify('-7 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'past30Days' => [
                ['rangeType' => DateRange::TYPE_PAST_30_DAYS],
                [
                    fn() => DateTimeHelper::today()->modify('-30 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'past90Days' => [
                ['rangeType' => DateRange::TYPE_PAST_90_DAYS],
                [
                    fn() => DateTimeHelper::today()->modify('-90 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'pastYear' => [
                ['rangeType' => DateRange::TYPE_PAST_YEAR],
                [
                    fn() => DateTimeHelper::today()->modify('-1 year'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'periodTypeHoursAfter' => [
                ['rangeType' => DateRange::TYPE_AFTER, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_HOURS_AGO],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesAfter' => [
                ['rangeType' => DateRange::TYPE_AFTER, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_MINUTES_AGO],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysAfter' => [
                ['rangeType' => DateRange::TYPE_AFTER, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_DAYS_AGO],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 days')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeHoursBefore' => [
                ['rangeType' => DateRange::TYPE_BEFORE, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_HOURS_AGO],
                [
                    static function() {
                        return '< ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesBefore' => [
                ['rangeType' => DateRange::TYPE_BEFORE, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_MINUTES_AGO],
                [
                    static function() {
                        return '< ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysBefore' => [
                ['rangeType' => DateRange::TYPE_BEFORE, 'periodValue' => 10, 'periodType' => DateRange::PERIOD_DAYS_AGO],
                [
                    static function() {
                        return '< ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 days')->format(DATE_ATOM);
                    },
                ],
            ],
        ];
    }

    protected function _before(): void
    {
        DateTimeHelper::pause();
    }

    protected function _after(): void
    {
        DateTimeHelper::resume();
    }
}
