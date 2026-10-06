<?php

/**
 * Tencent Cloud Token Hub model metadata directory.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Metadata;

if (!defined('ABSPATH')) {
    exit;
}

use TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubModelCatalog;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Parses Token Hub's `{id, name, status}` model list.
 *
 * Token Hub does not return capability metadata. Online models are treated as
 * chat models unless the conservative catalog identifies a non-chat family.
 * Only confirmed vision families receive image input metadata.
 *
 * @phpstan-type ModelsResponseData array{data: list<array{id?: mixed, name?: mixed, status?: mixed}>}
 */
class TencentCloudTokenHubModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @param HttpMethodEnum $method HTTP method.
     * @param string $path Relative endpoint path.
     * @param array<string, string|list<string>> $headers Request headers.
     * @param string|array<string, mixed>|null $data Request data.
     * @return Request Request object.
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        $headers['User-Agent'] = TencentCloudTokenHubConfig::getUserAgent();
        $headers['Accept'] = 'application/json';

        return new Request(
            $method,
            TencentCloudTokenHubProvider::url($path),
            $headers,
            $data,
            TencentCloudTokenHubConfig::createRequestOptions()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param Response $response Model list response.
     * @return list<ModelMetadata> Usable online chat model metadata.
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !is_array($responseData['data']) || !$responseData['data']) {
            throw ResponseException::fromMissingData('Tencent Cloud Token Hub', 'data');
        }

        $preferredModelId = TencentCloudTokenHubConfig::getDefaultModelId();
        $models = [];
        $seen = [];

        foreach ($responseData['data'] as $modelData) {
            if (!is_array($modelData)) {
                continue;
            }

            $modelId = isset($modelData['id']) && is_string($modelData['id']) ? trim($modelData['id']) : '';
            if ($modelId === '' || ($modelData['status'] ?? null) !== 'online' || isset($seen[$modelId])) {
                continue;
            }

            if (TencentCloudTokenHubModelCatalog::isNonChatModel($modelId)) {
                continue;
            }

            $seen[$modelId] = true;
            $displayName = isset($modelData['name']) && is_string($modelData['name']) && trim($modelData['name']) !== ''
                ? trim($modelData['name'])
                : $modelId;

            $models[] = new ModelMetadata(
                $modelId,
                $displayName,
                [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
                $this->createSupportedOptions($modelId)
            );
        }

        if (!$models) {
            throw ResponseException::fromMissingData('Tencent Cloud Token Hub', 'data');
        }

        usort(
            $models,
            static function (ModelMetadata $left, ModelMetadata $right) use ($preferredModelId): int {
                if ($preferredModelId !== '') {
                    $leftPreferred = $left->getId() === $preferredModelId ? 0 : 1;
                    $rightPreferred = $right->getId() === $preferredModelId ? 0 : 1;
                    if ($leftPreferred !== $rightPreferred) {
                        return $leftPreferred <=> $rightPreferred;
                    }
                }

                return TencentCloudTokenHubModelCatalog::compareModelIds($left->getId(), $right->getId());
            }
        );

        return $models;
    }

    /**
     * Create supported options for one chat model.
     *
     * @param string $modelId Model ID.
     * @return list<SupportedOption> Supported options.
     */
    private function createSupportedOptions(string $modelId): array
    {
        $inputModalities = [[ModalityEnum::text()]];
        if (TencentCloudTokenHubModelCatalog::supportsImageInput($modelId)) {
            $inputModalities[] = [ModalityEnum::text(), ModalityEnum::image()];
        }

        return [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::presencePenalty()),
            new SupportedOption(OptionEnum::frequencyPenalty()),
            new SupportedOption(OptionEnum::logprobs()),
            new SupportedOption(OptionEnum::topLogprobs()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::functionDeclarations()),
            new SupportedOption(OptionEnum::customOptions()),
            new SupportedOption(OptionEnum::inputModalities(), $inputModalities),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];
    }
}
