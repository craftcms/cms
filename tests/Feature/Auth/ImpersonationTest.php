<?php

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\User\Elements\User;

beforeEach(function () {
    $this->impersonation = app(Impersonation::class);
});

test('impersonation', function () {
    expect($this->impersonation->getImpersonator())->toBeNull();
    expect($this->impersonation->getImpersonatorId())->toBeNull();
    expect($this->impersonation->isImpersonating())->toBeFalse();

    $id = User::findOne()->id;

    $this->impersonation->setImpersonatorId($id);

    expect($this->impersonation->getImpersonator())->toBeInstanceOf(User::class);
    expect($this->impersonation->getImpersonatorId())->toBe($id);
    expect($this->impersonation->isImpersonating())->toBeTrue();

    $this->impersonation->setImpersonatorId(null);

    expect($this->impersonation->getImpersonator())->toBeNull();
    expect($this->impersonation->getImpersonatorId())->toBeNull();
    expect($this->impersonation->isImpersonating())->toBeFalse();
});
