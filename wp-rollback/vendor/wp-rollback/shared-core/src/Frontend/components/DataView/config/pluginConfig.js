import { __ } from '@wordpress/i18n';
import PluginNameColumn from '../components/PluginNameColumn';
import StatusColumn from '../components/StatusColumn';
import ActionsColumn from '../components/ActionsColumn';
import VersionColumn from '../components/VersionColumn';

const primaryField = 'id';
const mediaField = 'img_src';

export const pluginConfig = {
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
            id: 'name',
            label: __( 'Plugin Name', 'wp-rollback' ),
            render: ( { item } ) => <PluginNameColumn item={ item } />,
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
                <ActionsColumn item={ item } onNavigateToRollback={ onNavigateToRollback } />
            ),
            enableSorting: false,
        },
    ],
};
