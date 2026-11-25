<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use Craft;
use craft\test\TestCase;
use craft\web\View;
use UnitTester;

/**
 * Unit tests for the `{% nav %}` tag.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class NavTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected View $view;

    private const LIST_TEMPLATE = '{% nav item in items %}<li>{{ item.title }}{% ifchildren %}<ul>{% children %}</ul>{% endifchildren %}</li>{% endnav %}';

    public function testNav(): void
    {
        [$a, $b, $c, $d] = $this->items();

        self::assertSame(
            '<li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li>',
            $this->render(self::LIST_TEMPLATE, [$a, $b, $c, $d]),
        );

        // the parent items should have been set
        self::assertSame($b, $c->parent);
        self::assertSame($a, $b->parent);
        self::assertNull($a->parent);
        self::assertNull($d->parent);
    }

    public function testNavClosesDeepLevelsAtEnd(): void
    {
        [$a, $b, $c] = $this->items();

        self::assertSame(
            '<li>A<ul><li>B<ul><li>C</li></ul></li></ul></li>',
            $this->render(self::LIST_TEMPLATE, [$a, $b, $c]),
        );
    }

    public function testNavWithoutChildren(): void
    {
        self::assertSame(
            'ABCD',
            $this->render('{% nav item in items %}{{ item.title }}{% endnav %}', $this->items()),
        );
    }

    public function testNavWithKeys(): void
    {
        self::assertSame(
            '0:A 1:B 2:C 3:D ',
            $this->render('{% nav key, item in items %}{{ key }}:{{ item.title }} {% endnav %}', $this->items()),
        );
    }

    public function testNavVariable(): void
    {
        self::assertSame(
            'A:1: B:2:A C:3:B D:1: ',
            $this->render(
                '{% nav item in items %}{{ item.title }}:{{ nav.level }}:{{ nav.parent ? nav.parent.item.title }} {% endnav %}',
                $this->items(),
            ),
        );
    }

    public function testOuterNavVariableIsRestored(): void
    {
        self::assertSame(
            'ABCD outer',
            $this->render(
                '{% set nav = "outer" %}{% nav item in items %}{{ item.title }}{% endnav %} {{ nav }}',
                $this->items(),
            ),
        );
    }

    /**
     * Returns a structure of items:
     *
     * - A
     *   - B
     *     - C
     * - D
     *
     * @return NavTestItem[]
     */
    private function items(): array
    {
        return [
            new NavTestItem('A', 1, 1, 6),
            new NavTestItem('B', 2, 2, 5),
            new NavTestItem('C', 3, 3, 4),
            new NavTestItem('D', 1, 7, 8),
        ];
    }

    private function render(string $template, array $items): string
    {
        return $this->view->renderString($template, ['items' => $items], View::TEMPLATE_MODE_SITE);
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->view = Craft::$app->getView();
    }
}

/**
 * A structured item for [[NavTest]].
 */
class NavTestItem
{
    public ?NavTestItem $parent = null;

    public function __construct(
        public string $title,
        public int $level,
        public int $lft,
        public int $rgt,
    ) {
    }

    public function setParent(?NavTestItem $parent): void
    {
        $this->parent = $parent;
    }
}
