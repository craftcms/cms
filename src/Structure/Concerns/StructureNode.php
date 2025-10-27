<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Concerns;

use CraftCms\Cms\Structure\Enums\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait StructureNode
{
    public string|false $treeAttribute = 'root';

    public string $leftAttribute = 'lft';

    public string $rightAttribute = 'rgt';

    public string $depthAttribute = 'level';

    public protected(set) ?Operation $nestedSetOperation = null;

    private ?self $node = null;

    public static function bootStructureNode(): void
    {
        static::creating(function (self $model) {
            $model->node?->refresh();

            match ($model->nestedSetOperation) {
                Operation::MakeRoot => $model->beforeSavingRootNode(),
                Operation::PrependTo => $model->beforeSavingNode($model->node->getAttribute($model->leftAttribute) + 1, 1),
                Operation::AppendTo => $model->beforeSavingNode($model->node->getAttribute($model->rightAttribute), 1),
                Operation::InsertBefore => $model->beforeSavingNode($model->node->getAttribute($model->leftAttribute), 0),
                Operation::InsertAfter => $model->beforeSavingNode($model->node->getAttribute($model->rightAttribute) + 1, 0),
                default => throw new RuntimeException('::create is not supported for inserting new nodes.'),
            };
        });

        static::created(function (self $model) {
            if ($model->nestedSetOperation === Operation::MakeRoot) {
                $model->setAttribute($model->treeAttribute, $model->getKey());
                $primaryKey = $model->getKeyName();

                self::query()
                    ->where($primaryKey, $model->getAttribute($model->treeAttribute))
                    ->update([
                        $model->treeAttribute => $model->getAttribute($model->treeAttribute),
                    ]);

                $model->refresh();
            }

            $model->refresh();
            $model->node?->refresh();

            $model->nestedSetOperation = null;
            $model->node = null;
        });

        static::saving(function (self $model) {
            switch ($model->nestedSetOperation) {
                case Operation::MakeRoot:
                    if ($model->treeAttribute === false) {
                        throw new RuntimeException('Can not move a node as the root when "treeAttribute" is false.');
                    }

                    if ($model->isRoot()) {
                        throw new RuntimeException('Can not move the root node as the root.');
                    }

                    break;
                case Operation::InsertBefore:
                case Operation::InsertAfter:
                    if ($model->node->isRoot()) {
                        throw new RuntimeException('Can not move a node when the target node is root.');
                    }
                case Operation::PrependTo:
                case Operation::AppendTo:
                    if (! $model->node->exists) {
                        throw new RuntimeException('Can not move a node when the target node is new record.');
                    }

                    if ($model->is($model->node)) {
                        throw new RuntimeException('Can not move a node when the target node is same.');
                    }

                    if ($model->node->isChildOf($model)) {
                        throw new RuntimeException('Can not move a node when the target node is child.');
                    }
            }
        });

        static::saved(function (self $model) {
            switch ($model->nestedSetOperation) {
                case Operation::MakeRoot:
                    $model->moveNodeAsRoot();
                    break;
                case Operation::PrependTo:
                    $model->moveNode($model->node->getAttribute($model->leftAttribute) + 1, 1);
                    break;
                case Operation::AppendTo:
                    $model->moveNode($model->node->getAttribute($model->rightAttribute), 1);
                    break;
                case Operation::InsertBefore:
                    $model->moveNode($model->node->getAttribute($model->leftAttribute), 0);
                    break;
                case Operation::InsertAfter:
                    $model->moveNode($model->node->getAttribute($model->rightAttribute) + 1, 0);
                    break;
                default:
                    return;
            }

            $model->node->refresh();
            $model->refresh();

            $model->nestedSetOperation = null;
            $model->node = null;
        });

        static::deleting(function (self $model) {
            if ($model->isRoot() && $model->nestedSetOperation !== Operation::DeleteWithChildren) {
                throw new RuntimeException('Can not delete the root node when "nestedSetOperation" is not "deleteWithChildren".');
            }
        });

        static::deleted(function (self $model) {
            $leftValue = $model->getAttribute($model->leftAttribute);
            $rightValue = $model->getAttribute($model->rightAttribute);

            if ($model->isLeaf() || $model->nestedSetOperation === Operation::DeleteWithChildren) {
                $model->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);
            } else {
                $model->treeQuery()
                    ->where($model->leftAttribute, '>=', $model->getAttribute($model->leftAttribute))
                    ->where($model->rightAttribute, '<=', $model->getAttribute($model->rightAttribute))
                    ->update([
                        $model->leftAttribute => DB::raw($model->leftAttribute.sprintf('%+d', -1)),
                        $model->rightAttribute => DB::raw($model->rightAttribute.sprintf('%+d', -1)),
                        $model->depthAttribute => DB::raw($model->depthAttribute.sprintf('%+d', -1)),
                    ]);

                $model->shiftLeftRightAttribute($rightValue + 1, -2);
            }

            $model->node?->refresh();
            $model->nestedSetOperation = null;
            $model->node = null;
        });
    }

    protected function beforeSavingRootNode(): void
    {
        if ($this->treeAttribute === false && $this->roots()->exists()) {
            throw new RuntimeException('Can not create more than one root when "treeAttribute" is false.');
        }

        $this->setAttribute($this->leftAttribute, 1);
        $this->setAttribute($this->rightAttribute, 2);
        $this->setAttribute($this->depthAttribute, 0);
    }

    protected function beforeSavingNode(int $value, int $depth): void
    {
        if (! $this->node->exists) {
            throw new RuntimeException('Can not create a node when the target node is a new record.');
        }

        if ($depth === 0 && $this->node->isRoot()) {
            throw new RuntimeException('Can not create a node when the target node is root.');
        }

        $this->node->refresh();

        $this->setAttribute($this->leftAttribute, $value);
        $this->setAttribute($this->rightAttribute, $value + 1);
        $this->setAttribute($this->depthAttribute, $this->node->getAttribute($this->depthAttribute) + $depth);

        if ($this->treeAttribute !== false) {
            $this->setAttribute($this->treeAttribute, $this->node->getAttribute($this->treeAttribute));
        }

        $this->shiftLeftRightAttribute($value, 2);
    }

    protected function moveNodeAsRoot(): void
    {
        $leftValue = $this->getAttribute($this->leftAttribute);
        $rightValue = $this->getAttribute($this->rightAttribute);
        $depthValue = $this->getAttribute($this->depthAttribute);

        $this->treeQuery()
            ->where($this->leftAttribute, '>=', $leftValue)
            ->where($this->rightAttribute, '<=', $rightValue)
            ->update(array_merge([
                $this->leftAttribute => DB::raw($this->leftAttribute.sprintf('%+d', 1 - $leftValue)),
                $this->rightAttribute => DB::raw($this->rightAttribute.sprintf('%+d', 1 - $leftValue)),
                $this->depthAttribute => DB::raw($this->depthAttribute.sprintf('%+d', -$depthValue)),
            ], $this->treeAttribute !== false ? [
                $this->treeAttribute => $this->getKey(),
            ] : []));

        $this->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);

        $this->refresh();
    }

    protected function moveNode(int $value, int $depth): void
    {
        $this->node->refresh();

        $leftValue = $this->getAttribute($this->leftAttribute);
        $rightValue = $this->getAttribute($this->rightAttribute);
        $depthValue = $this->getAttribute($this->depthAttribute);
        $depth = $this->node->getAttribute($this->depthAttribute) - $depthValue + $depth;

        if ($this->treeAttribute === false || $this->getAttribute($this->treeAttribute) === $this->node->getAttribute($this->treeAttribute)) {
            $delta = $rightValue - $leftValue + 1;
            $this->shiftLeftRightAttribute($value, $delta);

            if ($leftValue >= $value) {
                $leftValue += $delta;
                $rightValue += $delta;
            }

            $this->treeQuery()
                ->where($this->leftAttribute, '>=', $leftValue)
                ->where($this->rightAttribute, '<=', $rightValue)
                ->update([
                    $this->depthAttribute => DB::raw($this->depthAttribute.sprintf('%+d', $depth)),
                ]);

            foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
                $this->treeQuery()
                    ->where($attribute, '>=', $leftValue)
                    ->where($attribute, '<=', $rightValue)
                    ->update([
                        $attribute => DB::raw($attribute.sprintf('%+d', $value - $leftValue)),
                    ]);
            }

            $this->shiftLeftRightAttribute($rightValue + 1, -$delta);

            return;
        }

        $nodeRootValue = $this->node->getAttribute($this->treeAttribute);

        foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
            self::query()
                ->where($attribute, '>=', $value)
                ->where($this->treeAttribute, '=', $nodeRootValue)
                ->update([
                    $attribute => DB::raw($attribute.sprintf('%+d', $rightValue - $leftValue + 1)),
                ]);
        }

        $delta = $value - $leftValue;

        self::query()
            ->where($this->leftAttribute, '>=', $leftValue)
            ->where($this->rightAttribute, '<=', $rightValue)
            ->where($this->treeAttribute, '=', $this->getAttribute($this->treeAttribute))
            ->update([
                $this->leftAttribute => DB::raw($this->leftAttribute.sprintf('%+d', $delta)),
                $this->rightAttribute => DB::raw($this->rightAttribute.sprintf('%+d', $delta)),
                $this->depthAttribute => DB::raw($this->depthAttribute.sprintf('%+d', $depth)),
                $this->treeAttribute => $nodeRootValue,
            ]);

        $this->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);
    }

    protected function shiftLeftRightAttribute(int $value, int $delta): void
    {
        foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
            $this->treeQuery()
                ->where($attribute, '>=', $value)
                ->update([
                    $attribute => DB::raw($attribute.sprintf('%+d', $delta)),
                ]);
        }
    }

    public function makeRoot(): bool
    {
        $this->nestedSetOperation = Operation::MakeRoot;

        return $this->save();
    }

    public function prependTo(self $node): bool
    {
        $this->nestedSetOperation = Operation::PrependTo;
        $this->node = $node;

        return $this->save();
    }

    public function appendTo(self $node): bool
    {
        $this->nestedSetOperation = Operation::AppendTo;
        $this->node = $node;

        return $this->save();
    }

    public function insertBefore(self $node): bool
    {
        $this->nestedSetOperation = Operation::InsertBefore;
        $this->node = $node;

        return $this->save();
    }

    public function insertAfter(self $node): bool
    {
        $this->nestedSetOperation = Operation::InsertAfter;
        $this->node = $node;

        return $this->save();
    }

    public function deleteWithChildren(): int
    {
        $this->nestedSetOperation = Operation::DeleteWithChildren;

        return $this->treeQuery()
            ->where($this->leftAttribute, '>=', $this->getAttribute($this->leftAttribute))
            ->where($this->rightAttribute, '<=', $this->getAttribute($this->rightAttribute))
            ->delete();
    }

    public function parents(?int $depth = null): Builder
    {
        return $this->treeQuery()
            ->where($this->leftAttribute, '<', $this->getAttribute($this->leftAttribute))
            ->where($this->rightAttribute, '>', $this->getAttribute($this->rightAttribute))
            ->unless(
                is_null($depth),
                fn (Builder $query) => $query->where($this->depthAttribute, '>=',
                    $this->getAttribute($this->depthAttribute) - $depth),
            )
            ->orderBy($this->leftAttribute);
    }

    public function children(?int $depth = null): Builder
    {
        return $this->treeQuery()
            ->where($this->leftAttribute, '>', $this->getAttribute($this->leftAttribute))
            ->where($this->rightAttribute, '<', $this->getAttribute($this->rightAttribute))
            ->unless(
                is_null($depth),
                fn (Builder $query) => $query->where($this->depthAttribute, '<=',
                    $this->getAttribute($this->depthAttribute) + $depth),
            )
            ->orderBy($this->leftAttribute);
    }

    public function leaves(): Builder
    {
        return $this->treeQuery()
            ->where($this->leftAttribute, '>', $this->getAttribute($this->leftAttribute))
            ->where($this->rightAttribute, '<', $this->getAttribute($this->rightAttribute))
            ->where($this->rightAttribute, '=', DB::raw($this->leftAttribute.' + 1'))
            ->orderBy($this->leftAttribute);
    }

    public function prev(): Builder
    {
        return $this->treeQuery()->where($this->rightAttribute, '=', $this->getAttribute($this->leftAttribute) - 1);
    }

    public function next(): Builder
    {
        return $this->treeQuery()->where($this->leftAttribute, '=', $this->getAttribute($this->rightAttribute) + 1);
    }

    public function isRoot(): bool
    {
        return (int) $this->getAttribute($this->leftAttribute) === 1;
    }

    public function isChildOf(self $node): bool
    {
        $result = $this->getAttribute($this->leftAttribute) > $node->getAttribute($this->leftAttribute)
            && $this->getAttribute($this->rightAttribute) < $node->getAttribute($this->rightAttribute);

        if ($result && $this->treeAttribute !== false) {
            $result = $this->getAttribute($this->treeAttribute) === $node->getAttribute($this->treeAttribute);
        }

        return $result;
    }

    public function isLeaf(): bool
    {
        return $this->getAttribute($this->rightAttribute) - $this->getAttribute($this->leftAttribute) === 1;
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query
            ->where($this->leftAttribute, '=', 1)
            ->orderBy($this->getKeyName());
    }

    public function scopeLeaves(Builder $query): Builder
    {
        return $query
            ->where($this->rightAttribute, DB::raw($this->leftAttribute.' + 1'))
            ->when($this->treeAttribute !== false, fn (Builder $query) => $query->orderBy($this->treeAttribute))
            ->orderBy($this->leftAttribute);
    }

    protected function treeQuery(?Builder $query = null): Builder
    {
        $query ??= self::query();

        if ($this->treeAttribute === false) {
            return $query;
        }

        return $query->where($this->treeAttribute, '=', $this->getAttribute($this->treeAttribute));
    }
}
