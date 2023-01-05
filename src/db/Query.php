<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use ArrayAccess;
use ArrayIterator;
use craft\base\ClonefixTrait;
use craft\elements\ElementCollection;
use craft\events\DefineBehaviorsEvent;
use craft\helpers\ArrayHelper;
use Illuminate\Support\Collection;
use IteratorAggregate;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\db\Connection as YiiConnection;

/**
 * Class Query
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Query extends \yii\db\Query implements ArrayAccess, IteratorAggregate
{
    use ClonefixTrait;

    /**
     * @event \yii\base\Event The event that is triggered after the query's init cycle
     * @see init()
     */
    public const EVENT_INIT = 'init';

    /**
     * @event DefineBehaviorsEvent The event that is triggered when defining the class behaviors
     * @see behaviors()
     */
    public const EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * Required by the IteratorAggregate interface.
     *
     * @return ArrayIterator
     * @since 4.2.0
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param mixed $offset
     * @return bool
     * @since 4.2.0
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_numeric($offset)) {
            $offset = $this->offset;
            $limit = $this->limit;
            $this->offset = $offset;
            $this->limit = 1;
            $exists = $this->exists();
            $this->offset = $offset;
            $this->limit = $limit;
            return $exists;
        }

        return $this->__isset($offset);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param mixed $offset
     * @return mixed
     * @throws UnknownPropertyException
     * @since 4.2.0
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (is_numeric($offset)) {
            $element = $this->nth($offset);
            if ($element) {
                return $element;
            }
        }

        return $this->__get($offset);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws NotSupportedException
     * @throws UnknownPropertyException
     * @since 4.2.0
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_numeric($offset)) {
            throw new NotSupportedException('Queries do not support setting values using array syntax.');
        }

        $this->__set($offset, $value);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param mixed $offset
     * @return void
     * @throws NotSupportedException
     * @since 4.2.0
     */
    public function offsetUnset(mixed $offset): void
    {
        if (is_numeric($offset)) {
            throw new NotSupportedException('Queries do not support unsetting values using array syntax.');
        }

        $this->__unset($offset);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        // Fire a 'defineBehaviors' event
        $event = new DefineBehaviorsEvent();
        $this->trigger(self::EVENT_DEFINE_BEHAVIORS, $event);
        return $event->behaviors;
    }

    /**
     * Returns whether a given table has been joined in this query.
     *
     * @param string $table
     * @return bool
     */
    public function isJoined(string $table): bool
    {
        foreach ($this->join as $join) {
            if ($join[1] === $table || str_starts_with($join[1], $table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function where($condition, $params = []): static
    {
        if (!$condition) {
            $condition = null;
        }

        return parent::where($condition, $params);
    }

    /**
     * @inheritdoc
     */
    public function andWhere($condition, $params = []): static
    {
        if (!$condition) {
            return $this;
        }

        return parent::andWhere($condition, $params);
    }

    /**
     * @inheritdoc
     */
    public function orWhere($condition, $params = []): static
    {
        if (!$condition) {
            return $this;
        }

        return parent::orWhere($condition, $params);
    }

    // Execution functions
    // -------------------------------------------------------------------------

    /**
     * Executes the query and returns the first two columns in the results as key/value pairs.
     *
     * @param YiiConnection|null $db The database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     * @throws Exception if less than two columns were selected
     */
    public function pairs(?YiiConnection $db = null): array
    {
        try {
            $rows = $this->createCommand($db)->queryAll();
        } catch (QueryAbortedException) {
            return [];
        }

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);

            if (count($columns) < 2) {
                throw new Exception('Less than two columns were selected');
            }

            $rows = ArrayHelper::map($rows, $columns[0], $columns[1]);
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function all($db = null): array
    {
        try {
            return parent::all($db);
        } catch (QueryAbortedException) {
            return [];
        }
    }

    /**
     * Executes the query and returns all results as a collection.
     *
     * @param YiiConnection|null $db The database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return Collection A collection of the resulting elements.
     * @since 4.0.0
     */
    public function collect(?YiiConnection $db = null): Collection
    {
        return ElementCollection::make($this->all($db));
    }

    /**
     * @inheritdoc
     */
    public function one($db = null): mixed
    {
        $limit = $this->limit;
        $this->limit = 1;
        try {
            $result = parent::one($db);
            // Be more like Yii 2.1
            if ($result === false) {
                $result = null;
            }
        } catch (QueryAbortedException) {
            $result = null;
        }
        $this->limit = $limit;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function scalar($db = null): bool|int|string|null
    {
        $limit = $this->limit;
        $this->limit = 1;
        try {
            $result = parent::scalar($db);
        } catch (QueryAbortedException) {
            $result = false;
        }
        $this->limit = $limit;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function column($db = null): array
    {
        try {
            return parent::column($db);
        } catch (QueryAbortedException) {
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function exists($db = null): bool
    {
        try {
            return parent::exists($db);
        } catch (QueryAbortedException) {
            return false;
        }
    }

    /**
     * Executes the query and returns a single row of result at a given offset.
     *
     * @param int $n The offset of the row to return. If [[offset]] is set, $offset will be added to it.
     * @param YiiConnection|null $db The database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return mixed The row (in terms of an array) of the query result. Null is returned if the query
     * results in nothing.
     */
    public function nth(int $n, ?YiiConnection $db = null): mixed
    {
        $offset = $this->offset;
        $this->offset = ($offset ?: 0) + $n;
        $result = $this->one($db);
        $this->offset = $offset;

        return $result;
    }

    /**
     * Shortcut for `createCommand()->getRawSql()`.
     *
     * @param YiiConnection|null $db the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return string
     * @see createCommand()
     * @see \yii\db\Command::getRawSql()
     */
    public function getRawSql(?YiiConnection $db = null): string
    {
        return $this->createCommand($db)->getRawSql();
    }

    /**
     * @inheritdoc
     */
    protected function queryScalar($selectExpression, $db): bool|string|null
    {
        try {
            return parent::queryScalar($selectExpression, $db);
        } catch (QueryAbortedException) {
            return false;
        }
    }
}
