import { __ } from '@wordpress/i18n';
import DataView from './DataView';
import { useThemes } from '../hooks/dataViews/useThemes';
import { useDataViewState } from '../hooks/dataViews/useDataViewState';
import { themeConfig } from './DataView/config/themeConfig';

/**
 * ThemesDataView component for displaying theme data in a customizable view
 *
 * @param {Object}   props                      Component properties
 * @param {Function} props.onNavigateToRollback Callback function for rollback navigation
 * @return {JSX.Element}                        The rendered component
 */
const ThemesDataView = ( { onNavigateToRollback } ) => {
    const { data, isLoading } = useThemes();
    const [ view, setView ] = useDataViewState( themeConfig );

    return (
        <DataView
            data={ data }
            isLoading={ isLoading }
            fields={ themeConfig.fields }
            defaultLayouts={ themeConfig.defaultLayouts }
            view={ view }
            onChangeView={ setView }
            onNavigateToRollback={ onNavigateToRollback }
            emptyStateTitle={ __( 'No Themes Found', 'wp-rollback' ) }
            emptyStateDescription={ __( 'No themes available for rollback.', 'wp-rollback' ) }
        />
    );
};

export default ThemesDataView;
