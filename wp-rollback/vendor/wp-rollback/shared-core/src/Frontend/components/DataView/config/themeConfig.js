import { __ } from '@wordpress/i18n';
import ThemeNameColumn from '../components/ThemeNameColumn';
import { createVersionField, createStatusField, createActionsField, createDefaultLayouts } from './fieldHelpers';

export const themeConfig = {
    defaultLayouts: createDefaultLayouts( 'template', 'screenshot' ),
    fields: [
        {
            id: 'screenshot',
            label: __( 'Screenshot', 'wp-rollback' ),
            render: ( { item } ) => (
                <div className="wpr-theme-screenshot">
                    <img src={ item.screenshot } alt={ item.name.rendered } />
                </div>
            ),
            enableSorting: false,
        },
        {
            id: 'name',
            label: __( 'Theme Name', 'wp-rollback' ),
            render: ( { item } ) => <ThemeNameColumn item={ item } />,
            getValue: ( { item } ) => item.name?.rendered || item.name,
            enableSorting: true,
            enableHiding: false,
        },
        createVersionField(),
        createStatusField(),
        createActionsField( 'theme' ),
    ],
};
