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
     * Constructor
     *
     * @param string[] $columns The columns that should be coalesced.
     * @param array $config
     */
    public function __construct(public array $columns = [], array $config = [])
    {
        parent::__construct($config);
    }

    public function getSql(array &$params): string
    {
        $columns = array_map(
            fn(string $column) => str_contains($column, '(') ? $column : "[[$column]]",
            $this->columns,
        );
        return sprintf('COALESCE(%s)', implode(', ', $columns));
    }
}
