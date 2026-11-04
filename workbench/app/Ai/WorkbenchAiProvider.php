<?php

declare(strict_types=1);

namespace Workbench\App\Ai;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Concerns\GeneratesText;
use Laravel\Ai\Providers\Concerns\HasTextGateway;
use Laravel\Ai\Providers\Concerns\StreamsText;
use Laravel\Ai\Providers\Provider;

/**
 * A `laravel/ai` text provider for the workbench that answers from a script instead of a model, so the AI features
 * can be tried without an API key. See {@see WorkbenchTextGateway} for what it answers.
 */
class WorkbenchAiProvider extends Provider implements TextProvider
{
    use GeneratesText;
    use HasTextGateway;
    use StreamsText;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config, protected Dispatcher $events) {}

    public function providerCredentials(): array
    {
        return ['key' => ''];
    }

    public function textGateway(): StepTextGateway
    {
        return $this->textGateway ??= new WorkbenchTextGateway($this->config['delay'] ?? 20);
    }

    public function defaultTextModel(): string
    {
        return 'scripted';
    }

    public function cheapestTextModel(): string
    {
        return 'scripted';
    }

    public function smartestTextModel(): string
    {
        return 'scripted';
    }
}
