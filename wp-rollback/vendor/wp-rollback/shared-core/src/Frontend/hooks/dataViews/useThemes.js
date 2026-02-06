import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

// Constant empty array to maintain stable reference
const EMPTY_ARRAY = [];

/**
 * Hook for fetching theme records
 *
 * Enhances theme data with slug property for consistency with plugins
 *
 * @return {Object} Object containing data and isLoading state
 */
export const useThemes = () => {
    const { themes, isLoading } = useSelect( select => {
        const records = select( coreStore ).getEntityRecords( 'root', 'theme', {
            per_page: -1,
            context: 'edit',
        } );

        return {
            themes: records,
            isLoading: select( coreStore ).isResolving( 'getEntityRecords', [
                'root',
                'theme',
                {
                    per_page: -1,
                    context: 'edit',
                },
            ] ),
        };
    }, [] );

    // Enhance themes data with slug property for consistency
    const data = useMemo( () => {
        if ( ! themes || ! themes.length ) {
            return EMPTY_ARRAY;
        }

        return themes.map( theme => ( {
            ...theme,
            slug: theme.template, // Theme template is the slug identifier
        } ) );
    }, [ themes ] );

    return { data, isLoading };
};
