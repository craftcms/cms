<?php

declare(strict_types=1);

namespace CraftCms\Cms\Structure\Concerns;

use CraftCms\Cms\Structure\Enums\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait StructureNode
{
    public protected(set) ?Operation $nestedSetOperation = null;

    private ?self $node = null;

    public static function bootStructureNode(): void
    {
        static::creating(function (self $model) {
            $model->node?->refresh();

            match ($model->nestedSetOperation) {
                Operation::MakeRoot => $model->beforeSavingRootNode(),
                Operation::PrependTo => $model->beforeSavingNode($model->node->lft + 1, 1),
                Operation::AppendTo => $model->beforeSavingNode($model->node->rgt, 1),
                Operation::InsertBefore => $model->beforeSavingNode($model->node->lft, 0),
                Operation::InsertAfter => $model->beforeSavingNode($model->node->rgt + 1, 0),
                default => throw new RuntimeException('::create is not supported for inserting new nodes.'),
            };
        });

        static::created(function (self $model) {
            if ($model->nestedSetOperation === Operation::MakeRoot) {
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
            $model->node?->refresh();

            $model->nestedSetOperation = null;
            $model->node = null;
        });

        static::saving(function (self $model) {
            switch ($model->nestedSetOperation) {
                case Operation::MakeRoot:
                    throw_if($model->isRoot(), RuntimeException::class, 'Can not move the root node as the root.');

                    break;
                case Operation::InsertBefore:
                case Operation::InsertAfter:
                    throw_if($model->node->isRoot(), RuntimeException::class, 'Can not move a node when the target node is root.');
                case Operation::PrependTo:
                case Operation::AppendTo:
                    throw_unless($model->node->exists, RuntimeException::class, 'Can not move a node when the target node is new record.');

                    throw_if($model->is($model->node), RuntimeException::class, 'Can not move a node when the target node is same.');

                    throw_if($model->node->isChildOf($model), RuntimeException::class, 'Can not move a node when the target node is child.');
            }
        });

        static::saved(function (self $model) {
            switch ($model->nestedSetOperation) {
                case Operation::MakeRoot:
                    $model->moveNodeAsRoot();
                    break;
                case Operation::PrependTo:
                    $model->moveNode($model->node->lft + 1, 1);
                    break;
                case Operation::AppendTo:
                    $model->moveNode($model->node->rgt, 1);
                    break;
                case Operation::InsertBefore:
                    $model->moveNode($model->node->lft, 0);
                    break;
                case Operation::InsertAfter:
                    $model->moveNode($model->node->rgt + 1, 0);
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
            throw_if($model->isRoot() && $model->nestedSetOperation !== Operation::DeleteWithChildren, RuntimeException::class, 'Can not delete the root node when "nestedSetOperation" is not "deleteWithChildren".');
        });

        static::deleted(function (self $model) {
            $leftValue = $model->lft;
            $rightValue = $model->rgt;

            if ($model->isLeaf() || $model->nestedSetOperation === Operation::DeleteWithChildren) {
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

            $model->node?->refresh();
            $model->nestedSetOperation = null;
            $model->node = null;
        });
    }

    protected function beforeSavingRootNode(): void
    {
        throw_if($this->treeQuery()->roots()->exists(), RuntimeException::class, "A root already exists for structure {$this->root}.");

        $this->lft = 1;
        $this->rgt = 2;
        $this->level = 0;
    }

    protected function beforeSavingNode(int $value, int $depth): void
    {
        throw_unless($this->node->exists, RuntimeException::class, 'Can not create a node when the target node is a new record.');

        throw_if($depth === 0 && $this->node->isRoot(), RuntimeException::class, 'Can not create a node when the target node is root.');

        $this->node->refresh();

        $this->lft = $value;
        $this->rgt = $value + 1;
        $this->level = $this->node->level + $depth;
        $this->root = $this->node->root;

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

    protected function moveNode(int $value, int $depth): void
    {
        $this->node->refresh();

        $leftValue = $this->lft;
        $rightValue = $this->rgt;
        $depthValue = $this->level;
        $depth = $this->node->level - $depthValue + $depth;

        if ($this->root === $this->node->root) {
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

        $nodeRootValue = $this->node->root;

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
            ->where('lft', '>=', $this->lft)
            ->where('rgt', '<=', $this->rgt)
            ->delete();
    }

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

    public function leaves(): Builder
    {
        return $this->treeQuery()
            ->where('lft', '>', $this->lft)
            ->where('rgt', '<', $this->rgt)
            ->where('rgt', '=', DB::raw('lft'.' + 1'))
            ->orderBy('lft');
    }

    public function prev(): Builder
    {
        return $this->treeQuery()->where('rgt', '=', $this->lft - 1);
    }

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

    protected function scopeRoots(Builder $query): Builder
    {
        return $query
            ->where('lft', '=', 1)
            ->orderBy($this->getKeyName());
    }

    protected function scopeLeaves(Builder $query): Builder
    {
        return $query
            ->where('rgt', DB::raw('lft'.' + 1'))
            ->orderBy('root')
            ->orderBy('lft');
    }

    /** @return Builder<static> */
    protected function treeQuery(?Builder $query = null): Builder
    {
        $query ??= static::query();

        return $query->where('root', '=', $this->root);
    }
}
