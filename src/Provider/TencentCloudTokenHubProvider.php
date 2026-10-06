<?php

/**
 * Tencent Cloud Token Hub provider class file.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Provider;

if (!defined('ABSPATH')) {
    exit;
}

use TencentCloudTokenHub\AiProvider\Metadata\TencentCloudTokenHubModelMetadataDirectory;
use TencentCloudTokenHub\AiProvider\Models\TencentCloudTokenHubTextGenerationModel;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Provider for Tencent Cloud Token Hub's OpenAI-compatible Chat Completions API.
 */
class TencentCloudTokenHubProvider extends AbstractApiProvider
{
    /** @var string Public provider identifier. */
    public const PROVIDER_ID = TencentCloudTokenHubConfig::PROVIDER_ID;

    /**
     * {@inheritDoc}
     *
     * @return string Fixed Token Hub base URL.
     */
    protected static function baseUrl(): string
    {
        return TencentCloudTokenHubConfig::getBaseUrl();
    }

    /**
     * {@inheritDoc}
     *
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @return ModelInterface Text generation model.
     * @throws RuntimeException If the model has no supported text capability.
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        foreach ($modelMetadata->getSupportedCapabilities() as $capability) {
            if (!$capability->isTextGeneration()) {
                continue;
            }

            $model = new TencentCloudTokenHubTextGenerationModel($modelMetadata, $providerMetadata);
            $model->setRequestOptions(TencentCloudTokenHubConfig::createRequestOptions());

            return $model;
        }

        throw new RuntimeException(
            sprintf(
                'The model "%s" has no supported capability for Tencent Cloud Token Hub.',
                $modelMetadata->getId()
            )
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return ProviderMetadata Provider metadata.
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        $args = [
            TencentCloudTokenHubConfig::PROVIDER_ID,
            'Tencent Cloud Token Hub',
            ProviderTypeEnum::cloud(),
            'https://cloud.tencent.com/document/product/1823/130078',
            RequestAuthenticationMethod::apiKey(),
        ];

        if (version_compare(AiClient::VERSION, '1.2.0', '>=')) {
            $description = 'Text and vision generation with Tencent Cloud Token Hub models.';
            $args[] = function_exists('__')
                ? __('Text and vision generation with Tencent Cloud Token Hub models.', 'bestony-ai-provider-for-tencent-cloud-token-hub')
                : $description;
        }

        return new ProviderMetadata(...$args);
    }

    /**
     * {@inheritDoc}
     *
     * @return ProviderAvailabilityInterface Provider availability check.
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(static::modelMetadataDirectory());
    }

    /**
     * {@inheritDoc}
     *
     * @return ModelMetadataDirectoryInterface Model metadata directory.
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new TencentCloudTokenHubModelMetadataDirectory();
    }
}
