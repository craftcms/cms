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
use craft\enums\DateRange;
use craft\enums\PeriodType;
use craft\helpers\DateRangeHelper;
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
                ['dateRange' => 'before', 'periodTypeValue' => 99, 'periodType' => 'hours'],
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

        if ($rule->dateRange !== DateRangeHelper::RANGE) {
            if (count($expected) === 1) {
                $expected = $expected[0]();
            } else {
                $startDate = $expected[0](); // StartDate
                $endDate = $expected[1](); // EndDate
                $expected = [
                    'and',
                    $startDate instanceof DateTime ? '>= ' . $startDate->format(DateTime::ATOM) : $startDate,
                    $endDate instanceof DateTime ? '<= ' . $endDate->format(DateTime::ATOM) : $endDate,
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
                ['dateRange' => DateRangeHelper::RANGE, 'startDate' => $startDate, 'endDate' => $endDate],
                ['and', '>= ' . $startDate->format(DateTime::ATOM), '< ' . $endDate->format(DateTime::ATOM)],
            ],
            'today' => [
                ['dateRange' => DateRange::Today],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->setTime(0, 0);
                    }, // Start Date
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->setTime(23, 59, 59);
                    }, // End Date
                ],
            ],
            'thisMonth' => [
                ['dateRange' => DateRange::ThisMonth],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->modify('first day of this month')
                            ->setTime(0, 0);
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->modify('last day of this month')
                            ->setTime(23, 59, 59);
                    },
                ],
            ],
            'thisYear' => [
                ['dateRange' => DateRange::ThisYear],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->modify('1st January ' . date('Y'))
                            ->setTime(0, 0);
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->modify('last day of December ' . date('Y'))
                            ->setTime(23, 59, 59);
                    },
                ],
            ],
            'past7Days' => [
                ['dateRange' => DateRange::Past7Days],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->sub(new DateInterval('P7D'));
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                    },
                ],
            ],
            'past30Days' => [
                ['dateRange' => DateRange::Past30Days],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->sub(new DateInterval('P30D'));
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                    },
                ],
            ],
            'past90Days' => [
                ['dateRange' => DateRange::Past90Days],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->sub(new DateInterval('P90D'));
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                    },
                ],
            ],
            'pastYear' => [
                ['dateRange' => DateRange::PastYear],
                [
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')))
                            ->sub(new DateInterval('P1Y'));
                    },
                    static function() {
                        return (new DateTime('now', new DateTimeZone('America/Los_Angeles')));
                    },
                ],
            ],
            'periodTypeHoursAfter' => [
                ['dateRange' => DateRangeHelper::AFTER, 'periodTypeValue' => 10, 'periodType' => PeriodType::Hours],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesAfter' => [
                ['dateRange' => DateRangeHelper::AFTER, 'periodTypeValue' => 10, 'periodType' => PeriodType::Minutes],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysAfter' => [
                ['dateRange' => DateRangeHelper::AFTER, 'periodTypeValue' => 10, 'periodType' => PeriodType::Days],
                [
                    static function() {
                        return '>= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 days')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeHoursBefore' => [
                ['dateRange' => DateRangeHelper::BEFORE, 'periodTypeValue' => 10, 'periodType' => PeriodType::Hours],
                [
                    static function() {
                        return '<= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 hours')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeMinutesBefore' => [
                ['dateRange' => DateRangeHelper::BEFORE, 'periodTypeValue' => 10, 'periodType' => PeriodType::Minutes],
                [
                    static function() {
                        return '<= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 minutes')->format(DATE_ATOM);
                    },
                ],
            ],
            'periodTypeDaysBefore' => [
                ['dateRange' => DateRangeHelper::BEFORE, 'periodTypeValue' => 10, 'periodType' => PeriodType::Days],
                [
                    static function() {
                        return '<= ' . (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->modify('-10 days')->format(DATE_ATOM);
                    },
                ],
            ],
        ];
    }
}
