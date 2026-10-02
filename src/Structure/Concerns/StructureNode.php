<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Concerns;

use Closure;
use CraftCms\Cms\Structure\Data\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

trait StructureNode
{
    public protected(set) ?Operation $nestedSetOperation = null;

    private bool $nestedSetOperationHandledOnCreate = false;

    public static function bootStructureNode(): void
    {
        static::creating(function (self $model) {
            $operation = $model->nestedSetOperation;
            $operation?->target?->refresh();

            match ($operation?->type) {
                Operation::MakeRoot => $model->beforeSavingRootNode(),
                Operation::PrependTo => $model->beforeSavingNode($operation->target->lft + 1, 1, $operation->target),
                Operation::AppendTo => $model->beforeSavingNode($operation->target->rgt, 1, $operation->target),
                Operation::InsertBefore => $model->beforeSavingNode($operation->target->lft, 0, $operation->target),
                Operation::InsertAfter => $model->beforeSavingNode($operation->target->rgt + 1, 0, $operation->target),
                default => throw new RuntimeException('::create is not supported for inserting new nodes.'),
            };
        });

        static::created(function (self $model) {
            if ($model->nestedSetOperation?->type === Operation::MakeRoot) {
                $model->setAttribute('root', $model->getKey());
                $primaryKey = $model->getKeyName();

                static::query()
                    ->where($primaryKey, $model->root)
                    ->update([
                        'root' => $model->root,
                    ]);

                $model->refresh();
            }

            $model->refresh();
            $model->nestedSetOperation?->target?->refresh();

            $model->nestedSetOperationHandledOnCreate = true;
        });

        static::saving(function (self $model) {
            $operation = $model->nestedSetOperation;

            switch ($operation?->type) {
                case Operation::MakeRoot:
                    if ($model->isRoot()) {
                        throw new RuntimeException('Can not move the root node as the root.');
                    }

                    break;
                case Operation::InsertBefore:
                case Operation::InsertAfter:
                    if ($operation->target->isRoot()) {
                        throw new RuntimeException('Can not move a node when the target node is root.');
                    }
                case Operation::PrependTo:
                case Operation::AppendTo:
                    if (! $operation->target->exists) {
                        throw new RuntimeException('Can not move a node when the target node is new record.');
                    }

                    if ($model->is($operation->target)) {
                        throw new RuntimeException('Can not move a node when the target node is same.');
                    }

                    if ($operation->target->isChildOf($model)) {
                        throw new RuntimeException('Can not move a node when the target node is child.');
                    }
            }
        });

        static::saved(function (self $model) {
            if ($model->nestedSetOperationHandledOnCreate) {
                $model->nestedSetOperationHandledOnCreate = false;

                return;
            }

            $operation = $model->nestedSetOperation;

            switch ($operation?->type) {
                case Operation::MakeRoot:
                    $model->moveNodeAsRoot();
                    break;
                case Operation::PrependTo:
                    $model->moveNode($operation->target->lft + 1, 1, $operation->target);
                    break;
                case Operation::AppendTo:
                    $model->moveNode($operation->target->rgt, 1, $operation->target);
                    break;
                case Operation::InsertBefore:
                    $model->moveNode($operation->target->lft, 0, $operation->target);
                    break;
                case Operation::InsertAfter:
                    $model->moveNode($operation->target->rgt + 1, 0, $operation->target);
                    break;
                default:
                    return;
            }

            $operation->target?->refresh();
            $model->refresh();
        });

        static::deleting(function (self $model) {
            if ($model->isRoot() && $model->nestedSetOperation?->type !== Operation::DeleteWithChildren) {
                throw new RuntimeException('Can not delete the root node when "nestedSetOperation" is not "deleteWithChildren".');
            }
        });

        static::deleted(function (self $model) {
            $leftValue = $model->lft;
            $rightValue = $model->rgt;

            if ($model->isLeaf() || $model->nestedSetOperation?->type === Operation::DeleteWithChildren) {
                $model->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);
            } else {
                $model->treeQuery()
                    ->where('lft', '>=', $model->lft)
                    ->where('rgt', '<=', $model->rgt)
                    ->update([
                        'lft' => DB::raw('lft'.sprintf('%+d', -1)),
                        'rgt' => DB::raw('rgt'.sprintf('%+d', -1)),
                        'level' => DB::raw('level'.sprintf('%+d', -1)),
                    ]);

                $model->shiftLeftRightAttribute($rightValue + 1, -2);
            }
        });
    }

    protected function beforeSavingRootNode(): void
    {
        if ($this->treeQuery()->roots()->exists()) {
            throw new RuntimeException("A root already exists for structure {$this->root}.");
        }

        $this->lft = 1;
        $this->rgt = 2;
        $this->level = 0;
    }

    protected function beforeSavingNode(int $value, int $depth, self $target): void
    {
        if (! $target->exists) {
            throw new RuntimeException('Can not create a node when the target node is a new record.');
        }

        if ($depth === 0 && $target->isRoot()) {
            throw new RuntimeException('Can not create a node when the target node is root.');
        }

        $target->refresh();

        $this->lft = $value;
        $this->rgt = $value + 1;
        $this->level = $target->level + $depth;
        $this->root = $target->root;

        $this->shiftLeftRightAttribute($value, 2);
    }

    protected function moveNodeAsRoot(): void
    {
        $leftValue = $this->lft;
        $rightValue = $this->rgt;
        $depthValue = $this->level;

        $this->treeQuery()
            ->where('lft', '>=', $leftValue)
            ->where('rgt', '<=', $rightValue)
            ->update([
                'lft' => DB::raw('lft'.sprintf('%+d', 1 - $leftValue)),
                'rgt' => DB::raw('rgt'.sprintf('%+d', 1 - $leftValue)),
                'level' => DB::raw('level'.sprintf('%+d', -$depthValue)),
                'root' => $this->getKey(),
            ]);

        $this->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);

        $this->refresh();
    }

    protected function moveNode(int $value, int $depth, self $target): void
    {
        $target->refresh();

        $leftValue = $this->lft;
        $rightValue = $this->rgt;
        $depthValue = $this->level;
        $depth = $target->level - $depthValue + $depth;

        if ($this->root === $target->root) {
            $delta = $rightValue - $leftValue + 1;
            $this->shiftLeftRightAttribute($value, $delta);

            if ($leftValue >= $value) {
                $leftValue += $delta;
                $rightValue += $delta;
            }

            $this->treeQuery()
                ->where('lft', '>=', $leftValue)
                ->where('rgt', '<=', $rightValue)
                ->update([
                    'level' => DB::raw('level'.sprintf('%+d', $depth)),
                ]);

            foreach (['lft', 'rgt'] as $attribute) {
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

        $nodeRootValue = $target->root;

        foreach (['lft', 'rgt'] as $attribute) {
            static::query()
                ->where($attribute, '>=', $value)
                ->where('root', '=', $nodeRootValue)
                ->update([
                    $attribute => DB::raw($attribute.sprintf('%+d', $rightValue - $leftValue + 1)),
                ]);
        }

        $delta = $value - $leftValue;

        static::query()
            ->where('lft', '>=', $leftValue)
            ->where('rgt', '<=', $rightValue)
            ->where('root', '=', $this->root)
            ->update([
                'lft' => DB::raw('lft'.sprintf('%+d', $delta)),
                'rgt' => DB::raw('rgt'.sprintf('%+d', $delta)),
                'level' => DB::raw('level'.sprintf('%+d', $depth)),
                'root' => $nodeRootValue,
            ]);

        $this->shiftLeftRightAttribute($rightValue + 1, $leftValue - $rightValue - 1);
    }

    protected function shiftLeftRightAttribute(int $value, int $delta): void
    {
        foreach (['lft', 'rgt'] as $attribute) {
            $this->treeQuery()
                ->where($attribute, '>=', $value)
                ->update([
                    $attribute => DB::raw($attribute.sprintf('%+d', $delta)),
                ]);
        }
    }

    public function makeRoot(): bool
    {
        return $this->performNestedSetOperation(
            Operation::makeRoot(),
            fn () => $this->save(),
        );
    }

    public function prependTo(self $node): bool
    {
        return $this->performNestedSetOperation(
            Operation::prependTo($node),
            fn () => $this->save(),
        );
    }

    public function appendTo(self $node): bool
    {
        return $this->performNestedSetOperation(
            Operation::appendTo($node),
            fn () => $this->save(),
        );
    }

    public function insertBefore(self $node): bool
    {
        return $this->performNestedSetOperation(
            Operation::insertBefore($node),
            fn () => $this->save(),
        );
    }

    public function insertAfter(self $node): bool
    {
        return $this->performNestedSetOperation(
            Operation::insertAfter($node),
            fn () => $this->save(),
        );
    }

    public function deleteWithChildren(): int
    {
        return $this->performNestedSetOperation(
            Operation::deleteWithChildren(),
            fn () => $this->treeQuery()
                ->where('lft', '>=', $this->lft)
                ->where('rgt', '<=', $this->rgt)
                ->delete(),
        );
    }

    public function delete(): ?bool
    {
        return $this->performNestedSetOperation(
            Operation::remove(),
            fn () => parent::delete(),
        );
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function performNestedSetOperation(Operation $operation, Closure $callback): mixed
    {
        if ($this->nestedSetOperation !== null) {
            throw new RuntimeException('A nested set operation is already in progress.');
        }

        $this->nestedSetOperation = $operation;

        try {
            DB::beginTransaction();

            try {
                $result = $callback();

                if ($result === false) {
                    DB::rollBack();

                    return false;
                }

                DB::commit();

                return $result;
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } finally {
            $this->nestedSetOperation = null;
            $this->nestedSetOperationHandledOnCreate = false;
        }
    }

    /** @return Builder<static> */
    public function parents(?int $depth = null): Builder
    {
        return $this->treeQuery()
            ->where('lft', '<', $this->lft)
            ->where('rgt', '>', $this->rgt)
            ->unless(
                is_null($depth),
                fn (Builder $query) => $query->where('level', '>=', $this->level - $depth),
            )
            ->orderBy('lft');
    }

    /** @return Builder<static> */
    public function children(?int $depth = null): Builder
    {
        return $this->treeQuery()
            ->where('lft', '>', $this->lft)
            ->where('rgt', '<', $this->rgt)
            ->unless(
                is_null($depth),
                fn (Builder $query) => $query->where('level', '<=', $this->level + $depth),
            )
            ->orderBy('lft');
    }

    /** @return Builder<static> */
    public function leaves(): Builder
    {
        return $this->treeQuery()
            ->where('lft', '>', $this->lft)
            ->where('rgt', '<', $this->rgt)
            ->where('rgt', '=', DB::raw('lft'.' + 1'))
            ->orderBy('lft');
    }

    /** @return Builder<static> */
    public function prev(): Builder
    {
        return $this->treeQuery()->where('rgt', '=', $this->lft - 1);
    }

    /** @return Builder<static> */
    public function next(): Builder
    {
        return $this->treeQuery()->where('lft', '=', $this->rgt + 1);
    }

    public function isRoot(): bool
    {
        return $this->lft === 1;
    }

    public function isChildOf(self $node): bool
    {
        $result = $this->lft > $node->getAttribute('lft')
            && $this->rgt < $node->getAttribute('rgt');

        if ($result) {
            return $this->root === $node->getAttribute('root');
        }

        return $result;
    }

    public function isLeaf(): bool
    {
        return $this->rgt - $this->lft === 1;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeRoots(Builder $query): Builder
    {
        return $query
            ->where('lft', '=', 1)
            ->orderBy($this->getKeyName());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function scopeLeaves(Builder $query): Builder
    {
        return $query
            ->where('rgt', DB::raw('lft'.' + 1'))
            ->orderBy('root')
            ->orderBy('lft');
    }

    /**
     * @param  Builder<static>|null  $query
     * @return Builder<static>
     */
    protected function treeQuery(?Builder $query = null): Builder
    {
        $query ??= static::query();

        return $query->where('root', '=', $this->root);
    }
}
