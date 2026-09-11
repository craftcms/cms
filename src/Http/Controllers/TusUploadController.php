<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Uploads;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class TusUploadController
{
    public function __construct(private Uploads $uploads) {}

    public function __invoke(Request $request, string $upload): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->noContent(headers: [
                'Tus-Version' => '1.0.0',
                'Tus-Extension' => 'termination,expiration',
                'Tus-Max-Size' => (string) Cms::config()->maxUploadFileSize,
            ]);
        }

        if ($request->isMethod('DELETE')) {
            $this->uploads->cancel($request, $upload);

            return response()->noContent();
        }

        $offset = null;

        if ($request->isMethod('PATCH')) {
            abort_unless($request->header('Content-Type') === 'application/offset+octet-stream', 415);
            $offset = filter_var($request->header('Upload-Offset'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            abort_if($offset === false, 400, 'A valid Upload-Offset header is required.');
        }

        ['session' => $session, 'offset' => $offset] = $this->uploads->tus($request, $upload, $offset);

        return response()->noContent($request->isMethod('HEAD') ? 200 : 204, [
            'Upload-Offset' => (string) $offset,
            'Upload-Length' => (string) $session->size,
            'Upload-Expires' => $session->expiresAt->toRfc7231String(),
            'Cache-Control' => 'no-store',
        ]);
    }
}
