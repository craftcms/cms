<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Validation\Rules\LanguageRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\template;

final readonly class SystemMessagesController
{
    use RespondsWithFlash;

    public function __construct(
        private SystemMessages $systemMessages,
        Utilities $utilities,
    ) {
        if (! $utilities->checkAuthorization(Utilities\SystemMessages::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }

        Edition::require(Edition::Pro);
    }

    public function show(Request $request): Response
    {
        $data = $request->validate([
            'key' => ['required'],
            'language' => ['nullable'],
        ]);

        $message = $this->systemMessages->getMessage($data['key'], $data['language'] ?? null);

        return new JsonResponse([
            'body' => template('_components/utilities/SystemMessages/message-modal', [
                'message' => $message,
                'language' => $message?->language,
            ]),
        ]);
    }

    public function store(Request $request): Response
    {
        $message = new SystemMessage($request->validate([
            'key' => ['required', 'string', 'max:150'],
            'language' => ['required', new LanguageRule],
            'subject' => ['required', 'string', 'max:1000'],
            'body' => ['required', 'string'],
        ]));

        $this->systemMessages->saveMessage($message);

        return $this->asSuccess();
    }
}
