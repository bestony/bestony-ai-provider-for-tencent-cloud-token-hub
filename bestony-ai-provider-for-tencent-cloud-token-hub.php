<?php
/**
 * Plugin Name:       Bestony AI Provider for Tencent Cloud Token Hub
 * Plugin URI:        https://github.com/bestony/bestony-ai-provider-for-tencent-cloud-token-hub
 * Description:       Bestony's independent Tencent Cloud Token Hub provider for the WordPress AI Client.
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Bestony
 * Author URI:        https://github.com/bestony
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       bestony-ai-provider-for-tencent-cloud-token-hub
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider;

use TencentCloudTokenHub\AiProvider\Provider\TencentCloudTokenHubProvider;
use TencentCloudTokenHub\AiProvider\Util\TencentCloudTokenHubConfig;
use WordPress\AiClient\AiClient;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Register the provider before the Connectors screen builds its cards.
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();
    if ($registry->hasProvider(TencentCloudTokenHubProvider::class)) {
        return;
    }

    $registry->registerProvider(TencentCloudTokenHubProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);

/**
 * Put the configured Token Hub model first while retaining other providers' preferences.
 *
 * @param mixed $preferredModels List of [provider ID, model ID] tuples.
 * @return array<int, array{string, string}> Filtered preference tuples.
 */
function prefer_tokenhub_models($preferredModels): array
{
    $preferredList = is_array($preferredModels) ? array_values($preferredModels) : [];
    if (!TencentCloudTokenHubConfig::hasCredentials()) {
        return $preferredList;
    }

    $defaultModel = TencentCloudTokenHubConfig::getDefaultModelId();
    if ($defaultModel === '') {
        return $preferredList;
    }

    $preferred = [[TencentCloudTokenHubConfig::PROVIDER_ID, $defaultModel]];
    foreach ($preferredList as $entry) {
        if (!is_array($entry) || count($entry) < 2) {
            continue;
        }

        $entry = array_values($entry);
        if (!is_string($entry[0]) || !is_string($entry[1])) {
            continue;
        }

        if ($entry[0] === TencentCloudTokenHubConfig::PROVIDER_ID) {
            continue;
        }

        $preferred[] = [$entry[0], $entry[1]];
    }

    return $preferred;
}

add_filter('wpai_preferred_text_models', __NAMESPACE__ . '\\prefer_tokenhub_models');
add_filter('wpai_preferred_vision_models', __NAMESPACE__ . '\\prefer_tokenhub_models');
