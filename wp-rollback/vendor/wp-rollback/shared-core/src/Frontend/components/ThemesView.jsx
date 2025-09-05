import { __ } from '@wordpress/i18n';
import DataView from './DataView';
import { useThemes } from '../hooks/dataViews/useThemes';
import { themeConfig } from './DataView/config/themeConfig';

const ThemesView = () => {
    const { data, isLoading } = useThemes();

    return (
        <DataView
            data={ data }
            isLoading={ isLoading }
            fields={ themeConfig.fields }
            defaultLayouts={ themeConfig.defaultLayouts }
            loadingMessage={ __( 'Loading themes…', 'wp-rollback' ) }
            emptyStateTitle={ __( 'No Themes Found', 'wp-rollback' ) }
            emptyStateDescription={ __( 'No themes available for rollback.', 'wp-rollback' ) }
        />
    );
};

export default ThemesView;
