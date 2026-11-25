<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\FileHelper;
use craft\helpers\Template;
use craft\test\TestCase;
use craft\web\twig\Environment;
use craft\web\twig\TemplateLoader;
use craft\web\View;
use Twig\Error\RuntimeError;
use UnitTester;
use yii\base\Event;

/**
 * Unit tests for the Template helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.3
 */
class TemplateHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * Outside Dev Mode, compiled templates don't store their source path, so it must still resolve to the right
     * template after the template mode changes, such as when the error page is rendered, even when the other mode has
     * a template with the same name. It must leave the view's template mode and templates path as they were.
     */
    public function testResolveTemplatePathAndLineFromAnotherTemplateMode(): void
    {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $oldTwig = $view->getTwig();
        // The control panel has a login template too
        $path = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . 'login.twig';
        file_put_contents($path, "Line one\n{{ 1 // 0 }}");
        $twig = new Environment(new TemplateLoader($view), [
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'debug' => false,
        ]);
        $view->setTwig($twig);

        try {
            $twig->render('login');
            self::fail('Expected the template to throw.');
        } catch (RuntimeError $e) {
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);
            // A different path that still resolves the control panel's login template
            $customTemplatesPath = Craft::$app->getPath()->getCpTemplatesPath() . DIRECTORY_SEPARATOR . '.';
            $view->setTemplatesPath($customTemplatesPath);
            $previous = $e->getPrevious();
            [$resolvedPath, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath($path), realpath($resolvedPath));
            self::assertSame(2, $line);
            self::assertSame(View::TEMPLATE_MODE_CP, $view->getTemplateMode());
            self::assertSame($customTemplatesPath, $view->getTemplatesPath());
        } finally {
            unlink($path);
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            $view->setTwig($oldTwig);
        }
    }

    /**
     * Unescaped string templates turn off escaping while they render, which changes the class of the templates they
     * include.
     */
    public function testResolveTemplatePathAndLineForTemplateIncludedByUnescapedString(): void
    {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $oldTwig = $view->getTwig();
        $path = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . 'broken-partial.twig';
        file_put_contents($path, "Line one\n{{ 1 // 0 }}");
        $view->setTwig(new Environment(new TemplateLoader($view), [
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'debug' => false,
        ]));

        try {
            $view->renderString('{% include "broken-partial" %}');
            self::fail('Expected the template to throw.');
        } catch (RuntimeError $e) {
            $previous = $e->getPrevious();
            [$resolvedPath, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath($path), realpath($resolvedPath));
            self::assertSame(2, $line);
        } finally {
            unlink($path);
            $view->setTwig($oldTwig);
        }
    }

    public function testResolveTemplatePathAndLineForTemplateUsingTraitOnlyItsModeHas(): void
    {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $oldTwig = $view->getTwig();
        $dir = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . 'resolve-trait-' . uniqid();
        FileHelper::writeToFile("$dir/site-trait.twig", '{% block trait %}{% endblock %}');
        FileHelper::writeToFile("$dir/broken.twig", "{% use '" . basename($dir) . "/site-trait' %}\n{{ 1 // 0 }}");
        $twig = new Environment(new TemplateLoader($view), [
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'debug' => false,
        ]);
        $view->setTwig($twig);

        try {
            $twig->render(basename($dir) . '/broken');
            self::fail('Expected the template to throw.');
        } catch (RuntimeError $e) {
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);
            $previous = $e->getPrevious();
            [$resolvedPath, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath("$dir/broken.twig"), realpath($resolvedPath));
            self::assertSame(2, $line);
        } finally {
            FileHelper::removeDirectory($dir);
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            $view->setTwig($oldTwig);
        }
    }

    /**
     * A template root registered for both template modes resolves a name to the same file in each, which isn't
     * ambiguous.
     */
    public function testResolveTemplatePathAndLineForTemplateRootInBothModes(): void
    {
        $oldView = Craft::$app->getView();
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shared-root-' . uniqid();
        FileHelper::writeToFile("$dir/broken.twig", "Line one\n{{ 1 // 0 }}");
        $registerRoot = function(RegisterTemplateRootsEvent $event) use ($dir) {
            $event->roots['shared-root'] = $dir;
        };
        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, $registerRoot);
        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, $registerRoot);
        $view = new View();
        Craft::$app->set('view', $view);
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $view->setTwig(new Environment(new TemplateLoader($view), [
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'debug' => false,
        ]));

        try {
            // Unescaped string templates compile the templates they include under another class
            $view->renderString('{% include "shared-root/broken" %}');
            self::fail('Expected the template to throw.');
        } catch (RuntimeError $e) {
            $previous = $e->getPrevious();
            [$resolvedPath, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath("$dir/broken.twig"), realpath($resolvedPath));
            self::assertSame(2, $line);
        } finally {
            Event::off(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, $registerRoot);
            Event::off(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, $registerRoot);
            Craft::$app->set('view', $oldView);
            FileHelper::removeDirectory($dir);
        }
    }
}
