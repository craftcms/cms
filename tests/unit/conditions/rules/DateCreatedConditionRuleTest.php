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
use craft\enums\DateRangeType;
use craft\enums\PeriodType;
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

        if ($rule->rangeType !== DateRangeType::Range) {
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
                ['rangeType' => DateRangeType::Range, 'startDate' => $startDate, 'endDate' => $endDate],
                ['and', '>= ' . $startDate->format(DateTime::ATOM), '< ' . $endDate->format(DateTime::ATOM)],
            ],
            'today' => [
                ['rangeType' => DateRangeType::Today],
                [
                    fn() => DateTimeHelper::today(),
                    fn() => DateTimeHelper::tomorrow(),
                ],
            ],
            'thisMonth' => [
                ['rangeType' => DateRangeType::ThisMonth],
                [
                    fn() => DateTimeHelper::thisMonth(),
                    fn() => DateTimeHelper::nextMonth(),
                ],
            ],
            'thisYear' => [
                ['rangeType' => DateRangeType::ThisYear],
                [
                    fn() => DateTimeHelper::thisYear(),
                    fn() => DateTimeHelper::nextYear(),
                ],
            ],
            'past7Days' => [
                ['rangeType' => DateRangeType::Past7Days],
                [
                    fn() => DateTimeHelper::today()->modify('-7 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'past30Days' => [
                ['rangeType' => DateRangeType::Past30Days],
                [
                    fn() => DateTimeHelper::today()->modify('-30 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'past90Days' => [
                ['rangeType' => DateRangeType::Past90Days],
                [
                    fn() => DateTimeHelper::today()->modify('-90 days'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'pastYear' => [
                ['rangeType' => DateRangeType::PastYear],
                [
                    fn() => DateTimeHelper::today()->modify('-1 year'),
                    fn() => DateTimeHelper::now(),
                ],
            ],
            'periodTypeHoursAfter' => [
                ['rangeType' => DateRangeType::After, 'periodValue' => 10, 'periodType' => PeriodType::Hours],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesAfter' => [
                ['rangeType' => DateRangeType::After, 'periodValue' => 10, 'periodType' => PeriodType::Minutes],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysAfter' => [
                ['rangeType' => DateRangeType::After, 'periodValue' => 10, 'periodType' => PeriodType::Days],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 days')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeHoursBefore' => [
                ['rangeType' => DateRangeType::Before, 'periodValue' => 10, 'periodType' => PeriodType::Hours],
                [
                    static function() {
                        return '< ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesBefore' => [
                ['rangeType' => DateRangeType::Before, 'periodValue' => 10, 'periodType' => PeriodType::Minutes],
                [
                    static function() {
                        return '< ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysBefore' => [
                ['rangeType' => DateRangeType::Before, 'periodValue' => 10, 'periodType' => PeriodType::Days],
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
