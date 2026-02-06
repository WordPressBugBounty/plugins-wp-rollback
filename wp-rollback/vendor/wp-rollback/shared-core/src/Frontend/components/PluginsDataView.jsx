import { __ } from '@wordpress/i18n';
import DataView from './DataView';
import { usePlugins } from '../hooks/dataViews/usePlugins';
import { useDataViewState } from '../hooks/dataViews/useDataViewState';
import { pluginConfig } from './DataView/config/pluginConfig';

/**
 * PluginsDataView component for displaying plugin data in a customizable view
 *
 * @param {Object}   props                      Component properties
 * @param {Function} props.onNavigateToRollback Callback function for rollback navigation
 * @return {JSX.Element}                        The rendered component
 */
const PluginsDataView = ( { onNavigateToRollback } ) => {
    const { data, isLoading } = usePlugins();
    const [ view, setView ] = useDataViewState( pluginConfig );

    return (
        <DataView
            data={ data }
            isLoading={ isLoading }
            fields={ pluginConfig.fields }
            defaultLayouts={ pluginConfig.defaultLayouts }
            view={ view }
            onChangeView={ setView }
            onNavigateToRollback={ onNavigateToRollback }
            emptyStateTitle={ __( 'No Plugins Found', 'wp-rollback' ) }
            emptyStateDescription={ __( 'No plugins available for rollback.', 'wp-rollback' ) }
        />
    );
};

export default PluginsDataView;
