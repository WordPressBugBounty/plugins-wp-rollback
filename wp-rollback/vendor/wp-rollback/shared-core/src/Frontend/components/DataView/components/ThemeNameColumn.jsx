import { Button } from '@wordpress/components';

const ThemeNameColumn = ( { item, onNavigateToRollback } ) => {
    const displayName = item.name?.rendered || item.name;

    const handleClick = () => {
        if ( typeof onNavigateToRollback === 'function' ) {
            onNavigateToRollback( 'theme', item.slug );
        }
    };

    return (
        <div className="wpr-theme-name-column" style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
            <Button className="wpr-name-column__link" label={ displayName } onClick={ handleClick }>
                { displayName }
            </Button>
        </div>
    );
};

export default ThemeNameColumn;
