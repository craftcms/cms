<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use Illuminate\Http\JsonResponse;

class GqlResponse extends JsonResponse
{
    /**
     * Create a new GQL response instance.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @param  bool  $json
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0, $json = false)
    {
        parent::__construct($data, $status, $headers, $json);

        $this->header('Content-Type', 'application/graphql-response+json');
    }

    public function noCache(): self
    {
        $this->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', '0');

        return $this;
    }
}
