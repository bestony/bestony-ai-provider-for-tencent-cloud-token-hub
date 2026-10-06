<?php

/**
 * Tencent Cloud Token Hub provider configuration.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Util;

if (!defined('ABSPATH')) {
    exit;
}

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

/**
 * Reads optional provider configuration without reading connector secrets.
 */
final class TencentCloudTokenHubConfig
{
    /** @var string */
    public const VERSION = '1.0.0';

    /** @var string */
    public const PROVIDER_ID = 'tencentcloud_tokenhub';

    /** @var string */
    public const DEFAULT_BASE_URL = 'https://api.lkeap.cloud.tencent.com/plan/v3';

    /** @var string */
    public const DEFAULT_MODEL = 'hy4-preview';

    /**
     * Resolve an environment variable or PHP constant.
     *
     * The API key itself is intentionally not read here. The AI Client owns
     * credential resolution and exposes only the registered authentication
     * object to this provider.
     *
     * @param string $name Environment variable or constant name.
     * @return string Resolved scalar value, or an empty string.
     */
    public static function env(string $name): string
    {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (defined($name)) {
            $constant = constant($name);
            if (is_scalar($constant)) {
                return (string) $constant;
            }
        }

        return '';
    }

    /**
     * The fixed Token Hub base URL.
     *
     * @return string Base URL without a trailing slash.
     */
    public static function getBaseUrl(): string
    {
        return self::DEFAULT_BASE_URL;
    }

    /**
     * Gets the preferred model ID.
     *
     * @return string Model ID.
     */
    public static function getDefaultModelId(): string
    {
        $model = trim(self::env('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL'));

        return $model === '' ? self::DEFAULT_MODEL : $model;
    }

    /**
     * Gets structured-output request mode.
     *
     * @return string One of json_schema, json_object or none.
     */
    public static function getStructuredOutputMode(): string
    {
        $mode = strtolower(trim(self::env('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT')));

        return in_array($mode, ['json_schema', 'json_object', 'none'], true) ? $mode : 'json_schema';
    }

    /**
     * Gets the HTTP request timeout.
     *
     * @return float Timeout in seconds.
     */
    public static function getRequestTimeout(): float
    {
        $configured = self::env('TENCENTCLOUD_TOKENHUB_REQUEST_TIMEOUT');
        $timeout = $configured === '' ? 120.0 : (float) $configured;

        return $timeout > 0 ? $timeout : 120.0;
    }

    /**
     * Gets the connection timeout.
     *
     * @return float Timeout in seconds.
     */
    public static function getConnectTimeout(): float
    {
        $configured = self::env('TENCENTCLOUD_TOKENHUB_CONNECT_TIMEOUT');
        $timeout = $configured === '' ? 10.0 : (float) $configured;

        return $timeout > 0 ? $timeout : 10.0;
    }

    /**
     * Whether the AI Client registry has a Token Hub credential.
     *
     * @return bool Whether authentication is registered.
     */
    public static function hasCredentials(): bool
    {
        if (!class_exists(AiClient::class)) {
            return false;
        }

        $registry = AiClient::defaultRegistry();
        if (!$registry->hasProvider(self::PROVIDER_ID)) {
            return false;
        }

        return $registry->getProviderRequestAuthentication(self::PROVIDER_ID) !== null;
    }

    /**
     * Build request options used by model discovery and generation.
     *
     * @return RequestOptions Request options.
     */
    public static function createRequestOptions(): RequestOptions
    {
        $options = new RequestOptions();
        $options->setTimeout(self::getRequestTimeout());
        $options->setConnectTimeout(self::getConnectTimeout());

        return $options;
    }

    /**
     * Gets the provider User-Agent value.
     *
     * @return string User-Agent header value.
     */
    public static function getUserAgent(): string
    {
        return 'bestony-ai-provider-for-tencent-cloud-token-hub/' . self::VERSION;
    }
}
