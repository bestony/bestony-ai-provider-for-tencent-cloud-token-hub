<?php

/**
 * Conservative Token Hub model classification rules.
 *
 * @package TencentCloudTokenHub\AiProvider
 */

declare(strict_types=1);

namespace TencentCloudTokenHub\AiProvider\Util;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Keeps capability decisions separate from HTTP and WordPress code.
 */
final class TencentCloudTokenHubModelCatalog
{
    /** @var list<string> */
    private const IMAGE_GENERATION_PATTERNS = [
        '#^hy-image(?:-|$)#i',
        '#^vidu-image(?:-|$)#i',
    ];

    /** @var list<string> */
    private const VIDEO_GENERATION_PATTERNS = [
        '#^hy-video(?:-|$)#i',
        '#^pixverse-video(?:-|$)#i',
        '#^kling-video(?:-|$)#i',
        '#^minimax-video(?:-|$)#i',
    ];

    /** @var list<string> */
    private const THREE_D_GENERATION_PATTERNS = [
        '#^hy-3d(?:[-.]|$)#i',
    ];

    /** @var list<string> */
    private const OTHER_NON_CHAT_PATTERNS = [
        '#(?:^|[-/])(?:text|vl)-embedding(?:[-.]|$)#i',
        '#(?:^|[-/])rerank(?:[-.]|$)#i',
        '#(?:^|[-/])(?:tts|asr|speech|audio)(?:[-.]|$)#i',
    ];

    /** @var list<string> */
    private const VISION_PATTERNS = [
        '#^(?:deepseek/)?deepseek-v4-flash-vision(?:-|$)#i',
        '#^glm-5v(?:-|$)#i',
        '#^hy-vision-(?:\d|v)#i',
        '#^hunyuan-t1-vision(?:-|$)#i',
        '#^hunyuan-turbos-vision-video(?:-|$)#i',
        '#^youtu-vita(?:-|$)#i',
    ];

    /**
     * Whether the model is a confirmed vision model.
     *
     * @param string $modelId Model ID.
     * @return bool Whether text and image input are supported.
     */
    public static function supportsImageInput(string $modelId): bool
    {
        foreach (self::VISION_PATTERNS as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alias used by callers that describe the capability rather than the request field.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is a vision model.
     */
    public static function isVisionModel(string $modelId): bool
    {
        return self::supportsImageInput($modelId);
    }

    /**
     * Whether a model is an image generation model.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is not a chat model.
     */
    public static function isImageGenerationModel(string $modelId): bool
    {
        return self::matchesAny($modelId, self::IMAGE_GENERATION_PATTERNS);
    }

    /**
     * Backward-compatible short name for image model classification.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model generates images.
     */
    public static function isImageModel(string $modelId): bool
    {
        return self::isImageGenerationModel($modelId);
    }

    /**
     * Whether a model is a video generation model.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is not a chat model.
     */
    public static function isVideoGenerationModel(string $modelId): bool
    {
        return self::matchesAny($modelId, self::VIDEO_GENERATION_PATTERNS);
    }

    /**
     * Short name for video model classification.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model generates video.
     */
    public static function isVideoModel(string $modelId): bool
    {
        return self::isVideoGenerationModel($modelId);
    }

    /**
     * Whether a model is a 3D generation model.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is not a chat model.
     */
    public static function isThreeDGenerationModel(string $modelId): bool
    {
        return self::matchesAny($modelId, self::THREE_D_GENERATION_PATTERNS);
    }

    /**
     * Short name for 3D model classification.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model generates 3D assets.
     */
    public static function isThreeDModel(string $modelId): bool
    {
        return self::isThreeDGenerationModel($modelId);
    }

    /**
     * Whether the model should be excluded from the chat provider.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is not implemented by this provider.
     */
    public static function isNonChatModel(string $modelId): bool
    {
        return self::isImageGenerationModel($modelId)
            || self::isVideoGenerationModel($modelId)
            || self::isThreeDGenerationModel($modelId)
            || self::matchesAny($modelId, self::OTHER_NON_CHAT_PATTERNS);
    }

    /**
     * Whether the model can be represented as a text generation model.
     *
     * @param string $modelId Model ID.
     * @return bool Whether the model is supported by this provider.
     */
    public static function isTextModel(string $modelId): bool
    {
        return !self::isNonChatModel($modelId);
    }

    /**
     * Natural comparison for stable model pickers.
     *
     * @param string $left First model ID.
     * @param string $right Second model ID.
     * @return int Comparison result.
     */
    public static function compareModelIds(string $left, string $right): int
    {
        return strnatcasecmp($left, $right);
    }

    /**
     * Test a model ID against a pattern list.
     *
     * @param string $modelId Model ID.
     * @param list<string> $patterns Regex patterns.
     * @return bool Whether any pattern matched.
     */
    private static function matchesAny(string $modelId, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $modelId) === 1) {
                return true;
            }
        }

        return false;
    }
}
