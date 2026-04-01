<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Twig\Contracts\SafeHtml;
use CraftCms\Cms\Twig\Events\TwigCreated;
use CraftCms\Cms\Twig\Extensions\ArrayTwigExtension;
use CraftCms\Cms\Twig\Extensions\CoreTwigExtension;
use CraftCms\Cms\Twig\Extensions\CpExtension;
use CraftCms\Cms\Twig\Extensions\DateTwigExtension;
use CraftCms\Cms\Twig\Extensions\FeExtension;
use CraftCms\Cms\Twig\Extensions\HtmlTwigExtension;
use CraftCms\Cms\Twig\Extensions\SinglePreloaderExtension;
use CraftCms\Cms\Twig\Extensions\TextTwigExtension;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\HtmlString;
use LogicException;
use Twig\Extension\CoreExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Runtime\EscaperRuntime;

#[Scoped]
class Twig
{
    private ?Environment $cpTwig = null;

    private ?Environment $siteTwig = null;

    private ?array $options = null;

    /** @var array<class-string<ExtensionInterface>, ExtensionInterface> */
    private array $cpExtensions = [];

    /** @var array<class-string<ExtensionInterface>, ExtensionInterface> */
    private array $siteExtensions = [];

    /**
     * Returns the Twig environment for the given (or current) template mode.
     * Lazily creates it on first access.
     */
    public function get(?TemplateMode $mode = null): Environment
    {
        $mode ??= TemplateMode::get();

        return match ($mode) {
            TemplateMode::Cp => $this->cpTwig ??= TemplateMode::with($mode, fn () => $this->create()),
            TemplateMode::Site => $this->siteTwig ??= TemplateMode::with($mode, fn () => $this->create()),
        };
    }

    /**
     * Replaces the cached Twig environment for the given (or current) template mode.
     */
    public function set(Environment $twig, ?TemplateMode $mode = null): void
    {
        $mode ??= TemplateMode::get();

        match ($mode) {
            TemplateMode::Cp => $this->cpTwig = $twig,
            TemplateMode::Site => $this->siteTwig = $twig,
        };
    }

    /**
     * Creates a fresh Twig environment for the current template mode.
     */
    public function create(): Environment
    {
        $twig = app(Environment::class, [
            'options' => $this->getOptions(),
        ]);

        foreach ([
            SafeHtml::class,
            HtmlString::class,
        ] as $class) {
            $twig->getRuntime(EscaperRuntime::class)->addSafeClass($class, ['html']);
        }

        foreach ([
            StringLoaderExtension::class,

            // Craft Twig Extensions
            CoreTwigExtension::class,
            DateTwigExtension::class,
            ArrayTwigExtension::class,
            TextTwigExtension::class,
            HtmlTwigExtension::class,
        ] as $extensionClass) {
            $twig->addExtension(app()->make($extensionClass, ['environment' => $twig]));
        }

        if (TemplateMode::is(TemplateMode::Cp)) {
            $twig->addExtension(new CpExtension);
        } elseif (Cms::isInstalled()) {
            $twig->addExtension(new FeExtension);

            if (Cms::config()->preloadSingles) {
                $twig->addExtension(new SinglePreloaderExtension);
            }
        }

        $registeredExtensions = match (TemplateMode::get()) {
            TemplateMode::Cp => $this->cpExtensions,
            TemplateMode::Site => $this->siteExtensions,
        };

        foreach ($registeredExtensions as $extension) {
            $twig->addExtension($extension);
        }

        // Only register the SandboxExtension if something else hasn't already
        if (! $twig->hasExtension(SandboxExtension::class)) {
            $sandboxConfig = config('craft.twig-sandbox', []);

            $twig->addExtension(new SandboxExtension(new SecurityPolicy(
                $sandboxConfig['allowedTags'] ?? [],
                $sandboxConfig['allowedFilters'] ?? [],
                $sandboxConfig['allowedFunctions'] ?? [],
                $sandboxConfig['allowedMethods'] ?? [],
                $sandboxConfig['allowedProperties'] ?? [],
                $sandboxConfig['allowedClasses'] ?? [],
            )));
        }

        $core = $twig->getExtension(CoreExtension::class);
        $core->setTimezone(Cms::timezone());

        event(new TwigCreated($twig, TemplateMode::get()));

        return $twig;
    }

    /**
     * Registers a Twig extension for the given template mode.
     * Passing null for the `$mode` will register the
     * extension for both Cp and Site.
     */
    public function registerExtension(ExtensionInterface $extension, ?TemplateMode $mode = null): void
    {
        if ($mode === null) {
            $this->registerExtension($extension, TemplateMode::Cp);
            $this->registerExtension($extension, TemplateMode::Site);

            return;
        }

        $class = $extension::class;

        match ($mode) {
            TemplateMode::Cp => $this->cpExtensions[$class] = $extension,
            TemplateMode::Site => $this->siteExtensions[$class] = $extension,
        };

        try {
            match ($mode) {
                TemplateMode::Cp => $this->cpTwig?->addExtension($extension),
                TemplateMode::Site => $this->siteTwig?->addExtension($extension),
            };
        } catch (LogicException) {
            // Invalidate cached environment — it will be rebuilt on next get()
            match ($mode) {
                TemplateMode::Cp => $this->cpTwig = null,
                TemplateMode::Site => $this->siteTwig = null,
            };
        }
    }

    private function getOptions(): array
    {
        if (isset($this->options)) {
            return $this->options;
        }

        $this->options = [
            // See: https://github.com/twigphp/Twig/issues/1951
            'cache' => Path::compiledTemplates(),
            'auto_reload' => true,
            'charset' => 'UTF-8',
        ];

        $generalConfig = Cms::config();

        if ($generalConfig->headlessMode && request()->isSiteRequest()) {
            $this->options['autoescape'] = 'js';
        }

        if (app()->hasDebugModeEnabled()) {
            $this->options['debug'] = true;
            $this->options['strict_variables'] = true;
        }

        return $this->options;
    }
}
