<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsFixes;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsStore;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsUtility;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @internal
 */
class LooseEndsController
{
    use RespondsWithFlash;

    public function resolve(int $id, Utilities $utilities, LooseEndsStore $store): Response
    {
        if (!$utilities->checkAuthorization(LooseEndsUtility::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        if (!$store->resolveUsage($id)) {
            return $this->asFailure(t('Couldn’t mark the item resolved.', category: 'yii2-adapter'));
        }

        return $this->asSuccess(t('Item marked resolved.', category: 'yii2-adapter'));
    }

    public function fix(Request $request, Utilities $utilities, LooseEndsFixes $fixes): Response
    {
        if (!$utilities->checkAuthorization(LooseEndsUtility::class) || !$fixes->allowed()) {
            abort(403, 'User is not authorized to perform this action.');
        }

        try {
            $fixed = $fixes->fix($request->string('key')->toString());
        } catch (Throwable $e) {
            report($e);
            $fixed = false;
        }

        if (!$fixed) {
            return $this->asFailure(t('Couldn’t fix the item.', category: 'yii2-adapter'));
        }

        return $this->asSuccess(t('Item fixed.', category: 'yii2-adapter'));
    }
}
