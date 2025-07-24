import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export const useThemes = () => {
    return useSelect(
        select => ( {
            data:
                select( coreStore ).getEntityRecords( 'root', 'theme', {
                    per_page: -1,
                    context: 'edit',
                } ) || [],
            isLoading: select( coreStore ).isResolving( 'getEntityRecords', [
                'root',
                'theme',
                {
                    per_page: -1,
                    context: 'edit',
                },
            ] ),
        } ),
        []
    );
};
