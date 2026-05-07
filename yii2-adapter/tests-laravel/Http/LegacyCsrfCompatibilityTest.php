<?php

namespace craft\controllers {
    use craft\web\Controller;
    use yii\web\Response;

    class CsrfCompatibilityController extends Controller
    {
        public $enableCsrfValidation = false;

        protected array|bool|int $allowAnonymous = true;

        public function actionPing(): Response
        {
            return $this->asJson(['ok' => true]);
        }
    }
}

namespace {
    use CraftCms\Cms\Cms;
    use CraftCms\Yii2Adapter\Http\ExcludeCsrfValidationForLegacyController;
    use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
    use Illuminate\Http\Request;

    it('excludes legacy controller action URLs when CSRF validation is disabled', function() {
        $property = new ReflectionProperty(PreventRequestForgery::class, 'neverVerify');
        $original = $property->getValue();

        try {
            $request = Request::create('/actions/csrf-compatibility/ping', 'POST');

            app(ExcludeCsrfValidationForLegacyController::class)->handle($request, fn() => response('ok'));

            $actionTrigger = trim(Cms::config()->actionTrigger, '/');
            $cpTrigger = trim(Cms::config()->cpTrigger, '/');

            expect(app(PreventRequestForgery::class)->getExcludedPaths())->toContain(
                'csrf-compatibility',
                'csrf-compatibility/*',
                "$actionTrigger/csrf-compatibility",
                "$actionTrigger/csrf-compatibility/*",
                "$cpTrigger/$actionTrigger/csrf-compatibility",
                "$cpTrigger/$actionTrigger/csrf-compatibility/*",
            );
        } finally {
            $property->setValue(null, $original);
        }
    });
}
