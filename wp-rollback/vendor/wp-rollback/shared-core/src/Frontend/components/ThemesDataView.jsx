import { useState } from '@wordpress/element';
import DataView from './DataView';
import { useThemes } from '../hooks/dataViews/useThemes';
import { themeConfig } from './DataView/config/themeConfig';

/**
 * ThemesDataView component for displaying theme data in a customizable view
 *
 * @param {Object}   props                      Component properties
 * @param {Function} props.onNavigateToRollback Callback function for rollback navigation
 * @return {JSX.Element}                   The rendered component
 */
const ThemesDataView = ( { onNavigateToRollback } ) => {
    const { data, isLoading } = useThemes();
    const [ view, setView ] = useState( {
        type: 'table',
        perPage: 10,
        layout: themeConfig.defaultLayouts.table?.layout,
        fields: themeConfig.fields.map( field => field.id ),
    } );

    return (
        <DataView
            data={ data }
            isLoading={ isLoading }
            fields={ themeConfig.fields }
            defaultLayouts={ themeConfig.defaultLayouts }
            view={ view }
            onChangeView={ setView }
            onNavigateToRollback={ onNavigateToRollback }
        />
    );
};

export default ThemesDataView;
