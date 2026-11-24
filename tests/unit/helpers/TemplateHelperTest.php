<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\FileHelper;
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
     * Outside Dev Mode, compiled templates don't store their source path, so it must still resolve after the template
     * mode changes, such as when the error page is rendered.
     */
    public function testResolveTemplatePathAndLineFromAnotherTemplateMode(): void
    {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        $dir = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . 'resolve-path-' . uniqid();
        FileHelper::writeToFile("$dir/broken.twig", "Line one\n{{ 1 // 0 }}");
        $twig = new Environment(new TemplateLoader($view), [
            'cache' => Craft::$app->getPath()->getCompiledTemplatesPath(),
            'debug' => false,
        ]);

        try {
            $twig->render(basename($dir) . '/broken');
            self::fail('Expected the template to throw.');
        } catch (RuntimeError $e) {
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);
            $previous = $e->getPrevious();
            [$path, $line] = Template::resolveTemplatePathAndLine($previous->getFile(), $previous->getLine());

            self::assertSame(realpath("$dir/broken.twig"), realpath($path));
            self::assertSame(2, $line);
            self::assertSame(View::TEMPLATE_MODE_CP, $view->getTemplateMode());
        } finally {
            FileHelper::removeDirectory($dir);
        }
    }
}
