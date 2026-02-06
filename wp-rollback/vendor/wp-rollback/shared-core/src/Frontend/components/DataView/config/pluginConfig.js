import { __ } from '@wordpress/i18n';
import PluginNameColumn from '../components/PluginNameColumn';
import { createVersionField, createStatusField, createActionsField, createDefaultLayouts } from './fieldHelpers';

export const pluginConfig = {
    defaultLayouts: createDefaultLayouts( 'id', 'img_src' ),
    fields: [
        {
            id: 'name',
            label: __( 'Plugin Name', 'wp-rollback' ),
            render: ( { item } ) => <PluginNameColumn item={ item } />,
            getValue: ( { item } ) => item.name,
            enableSorting: true,
            enableHiding: false,
        },
        createVersionField(),
        createStatusField(),
        createActionsField( 'plugin' ),
    ],
};
