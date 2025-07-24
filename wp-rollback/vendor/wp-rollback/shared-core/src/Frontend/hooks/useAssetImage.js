/* global Image */
import { useState, useEffect, useMemo } from '@wordpress/element';

// Cache configuration
const CACHE_EXPIRY = 1000 * 60 * 60; // 1 hour in milliseconds
const imageCache = new Map();
const cacheTimestamps = new Map();
const noImageCache = new Set();

const isCacheValid = key => {
    if ( ! cacheTimestamps.has( key ) ) {
        return false;
    }
    const timestamp = cacheTimestamps.get( key );
    return Date.now() - timestamp < CACHE_EXPIRY;
};

const ASSET_TYPES = {
    PLUGIN: 'plugin',
    THEME: 'theme',
};

/**
 * Custom hook to fetch and validate asset images
 * @param {string}      identifier  - Slug or full URL of the asset
 * @param {string}      type        - Type of asset (plugin or theme)
 * @param {string|null} assetPath   - Optional local asset path
 * @param {boolean}     isDirectUrl - Whether the identifier is a direct URL
 */
const useAssetImage = ( identifier, type = ASSET_TYPES.PLUGIN, assetPath = null, isDirectUrl = false ) => {
    const [ imageUrl, setImageUrl ] = useState( null );

    const checkImage = useMemo(
        () => url => {
            return new Promise( resolve => {
                const img = new Image();
                img.onload = () => resolve( true );
                img.onerror = () => resolve( false );
                img.src = url;
            } );
        },
        []
    );

    useEffect( () => {
        const checkAndSetImage = async () => {
            if ( ! identifier ) {
                return;
            }

            const cacheKey = isDirectUrl ? identifier : `${ type }-${ identifier }`;

            // Check regular cache first
            if ( imageCache.has( cacheKey ) && isCacheValid( cacheKey ) ) {
                setImageUrl( imageCache.get( cacheKey ) );
                return;
            }

            // Check negative cache
            if ( noImageCache.has( cacheKey ) && isCacheValid( cacheKey ) ) {
                setImageUrl( null );
                return;
            }

            // Handle direct URL
            if ( isDirectUrl ) {
                const exists = await checkImage( identifier );
                if ( exists ) {
                    imageCache.set( cacheKey, identifier );
                    cacheTimestamps.set( cacheKey, Date.now() );
                    setImageUrl( identifier );
                    return;
                }
                noImageCache.add( cacheKey );
                cacheTimestamps.set( cacheKey, Date.now() );
                setImageUrl( null );
                return;
            }

            // For themes, check local screenshot first
            if ( type === ASSET_TYPES.THEME && assetPath ) {
                const localScreenshot = `${ assetPath }/screenshot.png`;
                const exists = await checkImage( localScreenshot );
                if ( exists ) {
                    imageCache.set( cacheKey, localScreenshot );
                    cacheTimestamps.set( cacheKey, Date.now() );
                    setImageUrl( localScreenshot );
                    return;
                }
            }

            // Try WordPress.org repository for plugins
            if ( type === ASSET_TYPES.PLUGIN ) {
                const sizes = [ 'icon-256x256', 'icon-128x128', 'icon' ];
                const extensions = [ 'png', 'jpg', 'gif', 'svg' ];

                for ( const size of sizes ) {
                    for ( const ext of extensions ) {
                        const url = `https://ps.w.org/${ identifier }/assets/${ size }.${ ext }`;
                        const exists = await checkImage( url );
                        if ( exists ) {
                            imageCache.set( cacheKey, url );
                            cacheTimestamps.set( cacheKey, Date.now() );
                            setImageUrl( url );
                            return;
                        }
                    }
                }
            }

            // If no images found, add to negative cache
            noImageCache.add( cacheKey );
            cacheTimestamps.set( cacheKey, Date.now() );
            setImageUrl( null );
        };

        checkAndSetImage();
    }, [ identifier, type, assetPath, checkImage, isDirectUrl ] );

    return imageUrl;
};

// Export type constants
export { ASSET_TYPES };
export default useAssetImage;
