<?php

/**
 * Data Transfer Object for rollback item data (plugins/themes)
 *
 * @package WpRollback\SharedCore\Rollbacks\DTO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\DTO;

use WpRollback\SharedCore\Core\Exceptions\Primitives\Exception;
use WpRollback\SharedCore\Core\Exceptions\Primitives\InvalidArgumentException;

/**
 * Class RollbackItemDTO
 *
 * @since 1.0.0
 */
class RollbackItemDTO
{
    /** @var string */
    private $name;
    
    /** @var string */
    private $slug;
    
    /** @var string */
    private $version;
    
    /** @var string */
    private $currentVersion;
    
    /** @var string */
    private $author;
    
    /** @var array */
    private $versions;
    
    /** @var bool */
    private $isPro;
    
    /** @var string|null */
    private $description;
    
    /** @var string|null */
    private $pluginURI;
    
    /** @var string|null */
    private $authorURI;
    
    /** @var string|null */
    private $supportURL;
    
    /** @var string|null */
    private $requiresWP;
    
    /** @var string|null */
    private $requiresPHP;
    
    /** @var array|null */
    private $banners;
    
    /** @var string|null */
    private $changelog;
    
    /** @var string|null */
    private $type;

    /** @var string|null */
    private $testUpToWpVersion;

    /** @var string|null */
    private $screenshotUrl;

    /** @var string|null */
    private $authorAvatar;

    /** @var string|null */
    private $lastUpdated;

    /**
     * Validates and returns a string value or null if invalid
     *
     * @param mixed $value The value to validate
     * @return string|null
     */
    private static function validateStringOrNull($value): ?string
    {
        if (isset($value) && is_string($value) && !empty($value)) {
            return $value;
        }
        return null;
    }

    /**
     * RollbackItemDTO constructor.
     *
     * @param string $name The asset name
     * @param string $slug The asset slug
     * @param string $version The asset version
     * @param string $currentVersion The current installed version
     * @param string $author The asset author
     * @param array $versions Available versions
     * @param bool $isPro Whether this is a pro asset
     * @param string|null $description Asset description
     * @param string|null $pluginURI Plugin/Theme URI
     * @param string|null $authorURI Author URI
     * @param string|null $supportURL Support URL
     * @param string|null $requiresWP Required WordPress version
     * @param string|null $requiresPHP Required PHP version
     * @param array|null $banners Asset banners
     * @param string|null $changelog Asset changelog
     * @param string|null $type Asset type (plugin/theme)
     * @param string|null $testUpToWpVersion WordPress version tested up to
     * @param string|null $screenshotUrl Asset screenshot URL
     * @param string|null $authorAvatar Author avatar URL
     * @param string|null $lastUpdated Last updated date
     * @throws InvalidArgumentException When required fields are missing or invalid
     */
    public function __construct(
        string $name,
        string $slug,
        string $version,
        string $currentVersion,
        string $author,
        array $versions,
        bool $isPro,
        ?string $description = null,
        ?string $pluginURI = null,
        ?string $authorURI = null,
        ?string $supportURL = null,
        ?string $requiresWP = null,
        ?string $requiresPHP = null,
        ?array $banners = null,
        ?string $changelog = null,
        ?string $type = null,
        ?string $testUpToWpVersion = null,
        ?string $screenshotUrl = null,
        ?string $authorAvatar = null,
        ?string $lastUpdated = null
    ) {
        // Validate required fields
        if (empty($name)) {
            throw new InvalidArgumentException('Asset name is required');
        }
        if (empty($slug)) {
            throw new InvalidArgumentException('Asset slug is required');
        }
        if (empty($version)) {
            throw new InvalidArgumentException('Asset version is required');
        }
        if (empty($currentVersion)) {
            throw new InvalidArgumentException('Current version is required');
        }
        if (empty($author)) {
            throw new InvalidArgumentException('Author is required');
        }

        // Validate versions array structure
        foreach ($versions as $ver => $data) {
            if (!is_string($ver)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid version key: %s',
                        esc_html(print_r($ver, true))
                    )
                );
            }
            if (!is_array($data)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Version data must be an array for version: %s',
                        esc_html($ver)
                    )
                );
            }
        }

        $this->name = $name;
        $this->slug = $slug;
        $this->version = $version;
        $this->currentVersion = $currentVersion;
        $this->author = $author;
        $this->versions = $versions;
        $this->isPro = $isPro;
        $this->description = $description;
        $this->pluginURI = $pluginURI;
        $this->authorURI = $authorURI;
        $this->supportURL = $supportURL;
        $this->requiresWP = $requiresWP;
        $this->requiresPHP = $requiresPHP;
        $this->banners = $banners;
        $this->changelog = $changelog;
        $this->type = $type;
        $this->testUpToWpVersion = $testUpToWpVersion;
        $this->screenshotUrl = $screenshotUrl;
        $this->authorAvatar = $authorAvatar;
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * Convert the DTO to an array suitable for REST API response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'currentVersion' => $this->currentVersion,
            'author' => $this->author,
            'versions' => $this->versions,
            'isPro' => $this->isPro,
            'hasVersions' => !empty($this->versions),
            'description' => $this->description,
            'pluginURI' => $this->pluginURI,
            'authorURI' => $this->authorURI,
            'supportURL' => $this->supportURL,
            'requiresWP' => $this->requiresWP,
            'requiresPHP' => $this->requiresPHP,
            'banners' => $this->banners,
            'changelog' => $this->changelog,
            'type' => $this->type,
            'testUpToWpVersion' => $this->testUpToWpVersion,
            'screenshotUrl' => $this->screenshotUrl,
            'authorAvatar' => $this->authorAvatar,
            'lastUpdated' => $this->lastUpdated
        ];
    }

    /**
     * Create from WordPress.org API response
     *
     * @param object $data WordPress.org API response data
     * @param string $currentVersion Current installed version
     * @return self
     * @throws Exception When data is invalid
     */
    public static function fromWpOrg(object $data, string $currentVersion): self
    {
        try {
            // Validate required fields for WP.org plugins
            if (empty($data->name)) {
                throw new InvalidArgumentException('WordPress.org assets require the name field');
            }
            if (empty($data->slug)) {
                throw new InvalidArgumentException('WordPress.org assets require the slug field');
            }

            $versions = [];
            if (isset($data->versions) && (is_array($data->versions) || is_object($data->versions))) {
                foreach ($data->versions as $version => $downloadUrl) {
                    // Skip trunk and RC versions
                    if ('trunk' === $version || strpos($version, '-RC') !== false) {
                        continue;
                    }
                    
                    // Ensure version number is valid
                    if (!preg_match('/^\d+(\.\d+)*$/', $version)) {
                        continue;
                    }

                    $versions[$version] = [
                        'file' => basename($downloadUrl),
                        'downloadUrl' => $downloadUrl,
                        'released' => null,
                    ];
                }
            }

            // Always ensure current version is in the versions array
            if (!isset($versions[$currentVersion])) {
                $versions[$currentVersion] = [
                    'file' => '',
                    'downloadUrl' => '',
                    'released' => null,
                ];
            }

            $author = '';
            if (isset($data->author)) {
                if (is_string($data->author)) {
                    $author = $data->author;
                } elseif (is_object($data->author) && isset($data->author->display_name)) {
                    $author = $data->author->display_name;
                }
            }

            // Format the last_updated date to ISO format for better JavaScript compatibility
            $lastUpdated = null;
            if (isset($data->last_updated) && !empty($data->last_updated)) {
                // WordPress.org API returns dates like "2024-01-15 3:14pm GMT"
                // Convert to ISO format for better JavaScript compatibility
                $timestamp = strtotime($data->last_updated);
                if ($timestamp !== false) {
                    $lastUpdated = gmdate('c', $timestamp); // ISO 8601 format
                }
            }

            // Handle requires and requires_php fields that might be boolean false
            $requiresWP = self::validateStringOrNull($data->requires ?? null);
            $requiresPHP = self::validateStringOrNull($data->requires_php ?? null);

            return new self(
                $data->name,
                $data->slug,
                $data->version ?? $currentVersion,
                $currentVersion,
                wp_strip_all_tags($author),
                $versions,
                false,
                wp_strip_all_tags($data->sections->description ?? null),
                $data->homepage ?? null,
                $data->author_uri ?? null,
                $data->support_url ?? null,
                $requiresWP,
                $requiresPHP,
                isset($data->banners) ? (array) $data->banners : null,
                $data->sections->changelog ?? null,
                'plugin',
                $data->tested ?? null,
                $data->screenshot_url ?? null,
                $data->author->avatar ?? null,
                $lastUpdated
            );
        } catch (InvalidArgumentException $e) {
            // Convert to our custom exception
            throw new Exception(
                sprintf('Error creating DTO from WP.org data: %s', esc_html($e->getMessage())),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Create from premium plugin/theme data
     *
     * @param array $data Premium asset data
     * @param string $currentVersion Current installed version
     * @param string|null $requestSlug Optional slug from the request
     * @return self
     * @throws Exception When data is invalid
     */
    public static function fromPremium(array $data, string $currentVersion, ?string $requestSlug = null): self
    {
        try {
            // Validate required fields for premium plugins/themes
            $requiredFields = ['name', 'author'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Missing required field: %s',
                            esc_html($field)
                        )
                    );
                }
            }

            // For premium plugins/themes, try to get slug from various sources
            $slug = $data['slug'] ?? $requestSlug ?? basename(dirname($data['plugin'] ?? ''));
            if (empty($slug)) {
                throw new InvalidArgumentException('Could not determine slug from any source');
            }

            // Initialize versions array if not set
            $versions = $data['versions'] ?? [];
            if (empty($versions) && !empty($currentVersion)) {
                // If no versions but we have current version, create an entry
                $versions[$currentVersion] = [
                    'file' => $data['plugin'] ?? '',
                    'downloadUrl' => '',
                    'released' => null
                ];
            }

            // Get data from readme if available
            $readme = $data['readme'] ?? [];
            
            // Handle requires and requires_php fields that might be boolean false
            $requiresWP = self::validateStringOrNull($data['requiresWP'] ?? null) 
                ?? self::validateStringOrNull($readme['requires'] ?? null);
            $requiresPHP = self::validateStringOrNull($data['requiresPHP'] ?? null) 
                ?? self::validateStringOrNull($readme['requires_php'] ?? null);
            
            return new self(
                $data['name'],
                $slug,
                $currentVersion,
                $currentVersion,
                $data['author'],
                $versions,
                true,
                $data['description'] ?? $readme['short_description'] ?? null,
                $data['pluginURI'] ?? $readme['pluginURI'] ?? null,
                $data['authorURI'] ?? $readme['author']['author_uri'] ?? null,
                $data['supportURL'] ?? $readme['support_url'] ?? null,
                $requiresWP,
                $requiresPHP,
                $data['banners'] ?? $readme['banners'] ?? null,
                $data['changelog'] ?? ($readme['sections']['changelog'] ?? null),
                $data['type'] ?? 'plugin',
                $data['testUpToWpVersion'] ?? $readme['tested'] ?? null,
                $data['screenshotUrl'] ?? $readme['screenshot_url'] ?? null,
                $data['authorAvatar'] ?? $readme['author']['avatar'] ?? null,
                null // Premium assets don't have last_updated from WordPress.org
            );
        } catch (InvalidArgumentException $e) {
            throw new Exception(
                sprintf('Error creating DTO from premium data: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
} 