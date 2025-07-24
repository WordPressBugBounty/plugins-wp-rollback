<?php

/**
 * @package WpRollback\SharedCore\Rollbacks\DTO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WpRollback\SharedCore\Rollbacks\DTO;

use InvalidArgumentException;

/**
 * Data Transfer Object for API rollback requests
 * 
 * @since 1.0.0
 */
class RollbackApiRequestDTO
{
    /**
     * @since 1.0.0
     * @var string
     */
    private string $type;

    /**
     * @since 1.0.0
     * @var string
     */
    private string $slug;

    /**
     * @since 1.0.0
     * @var string
     */
    private string $version;

    /**
     * @since 1.0.0
     * @var string
     */
    private string $name;

    /**
     * @since 1.0.0
     * @var array
     */
    private array $meta = [];

    /**
     * @since 1.0.0
     * @param string $type The asset type (plugin, theme)
     * @param string $slug The asset slug
     * @param string $version The asset version
     * @param string $name The asset name
     * @throws InvalidArgumentException If type is not supported
     */
    public function __construct(string $type, string $slug, string $version, string $name = '')
    {
        // Validate type
        if (!in_array($type, ['plugin', 'theme'], true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid asset type provided: %s. Must be either "plugin" or "theme".',
                    esc_html($type)
                )
            );
        }

        // Validate slug and version
        if (empty($slug)) {
            throw new InvalidArgumentException('Asset slug cannot be empty.');
        }

        if (empty($version)) {
            throw new InvalidArgumentException('Asset version cannot be empty.');
        }

        $this->type = $type;
        $this->slug = $slug;
        $this->version = $version;
        $this->name = $name;
    }

    /**
     * Get the asset type
     * 
     * @since 1.0.0
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the asset slug
     * 
     * @since 1.0.0
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get the asset version
     * 
     * @since 1.0.0
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the asset name
     * 
     * @since 1.0.0
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the asset meta data
     * 
     * @since 1.0.0
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Set the asset meta data
     * 
     * @since 1.0.0
     * @param array $meta
     * @return self
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Create an instance from API request data
     *
     * @since 1.0.0
     * @param array $data The request data
     * @return self
     * @throws InvalidArgumentException If required data is missing
     */
    public static function fromApiRequestData(array $data): self
    {
        // Validate data
        $requiredKeys = ['assetType', 'assetSlug', 'assetVersion'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Missing required key in request data: %s',
                        esc_html($key)
                    )
                );
            }
        }

        $self = new self(
            $data['assetType'],
            $data['assetSlug'],
            $data['assetVersion'],
            $data['assetName'] ?? ''
        );

        // Handle meta data
        if (isset($data['meta']) && is_array($data['meta'])) {
            $self->setMeta($data['meta']);
        }

        return $self;
    }

    /**
     * Get the raw request data
     *
     * @since 1.0.0
     * @return array The request data
     */
    public function getData(): array
    {
        return [
            'assetType' => $this->type,
            'assetSlug' => $this->slug,
            'assetVersion' => $this->version,
            'assetName' => $this->name,
            'meta' => $this->meta,
        ];
    }
} 