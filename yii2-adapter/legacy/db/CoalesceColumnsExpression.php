<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\BaseObject;

/**
 * CoalesceColumnsExpression represents a `COALESCE()` SQL statement for a list of columns.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.1.0
 */
class CoalesceColumnsExpression extends BaseObject implements ExpressionInterface
{
    /**
     * @var string[] The columns that should be coalesced.
     */
    public array $columns;

    /**
     * Constructor
     *
     * @param string[] $columns The columns that should be coalesced.
     * @param array $config
     */
    public function __construct(array $columns = [], array $config = [])
    {
        $this->columns = $columns;
        parent::__construct($config);
    }

    public function getSql(array &$params): string
    {
        $columns = array_map(
            fn(string $column) => str_contains($column, '(') ? $column : "[[$column]]",
            $this->columns,
        );
        if (count($columns) === 1) {
            return reset($columns);
        }
        return sprintf('COALESCE(%s)', implode(', ', $columns));
    }
}
