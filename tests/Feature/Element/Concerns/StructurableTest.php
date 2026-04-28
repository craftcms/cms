<?php

declare(strict_types=1);

beforeEach(function () {
    [
        'root' => $this->root,
        'children' => [$this->child1, $this->child2],
        'nested' => [$this->grandChild],
    ] = createStructureHierarchy();
});

describe('parent/child relationships', function () {
    test('getParent returns null for root element', function () {
        expect($this->root->getParent())->toBeNull();
    });

    test('getParent returns parent element', function () {
        expect($this->child1->getParent()->id)->toBe($this->root->id);
    });

    test('getParentId returns parent ID', function () {
        expect($this->child1->getParentId())->toBe($this->root->id);
    });

    test('getParentId returns null for root element', function () {
        expect($this->root->getParentId())->toBeNull();
    });

    test('setParent updates parent and level', function () {
        $this->child2->setParent($this->child1);

        expect($this->child2->getParentId())->toBe($this->child1->id);
        expect($this->child2->level)->toBe($this->child1->level + 1);
    });

    test('setParent to null sets level to 1', function () {
        $this->child1->setParent(null);

        expect($this->child1->getParentId())->toBeNull();
        expect($this->child1->level)->toBe(1);
    });

    test('setParentId accepts array and uses first value', function () {
        $this->child1->setParentId([$this->root->id, 999]);

        expect($this->child1->getParentId())->toBe($this->root->id);
    });

    test('getParentUri returns parent uri', function () {
        $this->root->uri = 'parent-page';
        $this->child1->setParent($this->root);

        expect($this->child1->getParentUri())->toBe('parent-page');
    });

    test('getParentUri returns null for homepage parent', function () {
        $this->root->uri = '__home__';
        $this->child1->setParent($this->root);

        expect($this->child1->getParentUri())->toBeNull();
    });
});

describe('ancestor relationships', function () {
    test('getAncestors returns all ancestors', function () {
        $ancestors = $this->grandChild->getAncestors()->all();

        expect($ancestors)->toHaveCount(2);
        expect($ancestors[0]->id)->toBe($this->root->id);
        expect($ancestors[1]->id)->toBe($this->child1->id);
    });

    test('getAncestors with distance limit', function () {
        $ancestors = $this->grandChild->getAncestors(1)->all();

        expect($ancestors)->toHaveCount(1);
        expect($ancestors[0]->id)->toBe($this->child1->id);
    });

    test('isAncestorOf returns true for ancestor', function () {
        expect($this->root->isAncestorOf($this->grandChild))->toBeTrue();
    });

    test('isAncestorOf returns false for non-ancestor', function () {
        expect($this->child2->isAncestorOf($this->grandChild))->toBeFalse();
    });
});

describe('descendant relationships', function () {
    test('getDescendants returns all descendants', function () {
        $descendants = $this->root->getDescendants()->all();

        expect($descendants)->toHaveCount(3);
        $descendantIds = array_map(fn ($e) => $e->id, $descendants);
        expect($descendantIds)->toContain($this->child1->id);
        expect($descendantIds)->toContain($this->child2->id);
        expect($descendantIds)->toContain($this->grandChild->id);
    });

    test('getDescendants with distance limit', function () {
        $descendants = $this->root->getDescendants(1)->all();

        expect($descendants)->toHaveCount(2);
        $descendantIds = array_map(fn ($e) => $e->id, $descendants);
        expect($descendantIds)->toContain($this->child1->id);
        expect($descendantIds)->toContain($this->child2->id);
    });

    test('isDescendantOf returns true for descendant', function () {
        expect($this->grandChild->isDescendantOf($this->root))->toBeTrue();
    });

    test('isDescendantOf returns false for non-descendant', function () {
        expect($this->grandChild->isDescendantOf($this->child2))->toBeFalse();
    });

    test('getHasDescendants returns true when has descendants', function () {
        expect($this->root->getHasDescendants())->toBeTrue();
    });

    test('getHasDescendants returns false for leaf', function () {
        expect($this->grandChild->getHasDescendants())->toBeFalse();
    });

    test('getTotalDescendants returns count', function () {
        expect($this->root->getTotalDescendants())->toBe(3);
    });
});

describe('children relationships', function () {
    test('getChildren returns direct children', function () {
        $children = $this->root->getChildren()->all();

        expect($children)->toHaveCount(2);
        $childIds = array_map(fn ($e) => $e->id, $children);
        expect($childIds)->toContain($this->child1->id);
        expect($childIds)->toContain($this->child2->id);
    });

    test('isParentOf returns true for direct child', function () {
        expect($this->root->isParentOf($this->child1))->toBeTrue();
    });

    test('isParentOf returns false for grandchild', function () {
        expect($this->root->isParentOf($this->grandChild))->toBeFalse();
    });

    test('isChildOf returns true for parent', function () {
        expect($this->child1->isChildOf($this->root))->toBeTrue();
    });

    test('isChildOf returns false for grandparent', function () {
        expect($this->grandChild->isChildOf($this->root))->toBeFalse();
    });
});

describe('sibling relationships', function () {
    test('getSiblings returns siblings', function () {
        $siblings = $this->child1->getSiblings()->all();

        expect($siblings)->toHaveCount(1);
        expect($siblings[0]->id)->toBe($this->child2->id);
    });

    test('getPrevSibling returns previous sibling', function () {
        expect($this->child2->getPrevSibling()->id)->toBe($this->child1->id);
    });

    test('getPrevSibling returns null for first sibling', function () {
        expect($this->child1->getPrevSibling())->toBeNull();
    });

    test('getNextSibling returns next sibling', function () {
        expect($this->child1->getNextSibling()->id)->toBe($this->child2->id);
    });

    test('getNextSibling returns null for last sibling', function () {
        expect($this->child2->getNextSibling())->toBeNull();
    });

    test('isSiblingOf returns true for siblings', function () {
        expect($this->child1->isSiblingOf($this->child2))->toBeTrue();
    });

    test('isSiblingOf returns false for non-siblings', function () {
        expect($this->child1->isSiblingOf($this->grandChild))->toBeFalse();
    });

    test('isPrevSiblingOf returns true for previous sibling', function () {
        expect($this->child1->isPrevSiblingOf($this->child2))->toBeTrue();
    });

    test('isPrevSiblingOf returns false for next sibling', function () {
        expect($this->child2->isPrevSiblingOf($this->child1))->toBeFalse();
    });

    test('isNextSiblingOf returns true for next sibling', function () {
        expect($this->child2->isNextSiblingOf($this->child1))->toBeTrue();
    });

    test('isNextSiblingOf returns false for previous sibling', function () {
        expect($this->child1->isNextSiblingOf($this->child2))->toBeFalse();
    });
});

describe('next/prev element navigation', function () {
    test('setNext and getNext work together', function () {
        $this->child1->setNext($this->child2);

        expect($this->child1->getNext()->id)->toBe($this->child2->id);
    });

    test('setPrev and getPrev work together', function () {
        $this->child2->setPrev($this->child1);

        expect($this->child2->getPrev()->id)->toBe($this->child1->id);
    });

    test('getNext returns null when set to false', function () {
        $this->child1->setNext(false);

        expect($this->child1->getNext())->toBeNull();
    });

    test('getPrev returns null when set to false', function () {
        $this->child1->setPrev(false);

        expect($this->child1->getPrev())->toBeNull();
    });
});
