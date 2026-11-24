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
     * a template with the same name.
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
            $previous = $e->getPrevious();
            [$resolvedPath, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath($path), realpath($resolvedPath));
            self::assertSame(2, $line);
            self::assertSame(View::TEMPLATE_MODE_CP, $view->getTemplateMode());
        } finally {
            unlink($path);
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            $view->setTwig($oldTwig);
        }
    }
}
