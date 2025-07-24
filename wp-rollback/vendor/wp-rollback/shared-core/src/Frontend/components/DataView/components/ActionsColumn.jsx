import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';

/**
 * ActionsColumn component renders rollback action button
 *
 * @param {Object}   props                      Component properties
 * @param {Object}   props.item                 Item data (plugin or theme)
 * @param {string}   props.type                 Item type ('plugin' or 'theme')
 * @param {Function} props.onNavigateToRollback Callback function to navigate to rollback page
 * @return {JSX.Element}                  The rendered component
 */
const ActionsColumn = ( { item, type = 'plugin', onNavigateToRollback } ) => {
    const handleClick = () => {
        if ( typeof onNavigateToRollback === 'function' ) {
            const slug = type === 'plugin' ? item.plugin.split( '/' )[ 0 ] : item.stylesheet.split( '/' )[ 0 ];
            onNavigateToRollback( type, slug );
        }
    };

    return (
        <Button
            size="compact"
            variant="secondary"
            icon={ <Icon icon="backup" /> }
            iconSize={ 16 }
            onClick={ handleClick }
        >
            { __( 'Rollback', 'wp-rollback' ) }
        </Button>
    );
};

export default ActionsColumn;
