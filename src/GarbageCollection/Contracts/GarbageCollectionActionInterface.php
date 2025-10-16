<?php

namespace CraftCms\Cms\GarbageCollection\Contracts;

interface GarbageCollectionActionInterface
{
    public function run(): void;
}
