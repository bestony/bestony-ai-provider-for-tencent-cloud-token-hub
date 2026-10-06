# Bestony AI Provider for Tencent Cloud Token Hub

Bestony AI Provider for Tencent Cloud Token Hub adds Tencent Cloud Token Hub as an OpenAI-compatible provider for the [WordPress AI Client](https://github.com/WordPress/php-ai-client).

This is an independent third-party integration. It is not affiliated with or endorsed by Tencent Cloud.

## What it does

- Discovers models from `GET /models` and keeps only entries whose `status` is `online`.
- Sends text, chat history, tool calls, streaming requests, and structured output to `POST /chat/completions`.
- Declares image input only for the maintained list of confirmed Token Hub vision models.
- Excludes image, video, 3D generation, embedding, speech, and other non-chat models because this plugin implements Chat Completions only.
- Uses the AI Client credential registry for the Connector key. The key never passes through this plugin's configuration code.

The provider ID is `tencentcloud_tokenhub`.

## Requirements

- WordPress 7.0 or newer with the PHP AI Client SDK.
- PHP 7.4 or newer.
- A Tencent Cloud Token Hub API key.

## Install

Copy this directory to `wp-content/plugins/bestony-ai-provider-for-tencent-cloud-token-hub/`, activate it, then open **Settings → Connectors**. Open **Tencent Cloud Token Hub** and enter the API key.

## Configuration

Credentials can be supplied through the AI Client Connector (`connectors_ai_tencentcloud_tokenhub_api_key`) or through the SDK-supported `TENCENTCLOUD_TOKENHUB_API_KEY` environment variable or PHP constant. This provider checks the AI Client registry and does not read the option directly.

| Setting | Default | Purpose |
| --- | --- | --- |
| `TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL` | `hy4-preview` | Model placed first in text and vision model preferences |
| `TENCENTCLOUD_TOKENHUB_REQUEST_TIMEOUT` | `120` | Request timeout in seconds |
| `TENCENTCLOUD_TOKENHUB_CONNECT_TIMEOUT` | `10` | Connection timeout in seconds |
| `TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT` | `json_schema` | `json_schema`, `json_object`, or `none` |

The API base URL is fixed at `https://api.lkeap.cloud.tencent.com/plan/v3`. It is used for both model discovery and generation so the API key is not sent to a configurable host.

Token Hub accepts at most four messages in one Chat Completions request. The provider rejects a longer prompt before making a network request. Split long conversations before calling the AI Client.

The `/models` response does not include capability metadata. Unknown online models are exposed as text-only chat models. The catalog contains only confirmed vision rules; update it when Tencent documents a new vision model. Image, video, and 3D generation models remain visible to the upstream service but are excluded from this text-only provider.

## Model preferences

The provider adds `hy4-preview` to the front of the standard preference lists when a credential is configured. Other provider entries remain in their original order:

```php
add_filter('wpai_preferred_text_models', function ($models) {
    array_unshift($models, ['tencentcloud_tokenhub', 'hy4-preview']);
    return $models;
});
```

## Endpoints and data

The plugin calls:

- `GET https://api.lkeap.cloud.tencent.com/plan/v3/models` to discover online models.
- `POST https://api.lkeap.cloud.tencent.com/plan/v3/chat/completions` to generate text.

Both requests use `Authorization: Bearer <API key>`. Prompts, conversation history, tool definitions, schemas, and attached image data supplied by the calling plugin are sent to Token Hub when a generation is requested.

## External services

This plugin connects to Tencent Cloud Token Hub to discover online models and generate text. It sends the API key in the `Authorization` header, and sends prompts, conversation history, tool definitions, output schemas, and attached image data when a generation request is made. Requests are sent only to `https://api.lkeap.cloud.tencent.com/plan/v3`.

Tencent Cloud provides this service. See the [Tencent Cloud Terms of Service](https://cloud.tencent.com/document/product/301/1967) and [Tencent Cloud Privacy Policy](https://cloud.tencent.com/document/product/301/11470).

## Development

```text
php scripts/selfcheck.php
php scripts/selfcheck.php --sdk=/path/to/wordpress-php-ai-client/src
```

The self-check never needs a real API key and never calls Tencent Cloud.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
