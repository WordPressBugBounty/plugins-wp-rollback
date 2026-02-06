import { __ } from '@wordpress/i18n';
import StatusColumn from '../components/StatusColumn';
import ActionsColumn from '../components/ActionsColumn';
import VersionColumn from '../components/VersionColumn';

/**
 * Creates a version field configuration
 *
 * @return {Object} Version field configuration
 */
export const createVersionField = () => ( {
    id: 'version',
    label: __( 'Version', 'wp-rollback' ),
    render: ( { item } ) => <VersionColumn item={ item } />,
    getValue: ( { item } ) => item.version,
    enableSorting: true,
} );

/**
 * Creates a status field configuration
 *
 * @return {Object} Status field configuration
 */
export const createStatusField = () => ( {
    id: 'status',
    label: __( 'Status', 'wp-rollback' ),
    render: ( { item } ) => <StatusColumn item={ item } />,
    getValue: ( { item } ) => item.status,
    enableSorting: true,
} );

/**
 * Creates an actions field configuration
 *
 * @param {string} type Asset type ('theme' or 'plugin'), defaults to 'plugin'
 * @return {Object}     Actions field configuration
 */
export const createActionsField = ( type = 'plugin' ) => ( {
    id: 'actions',
    label: __( 'Actions', 'wp-rollback' ),
    render: ( { item, onNavigateToRollback } ) => (
        <ActionsColumn item={ item } type={ type } onNavigateToRollback={ onNavigateToRollback } />
    ),
    enableSorting: false,
} );

/**
 * Creates default layouts configuration for DataViews
 *
 * @param {string} primaryField Primary field for layouts
 * @param {string} mediaField   Media field for grid layout
 * @return {Object}             Default layouts configuration
 */
export const createDefaultLayouts = ( primaryField, mediaField = null ) => {
    const layouts = {
        table: {
            layout: { primaryField },
        },
    };

    if ( mediaField ) {
        layouts.grid = {
            layout: { primaryField, mediaField },
        };
    }

    return layouts;
};
