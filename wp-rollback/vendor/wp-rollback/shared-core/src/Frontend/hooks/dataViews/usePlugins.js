import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

// Constant empty array to maintain stable reference
const EMPTY_ARRAY = [];

/**
 * Hook for fetching plugin records
 *
 * Enhances plugin data with extracted slug from plugin file path
 *
 * @return {Object} Object containing data and isLoading state
 */
export const usePlugins = () => {
    const { plugins, isLoading } = useSelect( select => {
        const records = select( coreStore ).getEntityRecords( 'root', 'plugin', {
            per_page: -1,
            context: 'edit',
        } );

        return {
            plugins: records,
            isLoading: select( coreStore ).isResolving( 'getEntityRecords', [
                'root',
                'plugin',
                {
                    per_page: -1,
                    context: 'edit',
                },
            ] ),
        };
    }, [] );

    // Enhance plugins data with slug using useMemo to prevent unnecessary recalculations
    const data = useMemo( () => {
        if ( ! plugins || ! plugins.length ) {
            return EMPTY_ARRAY;
        }

        return plugins.map( plugin => {
            // Extract slug from plugin file path (e.g., 'woocommerce/woocommerce.php' -> 'woocommerce')
            const slug = plugin.plugin.split( '/' )[ 0 ];
            return {
                ...plugin,
                slug,
            };
        } );
    }, [ plugins ] );

    return { data, isLoading };
};
