<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use Closure;
use craft\base\Event as YiiEvent;
use craft\events\AuthorizeUploadEvent;
use craft\services\Uploads;
use CraftCms\Cms\Asset\AssetUploads;
use Illuminate\Http\Request;
use Override;

/**
 * Triggers {@see Uploads::EVENT_AUTHORIZE_UPLOAD} whenever an asset upload is authorized.
 *
 * Users pass unless a handler denies them. Guests pass if a handler allows them, or if the application’s
 * {@see AssetUploads::allowGuestUploadsUsing()} callback does and no handler denies them.
 *
 * @internal
 */
class LegacyAssetUploads extends AssetUploads
{
    /** @var Closure(Request, array{folderId: int|null, filename: string, size: int, context: array<string, mixed>}): bool|null */
    private ?Closure $applicationGuestAuthorizer = null;

    private bool $bridgesGuests = false;

    #[Override]
    public function allowGuestUploadsUsing(Closure $authorizer): void
    {
        $this->applicationGuestAuthorizer = $authorizer;
        parent::allowGuestUploadsUsing($this->bridgesGuests ? $this->authorizeGuest(...) : $authorizer);
    }

    #[Override]
    public function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        if (!YiiEvent::hasHandlers(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD)) {
            return parent::authorize($request, $parameters, $filename, $size);
        }

        // Guests are only considered once something could allow them, so route their decision through the event
        if (!$this->bridgesGuests) {
            $this->bridgesGuests = true;
            parent::allowGuestUploadsUsing($this->authorizeGuest(...));
        }

        $parameters = parent::authorize($request, $parameters, $filename, $size);

        if ($request->user()) {
            abort_unless($this->trigger($request, $parameters, $filename, $size, true), 403);
        }

        return $parameters;
    }

    /**
     * @param array{folderId: int|null, filename: string, size: int, context: array<string, mixed>} $file
     */
    private function authorizeGuest(Request $request, array $file): bool
    {
        $isValid = $this->applicationGuestAuthorizer !== null
            && ($this->applicationGuestAuthorizer)($request, $file) === true;

        if (!YiiEvent::hasHandlers(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD)) {
            return $isValid;
        }

        return $this->trigger($request, [
            'operation' => 'upload',
            'folderId' => $file['folderId'],
            'context' => $file['context'],
        ], $file['filename'], $file['size'], $isValid);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function trigger(Request $request, array $parameters, string $filename, int $size, bool $isValid): bool
    {
        $event = new AuthorizeUploadEvent([
            'handler' => AssetUploads::class,
            'filename' => $filename,
            'size' => $size,
            'params' => $parameters,
            'user' => $request->craftUser()?->asElement(),
            'isValid' => $isValid,
        ]);

        YiiEvent::trigger(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD, $event);

        return $event->isValid;
    }
}
