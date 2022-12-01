<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\console\generators\BaseGenerator;
use craft\console\generators\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use yii\console\ExitCode;

/**
 * Generates the scaffolding for a new system component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class MakeController extends Controller
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering generator types.
     *
     * Generator types must extend [[BaseGenerator]].
     * ---
     * ```php
     * use craft\console\controllers\MakeController;
     * use craft\events\RegisterComponentTypesEvent;
     * use yii\base\Event;
     *
     * Event::on(MakeController::class,
     *     Fields::EVENT_REGISTER_GENERATOR_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyGenerator::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_GENERATOR_TYPES = 'registerGeneratorTypes';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'generate';

    /**
     * Generates the scaffolding for a new system component.
     *
     * @param string|null $type The type of component to generate.
     */
    public function actionGenerate(?string $type = null): int
    {
        if ($type === null) {
            return $this->run('/help', ['make']);
        }

        if (!$this->interactive) {
            $this->stderr("This command must be run interactively.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        /** @var string|BaseGenerator|null $class */
        $class = ArrayHelper::firstWhere($this->types(), function(string $class) use ($type) {
            /** @var string|BaseGenerator $class */
            return $class::name() === $type;
        });

        if (!$class) {
            $this->stdout("Invalid generator type: $type\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        /** @var BaseGenerator $generator */
        $generator = Craft::createObject([
            'class' => $class,
            'controller' => $this,
        ]);

        return $generator->run();
    }

    /**
     * @inheritdoc
     */
    public function getActionArgsHelp($action): array
    {
        $args = parent::getActionArgsHelp($action);

        if ($action->id === 'generate') {
            $args['type']['comment'] .= " Options include:\n";

            foreach ($this->types() as $type) {
                /** @var string|BaseGenerator $type */
                $args['type']['comment'] .= $this->markdownToAnsi(sprintf("- `%s`: %s\n", $type::name(), $type::description()));
            }
        }

        return $args;
    }

    /**
     * @phpstan-return class-string<BaseGenerator>[]
     */
    private function types(): array
    {
        $types = [
            Plugin::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $types,
        ]);
        $this->trigger(self::EVENT_REGISTER_GENERATOR_TYPES, $event);

        return $event->types;
    }
}
