import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export const usePlugins = () => {
    return useSelect( select => {
        const plugins =
            select( coreStore ).getEntityRecords( 'root', 'plugin', {
                per_page: -1,
                context: 'edit',
            } ) || [];

        // Enhance plugins data with slug
        const enhancedPlugins = plugins.map( plugin => {
            // Extract slug from plugin file path (e.g., 'woocommerce/woocommerce.php' -> 'woocommerce')
            const slug = plugin.plugin.split( '/' )[ 0 ];
            return {
                ...plugin,
                slug,
            };
        } );

        return {
            data: enhancedPlugins,
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
};
