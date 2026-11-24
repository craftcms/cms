<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\Template;
use craft\test\TestCase;
use craft\web\twig\Environment;
use craft\web\twig\TemplateLoader;
use craft\web\View;
use Twig\Error\RuntimeError;
use UnitTester;

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
            $customTemplatesPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'custom-cp-templates';
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
}
