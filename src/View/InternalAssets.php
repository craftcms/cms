<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Chart;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Http\Request;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Yiisoft\Aliases\Aliases as YiiAliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoader;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisher;
use Yiisoft\Files\FileHelper;

#[Scoped]
class InternalAssets
{
    private readonly AssetManager $assetManager;

    /** @var array<string, true> */
    private array $registeredBundles = [];

    /** @var array<string, true> */
    private array $registeredJsFiles = [];

    private readonly string $assetRoot;

    public function __construct(
        private readonly CpBootstrap $cpBootstrap,
        private readonly HtmlStack $htmlStack,
        private readonly Request $request,
        private readonly YiiAliases $aliases,
    ) {
        $config = Cms::config();
        $this->assetRoot = dirname(__DIR__, 2).'/yii2-adapter/legacy/web/assets';

        $loader = new AssetLoader($this->aliases)
            ->withAppendTimestamp(true)
            ->withBasePath($config->resourceBasePath)
            ->withBaseUrl($config->resourceBaseUrl);

        $publisher = new AssetPublisher($this->aliases)
            ->withDirMode((int) $config->defaultDirMode)
            ->withHashCallback($this->hashPath(...));

        if ($config->defaultFileMode !== null) {
            $publisher = $publisher->withFileMode($config->defaultFileMode);
        }

        $this->assetManager = new AssetManager(
            aliases: $this->aliases,
            loader: $loader,
            customizedBundles: $this->bundleConfigs(),
        )->withPublisher($publisher);

        $this->markAsRegistered(
            array_filter(explode(',', (string) $this->request->header('X-Registered-Asset-Bundles', ''))),
            array_filter(explode(',', (string) $this->request->header('X-Registered-Js-Files', ''))),
        );
    }

    /**
     * @param  list<string>  $bundleIds
     * @param  list<string>  $jsFiles
     */
    public function markAsRegistered(array $bundleIds, array $jsFiles): void
    {
        foreach ($bundleIds as $bundleId) {
            $bundleId = trim($bundleId);
            if ($bundleId !== '') {
                $this->registeredBundles[$bundleId] = true;
            }
        }

        foreach ($jsFiles as $jsFile) {
            $jsFile = trim($jsFile);
            if ($jsFile !== '') {
                $this->registeredJsFiles[$jsFile] = true;
            }
        }
    }

    public function register(string $bundleId): void
    {
        if (isset($this->registeredBundles[$bundleId])) {
            return;
        }

        $bundle = $this->assetManager->getBundle($bundleId);

        foreach ($bundle->depends as $dependency) {
            $this->register($dependency);
        }

        $this->registerBundleAssets($bundle, $bundleId);

        $this->registeredBundles[$bundleId] = true;

        $this->applyBundleSideEffects($bundleId);
        $this->syncClientRegistry();
    }

    public function url(string $bundleId, string $path = ''): string
    {
        $bundle = $this->assetManager->getBundle($bundleId);

        if ($path === '') {
            return (string) $bundle->baseUrl;
        }

        $fullPath = rtrim((string) $bundle->basePath, '/').'/'.ltrim($path, '/');

        if (is_file($fullPath)) {
            return $this->withBuildId($this->assetManager->getUrl($bundleId, $path));
        }

        return rtrim((string) $bundle->baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * @return list<string>
     */
    public function registeredBundleIds(): array
    {
        return array_keys($this->registeredBundles);
    }

    /**
     * @return list<string>
     */
    public function registeredJsFiles(): array
    {
        return array_keys($this->registeredJsFiles);
    }

    private function registerBundleAssets(AssetBundle $bundle, string $bundleId): void
    {
        foreach ($bundle->css as $key => $css) {
            $this->registerCssFile($bundleId, is_string($key) ? $key : null, $css, $bundle->cssOptions);
        }

        foreach ($bundle->js as $key => $js) {
            $this->registerJsFile($bundleId, is_string($key) ? $key : null, $js, $bundle->jsOptions, $bundle->jsPosition);
        }

        foreach ($bundle->cssStrings as $key => $css) {
            $this->registerCssString(is_string($key) ? $key : null, $css, $bundle->cssOptions);
        }

        foreach ($bundle->jsStrings as $key => $js) {
            $this->registerJsString(is_string($key) ? $key : null, $js, $bundle->jsOptions, $bundle->jsPosition);
        }

        foreach ($bundle->jsVars as $name => $value) {
            if (is_string($name)) {
                $position = $bundle->jsPosition ?? Position::Head->value;
                $this->registerJsValue("var $name = ".Json::encode($value), $position, $name);

                continue;
            }
            if (! is_array($value)) {
                continue;
            }
            if (! isset($value[0], $value[1])) {
                continue;
            }

            $position = (int) ($value[2] ?? $bundle->jsPosition ?? Position::Head->value);
            $this->registerJsValue('var '.$value[0].' = '.Json::encode($value[1]), $position, (string) $value[0]);
        }
    }

    private function registerCssFile(
        string $bundleId,
        ?string $key,
        array|string $css,
        array $defaultOptions,
    ): void {
        $config = is_array($css) ? $css : [$css];
        $path = (string) $config[0];
        unset($config[0], $config[1]);

        $options = $config + $defaultOptions;
        $url = $this->withBuildId($this->assetManager->getUrl($bundleId, $path));
        $this->htmlStack->cssFile($url, $options, $key ?? $url);
    }

    private function registerJsFile(
        string $bundleId,
        ?string $key,
        array|string $js,
        array $defaultOptions,
        ?int $defaultPosition,
    ): void {
        $config = is_array($js) ? $js : [$js];
        $path = (string) $config[0];
        $position = (int) ($config[1] ?? $defaultPosition ?? Position::BodyEnd->value);
        unset($config[0], $config[1]);

        $options = $config + $defaultOptions + ['position' => $this->positionFor($position)->value];
        $url = $this->withBuildId($this->assetManager->getUrl($bundleId, $path));

        if (isset($this->registeredJsFiles[$url])) {
            return;
        }

        $this->registeredJsFiles[$url] = true;
        $this->htmlStack->jsFile($url, $options, $key ?? $url);
    }

    private function registerCssString(?string $key, array|string $css, array $defaultOptions): void
    {
        $config = is_array($css) ? $css : [$css];
        $value = (string) $config[0];
        unset($config[0], $config[1]);

        $this->htmlStack->css($value, $config + $defaultOptions, $key);
    }

    private function registerJsString(?string $key, array|string $js, array $defaultOptions, ?int $defaultPosition): void
    {
        $config = is_array($js) ? $js : [$js];
        $value = (string) $config[0];
        $position = (int) ($config[1] ?? $defaultPosition ?? Position::BodyEnd->value);
        unset($config[0], $config[1]);

        $options = $config + $defaultOptions;

        if (! empty($options)) {
            $this->htmlStack->script($value, $this->positionFor($position), $options, $key);

            return;
        }

        $this->registerJsValue($value, $position, $key);
    }

    private function registerJsValue(string $js, int $position, ?string $key = null): void
    {
        if (in_array($position, [Position::Head->value, Position::BodyBegin->value, Position::BodyEnd->value], true)) {
            $this->htmlStack->js($js, $this->positionFor($position), $key);

            return;
        }

        $wrapped = match ($position) {
            4 => 'jQuery(() => {'.$js.'});',
            5 => "jQuery(window).on('load', () => {".$js.'});',
            default => $js,
        };

        $this->htmlStack->js($wrapped, Position::BodyEnd, $key);
    }

    private function applyBundleSideEffects(string $bundleId): void
    {
        switch ($bundleId) {
            case 'cp':
                $this->htmlStack->icons($this->cpBootstrap->icons());
                $this->htmlStack->script(
                    'window.Craft = Object.assign({translations: {}}, window.Craft || {}, '.Json::encode(
                        $this->cpBootstrap->craftData(
                            $this->registeredBundleIds(),
                            $this->registeredJsFiles(),
                        ),
                    ).');',
                    Position::Head,
                    key: 'internal-assets:cp-bootstrap',
                );
                break;
            case 'd3':
                $this->htmlStack->js($this->d3BootstrapJs(), Position::BodyBegin, 'internal-assets:d3');
                break;
            case 'money':
                $this->htmlStack->js(
                    'window.Craft.CurrencySubUnits = '.Json::encode($this->currencySubUnits()).';',
                    Position::Head,
                    'internal-assets:money',
                );
                break;
        }
    }

    private function syncClientRegistry(): void
    {
        $bundleIds = $this->registeredBundleIds();
        $jsFiles = $this->registeredJsFiles();

        $this->htmlStack->jsWithVars(
            fn ($bundles, $files) => <<<JS
if (window.Craft) {
  window.Craft.registeredAssetBundles = $bundles;
  window.Craft.registeredJsFiles = $files;
}
if (window.Cp) {
  window.Cp.registeredAssetBundles = $bundles;
  window.Cp.registeredJsFiles = $files;
}
JS,
            [$bundleIds, $jsFiles],
            Position::BodyEnd,
            'internal-assets:registry-sync',
        );
    }

    private function d3BootstrapJs(): string
    {
        $locale = I18N::getFormattingLocale();
        $formatter = Craft::$app->getFormatter();

        $localeDef = [
            'decimal' => $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR),
            'thousands' => $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR),
            'grouping' => [3],
            'currency' => $locale->getCurrencySymbol('USD'),
            'numerals' => [
                $formatter->asDecimal(0, 0),
                $formatter->asDecimal(1, 0),
                $formatter->asDecimal(2, 0),
                $formatter->asDecimal(3, 0),
                $formatter->asDecimal(4, 0),
                $formatter->asDecimal(5, 0),
                $formatter->asDecimal(6, 0),
                $formatter->asDecimal(7, 0),
                $formatter->asDecimal(8, 0),
                $formatter->asDecimal(9, 0),
            ],
            'percent' => $locale->getNumberSymbol(Locale::SYMBOL_PERCENT),
            'minus' => $locale->getNumberSymbol(Locale::SYMBOL_MINUS_SIGN),
            'nan' => $locale->getNumberSymbol(Locale::SYMBOL_NAN),
        ];

        return 'window.d3FormatLocaleDefinition = '.Json::encode($localeDef).";\n".
            'window.d3TimeFormatLocaleDefinition = '.$this->d3TimeFormatDefinition().";\n".
            'window.d3Formats = '.Json::encode(Chart::formats()).';';
    }

    private function d3TimeFormatDefinition(): string
    {
        $locale = I18N::getFormattingLocale();
        $dir = $this->assetRoot.'/d3/dist/d3-time-format/locale';
        $defaultLanguages = [
            'ar' => 'ar-SA',
            'de' => 'de-DE',
            'en' => 'en-US',
            'es' => 'es-ES',
            'fr' => 'fr-FR',
        ];

        $candidates = [$locale->id];
        $language = $locale->getLanguageID();

        if (isset($defaultLanguages[$language])) {
            $candidates[] = $defaultLanguages[$language];
        }

        foreach (glob("$dir/$language*.json") ?: [] as $file) {
            $candidates[] = pathinfo($file, PATHINFO_FILENAME);
        }

        $candidates[] = 'en-US';

        foreach (array_unique($candidates) as $candidate) {
            $path = "$dir/$candidate.json";
            if (is_file($path)) {
                return (string) file_get_contents($path);
            }
        }

        return '{}';
    }

    /**
     * @return array<string, int>
     */
    private function currencySubUnits(): array
    {
        $currencies = new ISOCurrencies;
        $subUnitsByCurrencyCode = [];

        foreach (iterator_to_array($currencies) as $currency) {
            if (! $currency instanceof Currency) {
                continue;
            }

            $subUnitsByCurrencyCode[$currency->getCode()] = $currencies->subunitFor($currency);
        }

        return $subUnitsByCurrencyCode;
    }

    private function positionFor(int $position): Position
    {
        return match ($position) {
            Position::Head->value => Position::Head,
            Position::BodyBegin->value => Position::BodyBegin,
            default => Position::BodyEnd,
        };
    }

    private function withBuildId(string $url): string
    {
        if (Cms::config()->buildId) {
            return Url::urlWithParams($url, ['buildId' => Cms::config()->buildId]);
        }

        return $url;
    }

    private function hashPath(string $path): string
    {
        $dir = is_file($path) ? dirname($path) : $path;

        return sprintf('%x', crc32($dir.'|'.FileHelper::lastModifiedTime($path).'|0'));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function bundleConfigs(): array
    {
        $dist = fn (string $name): string => "{$this->assetRoot}/$name/dist";

        return [
            'admin-table' => [
                'sourcePath' => $dist('admintable'),
                'depends' => ['cp', 'vue'],
                'css' => ['css/app.css'],
                'js' => ['js/app.js'],
            ],
            'animation-blocker' => [
                'sourcePath' => $dist('animationblocker'),
                'js' => ['AnimationBlocker.js'],
            ],
            'auth-method-setup' => [
                'sourcePath' => $dist('authmethodsetup'),
                'depends' => ['cp'],
                'css' => ['css/auth.css'],
                'js' => ['auth.js'],
            ],
            'axios' => [
                'sourcePath' => $dist('axios'),
                'js' => ['axios.js'],
            ],
            'code-mirror' => [
                'sourcePath' => $dist('codemirror'),
                'css' => ['codemirror.css'],
                'js' => ['codemirror.js', 'javascript.js'],
            ],
            'condition-builder' => [
                'sourcePath' => $dist('conditionbuilder'),
                'depends' => ['htmx'],
                'js' => ['ConditionBuilder.js'],
            ],
            'content-window' => [
                'sourcePath' => $dist('iframeresizer'),
                'js' => ['iframeResizer.contentWindow.js'],
            ],
            'cp' => [
                'sourcePath' => $dist('cp'),
                'depends' => [
                    'tailwind-reset',
                    'animation-blocker',
                    'axios',
                    'd3',
                    'garnish',
                    'jquery',
                    'jquery-touch-events',
                    'jquery-ui',
                    'jquery-payment',
                    'datepicker-i18n',
                    'selectize',
                    'velocity',
                    'file-upload',
                    'xregexp',
                    'fabric',
                    'iframe-resizer',
                    'theme',
                    'picturefill',
                ],
                'css' => ['css/cp.css'],
                'js' => ['cp.js'],
            ],
            'craft-support' => [
                'sourcePath' => $dist('craftsupport'),
                'depends' => ['cp'],
                'css' => ['css/CraftSupportWidget.css'],
                'js' => ['CraftSupportWidget.js'],
            ],
            'd3' => [
                'sourcePath' => $dist('d3'),
            ],
            'dashboard' => [
                'sourcePath' => $dist('dashboard'),
                'depends' => ['cp'],
                'css' => ['css/Dashboard.css'],
                'js' => ['Dashboard.js'],
            ],
            'datepicker-i18n' => $this->datepickerI18nBundleConfig(),
            'edit-transform' => [
                'sourcePath' => $dist('edittransform'),
                'depends' => ['cp'],
                'css' => ['css/transforms.css'],
            ],
            'fabric' => [
                'sourcePath' => $dist('fabric'),
                'js' => ['fabric.js'],
            ],
            'feed' => [
                'sourcePath' => $dist('feed'),
                'depends' => ['cp'],
                'js' => ['FeedWidget.js'],
            ],
            'field-settings' => [
                'sourcePath' => $dist('fieldsettings'),
                'depends' => ['cp'],
                'js' => ['fieldsettings.js'],
            ],
            'file-upload' => [
                'sourcePath' => $dist('fileupload'),
                'depends' => ['jquery-ui'],
                'js' => ['jquery.fileupload.js'],
            ],
            'focal-point' => [
                'sourcePath' => $dist('focalpoint'),
                'depends' => ['jquery'],
                'css' => ['css/FocalPoint.css'],
                'js' => ['FocalPoint.js'],
            ],
            'garnish' => [
                'sourcePath' => $dist('garnish'),
                'depends' => ['jquery', 'jquery-touch-events', 'velocity'],
                'js' => ['garnish.js'],
            ],
            'graphiql' => [
                'sourcePath' => $dist('graphiql'),
                'depends' => ['cp'],
                'css' => ['css/graphiql.css'],
                'js' => ['graphiql.js'],
            ],
            'htmx' => [
                'sourcePath' => $dist('htmx'),
                'depends' => ['cp'],
                'js' => ['htmx.min.js'],
            ],
            'iframe-resizer' => [
                'sourcePath' => $dist('iframeresizer'),
                'js' => ['iframeResizer.js'],
            ],
            'inputmask' => [
                'sourcePath' => $dist('inputmask'),
                'js' => ['jquery.inputmask.bundle.js'],
            ],
            'jquery' => [
                'sourcePath' => $dist('jquery'),
                'js' => ['jquery.js'],
            ],
            'jquery-payment' => [
                'sourcePath' => $dist('jquerypayment'),
                'js' => ['jquery.payment.js'],
            ],
            'jquery-touch-events' => [
                'sourcePath' => $dist('jquerytouchevents'),
                'js' => ['jquery.mobile-events.js'],
            ],
            'jquery-ui' => [
                'sourcePath' => $dist('jqueryui'),
                'depends' => ['jquery'],
                'js' => ['jquery-ui.js'],
            ],
            'matrix' => [
                'sourcePath' => $dist('matrix'),
                'depends' => ['cp'],
                'js' => ['MatrixInput.js'],
            ],
            'money' => [
                'sourcePath' => $dist('money'),
                'depends' => ['cp', 'inputmask'],
                'css' => ['css/Money.css'],
                'js' => ['Money.js'],
            ],
            'new-users' => [
                'sourcePath' => $dist('newusers'),
                'depends' => ['cp'],
                'js' => ['NewUsersWidget.js'],
            ],
            'passkey-setup' => [
                'sourcePath' => $dist('passkeysetup'),
                'depends' => ['cp'],
                'js' => ['PasskeySetup.js'],
            ],
            'picturefill' => [
                'sourcePath' => $dist('picturefill'),
                'js' => ['picturefill.js'],
            ],
            'plugin-store' => [
                'sourcePath' => $dist('pluginstore'),
                'depends' => ['cp', 'vue'],
                'css' => ['css/app.css'],
                'js' => ['js/app.js'],
            ],
            'plugins' => [
                'sourcePath' => $dist('plugins'),
                'depends' => ['cp'],
                'css' => ['css/PluginManager.css'],
                'js' => ['PluginManager.js'],
            ],
            'prism' => [
                'sourcePath' => $dist('prismjs'),
                'css' => ['prism.css'],
                'js' => ['prism.js'],
            ],
            'recent-entries' => [
                'sourcePath' => $dist('recententries'),
                'depends' => ['cp'],
                'js' => ['RecentEntriesWidget.js'],
            ],
            'recovery-codes' => [
                'sourcePath' => $dist('recoverycodes'),
                'depends' => ['cp'],
                'js' => ['recoverycodes.js'],
            ],
            'routes' => [
                'sourcePath' => $dist('routes'),
                'depends' => ['cp'],
                'css' => ['css/routes.css'],
                'js' => ['routes.js'],
            ],
            'selectize' => [
                'sourcePath' => $dist('selectize'),
                'css' => ['css/selectize.css'],
                'js' => ['selectize.js'],
            ],
            'table-settings' => [
                'sourcePath' => $dist('tablesettings'),
                'depends' => ['cp'],
                'js' => ['TableFieldSettings.js'],
            ],
            'tailwind-reset' => [
                'sourcePath' => $dist('tailwindreset'),
                'css' => ['css/tailwind_reset.css'],
                'js' => ['tailwind_reset.js'],
            ],
            'theme' => [
                'sourcePath' => $dist('theme'),
                'css' => ['cp.css'],
            ],
            'timepicker' => [
                'sourcePath' => $dist('timepicker'),
                'depends' => ['jquery'],
                'js' => ['jquery.timepicker.js'],
            ],
            'totp' => [
                'sourcePath' => $dist('totp'),
                'depends' => ['cp'],
                'js' => ['totp.js'],
            ],
            'updater' => [
                'sourcePath' => $dist('updater'),
                'depends' => ['cp'],
                'css' => ['css/Updater.css'],
                'js' => ['Updater.js'],
            ],
            'updates-widget' => [
                'sourcePath' => $dist('updateswidget'),
                'depends' => ['cp'],
                'js' => ['UpdatesWidget.js'],
            ],
            'upgrade' => [
                'sourcePath' => $dist('upgrade'),
                'depends' => ['cp'],
                'css' => ['css/UpgradeUtility.css'],
                'js' => ['UpgradeUtility.js'],
            ],
            'user-permissions' => [
                'sourcePath' => $dist('userpermissions'),
                'depends' => ['cp'],
                'css' => ['css/UserPermissions.css'],
                'js' => ['UserPermissions.js'],
            ],
            'user-photo' => [
                'sourcePath' => $dist('userphoto'),
                'depends' => ['cp', 'file-upload'],
                'css' => ['css/UserPhotoInput.css'],
                'js' => ['UserPhotoInput.js'],
            ],
            'velocity' => [
                'sourcePath' => $dist('velocity'),
                'js' => ['velocity.js'],
            ],
            'vue' => [
                'sourcePath' => $dist('vue'),
                'js' => ['vue.js'],
            ],
            'xregexp' => [
                'sourcePath' => $dist('xregexp'),
                'js' => ['xregexp-all.js'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datepickerI18nBundleConfig(): array
    {
        $sourcePath = "{$this->assetRoot}/datepickeri18n/dist";
        $locale = I18N::getLocale();
        $languageId = $locale->getLanguageID();
        $languages = [app()->getLocale(), $languageId];

        if (isset(['cy' => 'cy-GB', 'zh' => 'zh-CN'][$languageId])) {
            $languages[] = ['cy' => 'cy-GB', 'zh' => 'zh-CN'][$languageId];
        }

        foreach (array_unique($languages) as $language) {
            $filename = "$sourcePath/datepicker-$language.js";
            if (is_file($filename)) {
                return [
                    'sourcePath' => $sourcePath,
                    'depends' => ['jquery-ui'],
                    'js' => ["datepicker-$language.js"],
                ];
            }
        }

        return [
            'sourcePath' => $sourcePath,
            'depends' => ['jquery-ui'],
        ];
    }
}
