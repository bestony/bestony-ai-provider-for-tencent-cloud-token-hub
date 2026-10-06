<?php

/**
 * Local checks for the Tencent Cloud Token Hub provider.
 *
 * Usage:
 *   php scripts/selfcheck.php
 *   php scripts/selfcheck.php --sdk=/path/to/wordpress-php-ai-client/src
 *
 * No check contacts Tencent Cloud or requires a real API key.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

$root = dirname(__DIR__);
// Dev-only CLI harness: exit on direct web access; define ABSPATH for the plugin files' guards.
if (!defined('ABSPATH')) {
    if ('cli' !== PHP_SAPI) {
        exit; // Exit if accessed directly.
    }
    define('ABSPATH', $root . '/');
}

require $root . '/src/autoload.php';

use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubModelCatalog;

$checks = 0;
$failures = 0;

/**
 * Record one assertion.
 *
 * @param bool $condition Assertion result.
 * @param string $description Assertion description.
 * @return void
 */
function check(bool $condition, string $description): void
{
    global $checks, $failures;
    $checks++;

    if ($condition) {
        fwrite(STDOUT, "ok    {$description}\n");
        return;
    }

    $failures++;
    fwrite(STDERR, "FAIL  {$description}\n");
}

// Fixed endpoint and defaults.
check(
    TencentCloudTokenHubConfig::getBaseUrl() === 'https://api.lkeap.cloud.tencent.com/plan/v3',
    'the Token Hub base URL is fixed'
);
putenv('TENCENTCLOUD_TOKENHUB_BASE_URL=https://evil.example.com');
check(
    TencentCloudTokenHubConfig::getBaseUrl() === 'https://api.lkeap.cloud.tencent.com/plan/v3',
    'an arbitrary base URL cannot redirect API-key requests'
);
putenv('TENCENTCLOUD_TOKENHUB_BASE_URL');

$previousModel = getenv('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL');
putenv('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL');
check(TencentCloudTokenHubConfig::getDefaultModelId() === 'hy4-preview', 'default model is hy4-preview');
putenv('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL=hy3');
check(TencentCloudTokenHubConfig::getDefaultModelId() === 'hy3', 'default model can be overridden');
if ($previousModel === false) {
    putenv('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL');
} else {
    putenv('TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL=' . $previousModel);
}

$previousStructuredOutput = getenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT');
putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT');
check(TencentCloudTokenHubConfig::getStructuredOutputMode() === 'json_schema', 'structured output defaults to json_schema');
putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT=none');
check(TencentCloudTokenHubConfig::getStructuredOutputMode() === 'none', 'structured output supports none mode');
if ($previousStructuredOutput === false) {
    putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT');
} else {
    putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT=' . $previousStructuredOutput);
}

check(
    TencentCloudTokenHubConfig::getUserAgent() === 'bestony-ai-provider-for-tencent-cloud-token-hub/1.0.0',
    'user agent identifies the provider'
);
check(TencentCloudTokenHubConfig::getRequestTimeout() >= 60.0, 'request timeout is suitable for model generation');
check(TencentCloudTokenHubConfig::getConnectTimeout() >= 5.0, 'connect timeout is non-trivial');

// Conservative model catalog.
check(
    TencentCloudTokenHubModelCatalog::isTextModel('hy4-preview'),
    'hy4-preview is a text chat model'
);
check(
    TencentCloudTokenHubModelCatalog::supportsImageInput('deepseek/deepseek-v4-flash-vision-exp'),
    'the confirmed DeepSeek vision model accepts image input'
);
check(
    TencentCloudTokenHubModelCatalog::supportsImageInput('glm-5v-turbo'),
    'the confirmed GLM-5V model accepts image input'
);
check(
    TencentCloudTokenHubModelCatalog::isImageGenerationModel('hy-image-v3'),
    'image generation models are excluded'
);
check(
    TencentCloudTokenHubModelCatalog::isVideoGenerationModel('hy-video-v1.5'),
    'video generation models are excluded'
);
check(
    TencentCloudTokenHubModelCatalog::isThreeDGenerationModel('hy-3d-3.1'),
    '3D generation models are excluded'
);
check(
    TencentCloudTokenHubModelCatalog::isNonChatModel('kinfra-text-embedding-0.6b'),
    'embedding models are excluded'
);
check(
    !TencentCloudTokenHubModelCatalog::isNonChatModel('deepseek-v4-pro'),
    'an unknown online language model remains available'
);
check(
    TencentCloudTokenHubModelCatalog::compareModelIds('model-2', 'model-10') < 0,
    'model IDs use natural ordering'
);

// SDK-dependent checks are opt-in so the provider logic can be checked without WordPress.
$sdkPath = null;
foreach ($argv as $argument) {
    if (strpos($argument, '--sdk=') === 0) {
        $sdkPath = substr($argument, 6);
    }
}

if ($sdkPath !== null && is_file($sdkPath . '/polyfills.php')) {
    require $sdkPath . '/polyfills.php';
    spl_autoload_register(static function (string $class) use ($sdkPath): void {
        $prefix = 'WordPress\\AiClient\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $file = $sdkPath . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });

    use_tokenhub_sdk_checks();
} else {
    check(!TencentCloudTokenHubConfig::hasCredentials(), 'without the AI Client there are no credentials');
    fwrite(STDOUT, "skip  SDK-dependent checks (pass --sdk=<path to php-ai-client/src> to run them)\n");
}

/**
 * Run checks that require the PHP AI Client SDK.
 *
 * @return void
 */
function use_tokenhub_sdk_checks(): void
{
    if (!function_exists('add_action')) {
        function add_action(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): void
        {
        }
    }
    if (!function_exists('add_filter')) {
        function add_filter(string $hook, $callback, int $priority = 10, int $acceptedArgs = 1): void
        {
        }
    }

    require_once dirname(__DIR__) . '/bestony-ai-provider-for-tencent-cloud-token-hub.php';

    $response = new \WordPress\AiClient\Providers\Http\DTO\Response(
        200,
        [],
        json_encode([
            'object' => 'list',
            'data' => [
                ['id' => 'hy4-preview', 'name' => 'Hy4 preview', 'status' => 'online'],
                ['id' => 'deepseek/deepseek-v4-flash-vision-exp', 'name' => 'DeepSeek vision', 'status' => 'online'],
                ['id' => 'hy-image-v3', 'name' => 'Hy Image', 'status' => 'online'],
                ['id' => 'hy-video-v1.5', 'name' => 'Hy Video', 'status' => 'online'],
                ['id' => 'hy-3d-3.1', 'name' => 'HY 3D', 'status' => 'online'],
                ['id' => 'offline-model', 'name' => 'Offline', 'status' => 'pre-offline'],
                ['id' => '', 'name' => 'Malformed', 'status' => 'online'],
            ],
        ])
    );

    $parser = new class extends \TencentCloudTokenHub\AiProvider\Metadata\TencentCloudTokenHubModelMetadataDirectory {
        /**
         * @param \WordPress\AiClient\Providers\Http\DTO\Response $response Response.
         * @return list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> Models.
         */
        public function parse(\WordPress\AiClient\Providers\Http\DTO\Response $response): array
        {
            return $this->parseResponseToModelMetadataList($response);
        }
    };

    $models = $parser->parse($response);
    check(count($models) === 2, 'online chat models are parsed while non-chat and offline models are filtered');
    check($models[0]->getId() === 'hy4-preview', 'the preferred model is sorted first');

    $byId = [];
    foreach ($models as $model) {
        $byId[$model->getId()] = $model;
    }

    $visionInputModalities = null;
    foreach ($byId['deepseek/deepseek-v4-flash-vision-exp']->getSupportedOptions() as $option) {
        if ($option->getName()->isInputModalities()) {
            $visionInputModalities = $option->getSupportedValues();
        }
    }
    check(is_array($visionInputModalities) && count($visionInputModalities) === 2, 'vision metadata declares text and image input');

    $textOptionNames = array_map(
        static fn($option): string => $option->getName()->value,
        $byId['hy4-preview']->getSupportedOptions()
    );
    check(in_array('outputSchema', $textOptionNames, true), 'chat models declare structured output');
    check(in_array('functionDeclarations', $textOptionNames, true), 'chat models declare function declarations');
    check(!in_array('topK', $textOptionNames, true), 'unsupported top_k is not declared');

    $directoryRequest = new class extends \TencentCloudTokenHub\AiProvider\Metadata\TencentCloudTokenHubModelMetadataDirectory {
        /**
         * @return \WordPress\AiClient\Providers\Http\DTO\Request Model-list request.
         */
        public function makeRequest(): \WordPress\AiClient\Providers\Http\DTO\Request
        {
            return $this->createRequest(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum::GET(), 'models');
        }
    };
    $request = $directoryRequest->makeRequest();
    $authenticated = (new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('test-key'))
        ->authenticateRequest($request);
    check($authenticated->getUri() === 'https://api.lkeap.cloud.tencent.com/plan/v3/models', 'model discovery uses GET /models');
    check(
        isset($authenticated->getHeaders()['Authorization'][0])
            && $authenticated->getHeaders()['Authorization'][0] === 'Bearer test-key',
        'model discovery uses Bearer authentication'
    );
    check(
        ($request->toArray()['options']['timeout'] ?? null) >= 60.0
            && ($request->toArray()['options']['connectTimeout'] ?? null) >= 5.0,
        'model discovery carries long request and connection timeouts'
    );

    $transporter = new class implements \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface {
        /** @var \WordPress\AiClient\Providers\Http\DTO\Request|null */
        public $request;

        /** @var array<string, mixed> */
        public $body = [];

        /** @var array<string, mixed> */
        public $queue = [];

        public function send(
            \WordPress\AiClient\Providers\Http\DTO\Request $request,
            ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null
        ): \WordPress\AiClient\Providers\Http\DTO\Response {
            $this->request = $request;
            $this->body = (array) $request->getData();

            return new \WordPress\AiClient\Providers\Http\DTO\Response(200, [], json_encode($this->queue));
        }
    };

    // Credential state comes from the AI Client registry, not from a Connector option lookup.
    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    $registry->setHttpTransporter($transporter);
    $previousApiKey = getenv('TENCENTCLOUD_TOKENHUB_API_KEY');
    putenv('TENCENTCLOUD_TOKENHUB_API_KEY=selfcheck-key');
    if (!$registry->hasProvider(\TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider::class)) {
        $registry->registerProvider(\TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider::class);
    }
    check(TencentCloudTokenHubConfig::hasCredentials(), 'the registry resolves the provider API key environment variable');
    if ($previousApiKey === false) {
        putenv('TENCENTCLOUD_TOKENHUB_API_KEY');
    } else {
        putenv('TENCENTCLOUD_TOKENHUB_API_KEY=' . $previousApiKey);
    }
    $registry->setProviderRequestAuthentication(
        TencentCloudTokenHubConfig::PROVIDER_ID,
        new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('test-key')
    );
    check(TencentCloudTokenHubConfig::hasCredentials(), 'the credential registry reports a configured API key');

    $preferences = \TencentCloudTokenHub\AiProvider\prefer_tokenhub_models([
        ['openai', 'gpt-5'],
        ['tencentcloud_tokenhub', 'old-model'],
        ['google', 'gemini-3'],
    ]);
    check(
        $preferences[0] === ['tencentcloud_tokenhub', 'hy4-preview']
            && $preferences[1] === ['openai', 'gpt-5']
            && $preferences[2] === ['google', 'gemini-3'],
        'model preference filters pin Token Hub and preserve other providers'
    );

    $model = new \TencentCloudTokenHub\AiProvider\Models\TencentCloudTokenHubTextGenerationModel(
        $byId['hy4-preview'],
        \TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider::metadata()
    );
    $model->setHttpTransporter($transporter);
    $model->setRequestAuthentication(new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('test-key'));
    $model->setRequestOptions(TencentCloudTokenHubConfig::createRequestOptions());
    $model->setConfig(\WordPress\AiClient\Providers\Models\DTO\ModelConfig::fromArray([
        'outputMimeType' => 'application/json',
        'outputSchema' => ['type' => 'object', 'properties' => ['answer' => ['type' => 'string']]],
    ]));
    $transporter->queue = [
        'id' => 'chatcmpl-test',
        'choices' => [[
            'message' => ['role' => 'assistant', 'content' => '{"answer":"ok"}'],
            'finish_reason' => 'stop',
        ]],
        'usage' => ['prompt_tokens' => 2, 'completion_tokens' => 2, 'total_tokens' => 4],
    ];
    $model->generateTextResult([
        new \WordPress\AiClient\Messages\DTO\Message(
            \WordPress\AiClient\Messages\Enums\MessageRoleEnum::user(),
            [new \WordPress\AiClient\Messages\DTO\MessagePart('hello')]
        ),
    ]);

    check($transporter->request->getUri() === 'https://api.lkeap.cloud.tencent.com/plan/v3/chat/completions', 'chat uses POST /chat/completions');
    check(($transporter->body['response_format']['type'] ?? null) === 'json_schema', 'structured output uses json_schema');
    check(
        ($transporter->body['response_format']['json_schema']['name'] ?? null) === 'tencentcloud_tokenhub_response',
        'structured output uses a named schema wrapper'
    );
    check(!isset($transporter->body['top_k']) && !isset($transporter->body['repetition_penalty']), 'unsupported sampling fields are omitted');
    check($transporter->request->getHeaders()['Content-Type'][0] === 'application/json', 'chat sets JSON content type');
    check($transporter->request->getHeaders()['User-Agent'][0] === TencentCloudTokenHubConfig::getUserAgent(), 'chat sets provider user agent');

    $previousStructuredOutput = getenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT');
    putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT=none');
    $model->generateTextResult([
        new \WordPress\AiClient\Messages\DTO\Message(
            \WordPress\AiClient\Messages\Enums\MessageRoleEnum::user(),
            [new \WordPress\AiClient\Messages\DTO\MessagePart('plain')]
        ),
    ]);
    check(!array_key_exists('response_format', $transporter->body), 'none mode omits response_format entirely');
    if ($previousStructuredOutput === false) {
        putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT');
    } else {
        putenv('TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT=' . $previousStructuredOutput);
    }

    $tooManyMessages = [];
    for ($index = 0; $index < 5; $index++) {
        $tooManyMessages[] = new \WordPress\AiClient\Messages\DTO\Message(
            \WordPress\AiClient\Messages\Enums\MessageRoleEnum::user(),
            [new \WordPress\AiClient\Messages\DTO\MessagePart('message')]
        );
    }

    $rejected = false;
    try {
        $model->generateTextResult($tooManyMessages);
    } catch (\WordPress\AiClient\Common\Exception\InvalidArgumentException $exception) {
        $rejected = true;
    }
    check($rejected, 'more than four prompt messages fail before transport');
}

fwrite(STDOUT, sprintf("\n%d checks, %d failure(s)\n", $checks, $failures));
exit($failures === 0 ? 0 : 1);
