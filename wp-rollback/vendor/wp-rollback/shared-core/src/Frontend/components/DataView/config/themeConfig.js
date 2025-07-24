import { __ } from '@wordpress/i18n';
import ThemeNameColumn from '../components/ThemeNameColumn';
import StatusColumn from '../components/StatusColumn';
import ActionsColumn from '../components/ActionsColumn';
import VersionColumn from '../components/VersionColumn';

const primaryField = 'template';
const mediaField = 'screenshot';

export const themeConfig = {
    defaultLayouts: {
        table: {
            layout: { primaryField },
        },
        grid: {
            layout: { primaryField, mediaField },
        },
    },
    fields: [
        {
            id: 'screenshot',
            label: __( 'Screenshot', 'wp-rollback' ),
            render: ( { item } ) => {
                return (
                    <div className="wpr-theme-screenshot">
                        <img src={ item.screenshot } alt={ item.name.rendered } />
                    </div>
                );
            },
            enableSorting: false,
        },
        {
            id: 'name',
            label: __( 'Theme Name', 'wp-rollback' ),
            render: ( { item } ) => <ThemeNameColumn item={ item } />,
            enableSorting: true,
            enableHiding: false,
        },
        {
            id: 'version',
            label: __( 'Version', 'wp-rollback' ),
            render: ( { item } ) => <VersionColumn item={ item } />,
            enableSorting: true,
        },
        {
            id: 'status',
            label: __( 'Status', 'wp-rollback' ),
            render: ( { item } ) => <StatusColumn item={ item } />,
            enableSorting: true,
        },
        {
            id: 'actions',
            label: __( 'Actions', 'wp-rollback' ),
            render: ( { item, onNavigateToRollback } ) => (
                <ActionsColumn item={ item } type={ 'theme' } onNavigateToRollback={ onNavigateToRollback } />
            ),
            enableSorting: false,
        },
    ],
};
