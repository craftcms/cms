<?php

namespace craft\base\imagetransforms;

interface EagerImageTransformerInterface
{
    public function eagerLoadTransforms(array $transforms, array $assets): void;
}
