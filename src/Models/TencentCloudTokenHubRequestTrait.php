<?php

/**
 * Shared Token Hub request creation.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Models;

if (!defined('ABSPATH')) {
    exit;
}

use TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * Builds JSON requests for the OpenAI-compatible Chat Completions endpoint.
 */
trait TencentCloudTokenHubRequestTrait
{
    /**
     * Creates an authenticated request. Authentication is attached by the AI Client registry.
     *
     * @param HttpMethodEnum $method HTTP method.
     * @param string $path Relative endpoint path.
     * @param array<string, string|list<string>> $headers Request headers.
     * @param string|array<string, mixed>|null $data Request body.
     * @return Request Request object.
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['User-Agent'] = TencentCloudTokenHubConfig::getUserAgent();

        return new Request(
            $method,
            TencentCloudTokenHubProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
