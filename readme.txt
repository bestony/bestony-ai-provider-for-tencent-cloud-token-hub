=== Bestony AI Provider for Tencent Cloud Token Hub ===
Contributors:      bestony
Tags:              ai, connector, tencent-cloud, tokenhub, chat
Requires at least: 7.0
Tested up to:      7.1
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Bestony AI Provider for Tencent Cloud Token Hub adds Tencent Cloud Token Hub as a provider for the WordPress AI Client.

This is an independent third-party integration. It is not affiliated with or endorsed by Tencent Cloud.

== Description ==

This plugin adds Tencent Cloud Token Hub's OpenAI-compatible Chat Completions API to the WordPress AI Client.

* The model list is fetched live from `GET /models`.
* Only models with `status: online` are exposed.
* Text generation, chat history, tool calling, streaming and structured output are supported.
* Confirmed vision models declare text and image input.
* Image, video and 3D generation models are excluded because this provider implements Chat Completions only.
* A request with more than four messages is rejected before it reaches the API.

== Installation ==

1. Copy the plugin directory to `/wp-content/plugins/bestony-ai-provider-for-tencent-cloud-token-hub/`.
2. Activate the plugin through the Plugins menu.
3. Go to Settings → Connectors, open the Tencent Cloud Token Hub card and paste your API key.

== Configuration ==

The provider ID is `tencentcloud_tokenhub`.

The API key is owned by the AI Client credential registry. You can enter it in Settings → Connectors as `connectors_ai_tencentcloud_tokenhub_api_key`, or define `TENCENTCLOUD_TOKENHUB_API_KEY` as an environment variable or PHP constant supported by the SDK. The provider does not read the Connector option directly.

Optional environment variables or PHP constants:

* `TENCENTCLOUD_TOKENHUB_DEFAULT_MODEL` — model preferred by the AI plugin. Default: `hy4-preview`.
* `TENCENTCLOUD_TOKENHUB_REQUEST_TIMEOUT` — request timeout in seconds. Default: `120`.
* `TENCENTCLOUD_TOKENHUB_CONNECT_TIMEOUT` — connection timeout in seconds. Default: `10`.
* `TENCENTCLOUD_TOKENHUB_STRUCTURED_OUTPUT` — `json_schema` (default), `json_object`, or `none`.

The API base URL is fixed at `https://api.lkeap.cloud.tencent.com/plan/v3`. The key is sent only to this host. The plugin does not provide a custom host setting.

== Model capabilities ==

Token Hub's `/models` response does not include capability details. Unknown online models are therefore treated as text-only chat models. The provider maintains a conservative list of confirmed vision IDs, including the documented DeepSeek V4 Flash Vision, GLM-5V, HY-Vision, Hunyuan vision, and YT-VITA families. The list can be updated when Tencent adds documented vision models.

Image, video, 3D, embedding, speech and other non-chat families are filtered out. No image, video or 3D generation endpoint is implemented.

== Message limit ==

Token Hub rejects a `messages` array longer than four entries. This provider throws an AI Client parameter exception before sending a request when the prompt would exceed that limit.

== External services ==

This independent plugin connects to Tencent Cloud Token Hub, a service provided by Tencent Cloud:

* `GET /models` — fetches the online model list and checks credentials.
* `POST /chat/completions` — sends prompts, conversation history, tools, schemas and image inputs for text generation.

Requests use `Authorization: Bearer <API key>`. See the [Token Hub API documentation](https://cloud.tencent.com/document/product/1823/130078) and [OpenAI Chat Completions field reference](https://cloud.tencent.com/document/product/1823/135872).

This service is provided by Tencent Cloud:

* Terms of service: [https://cloud.tencent.com/document/product/301/1967](https://cloud.tencent.com/document/product/301/1967)
* Privacy policy: [https://cloud.tencent.com/document/product/301/11470](https://cloud.tencent.com/document/product/301/11470)

== Frequently Asked Questions ==

= Does the plugin need a separate settings page? =

No. The Connector card is the only place in WordPress where the API key is entered. Optional behaviour is configured with environment variables or PHP constants.

= Why is a model missing from the list? =

Offline models and known non-chat families are intentionally filtered. A model with an unrecognised ID is treated as text-only, so it remains available for Chat Completions.

= Can I use a configurable Token Hub host? =

No. The endpoint is fixed to the official Token Plan host so the API key is not sent to an arbitrary domain.

= How do I handle a long conversation? =

Keep each request at four messages or fewer. Summarize earlier turns before starting the next request.

== Development ==

`php scripts/selfcheck.php` runs local checks without WordPress or a real key. Add `--sdk=/path/to/wordpress-php-ai-client/src` to run SDK integration checks.

== Changelog ==

= 1.0.0 =
* Initial release with dynamic online model discovery, OpenAI-compatible Chat Completions, structured output, tool calling and conservative vision metadata.
