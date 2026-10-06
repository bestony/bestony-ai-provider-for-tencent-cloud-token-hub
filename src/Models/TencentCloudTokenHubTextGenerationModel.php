<?php

/**
 * Tencent Cloud Token Hub text generation model.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Models;

if (!defined('ABSPATH')) {
    exit;
}

use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Uses the SDK's OpenAI-compatible message, tool, streaming and response parsing logic.
 */
class TencentCloudTokenHubTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    use TencentCloudTokenHubRequestTrait;

    /**
     * {@inheritDoc}
     *
     * Token Hub rejects requests whose final `messages` array has more than four entries.
     *
     * @param list<\WordPress\AiClient\Messages\DTO\Message> $prompt Prompt messages.
     * @return array<string, mixed> Request parameters.
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        if (count($prompt) > 4) {
            throw new InvalidArgumentException('Tencent Cloud Token Hub supports at most 4 prompt messages.');
        }

        $params = parent::prepareGenerateTextParams($prompt);

        if (isset($params['messages']) && is_array($params['messages']) && count($params['messages']) > 4) {
            throw new InvalidArgumentException('Tencent Cloud Token Hub supports at most 4 messages per request.');
        }

        // These OpenAI fields are explicitly unsupported by Token Hub.
        unset($params['top_k'], $params['repetition_penalty'], $params['modalities'], $params['audio']);

        // An empty array is the internal signal for `none`; do not send it as JSON.
        if (isset($params['response_format']) && $params['response_format'] === []) {
            unset($params['response_format']);
        }

        return $params;
    }

    /**
     * {@inheritDoc}
     *
     * Token Hub requires the standard named JSON schema wrapper.
     *
     * @param array<string, mixed>|null $outputSchema Output schema, or null for JSON object mode.
     * @return array<string, mixed> Response format, or an empty array for no response_format.
     */
    protected function prepareResponseFormatParam(?array $outputSchema): array
    {
        $mode = TencentCloudTokenHubConfig::getStructuredOutputMode();

        if ($mode === 'none') {
            return [];
        }

        if ($mode === 'json_object' || !is_array($outputSchema)) {
            return ['type' => 'json_object'];
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'tencentcloud_tokenhub_response',
                'schema' => $outputSchema,
            ],
        ];
    }
}
