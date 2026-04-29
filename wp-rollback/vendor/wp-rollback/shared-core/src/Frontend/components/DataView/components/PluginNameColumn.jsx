import { Button } from '@wordpress/components';
import useAssetImage from '../../../hooks/useAssetImage';
import { AssetImage } from '../../AssetImage';

const PluginNameColumn = ( { item, onNavigateToRollback } ) => {
    const imageUrl = useAssetImage( item.slug, 'plugin' );

    const handleClick = () => {
        if ( typeof onNavigateToRollback === 'function' ) {
            onNavigateToRollback( 'plugin', item.slug );
        }
    };

    return (
        <div className="wpr-name-column" style={ { display: 'flex', alignItems: 'center', gap: '15px' } }>
            <AssetImage slug={ item.slug } type="plugin" imageUrl={ imageUrl } width={ 48 } height={ 48 } />
            <Button className="wpr-name-column__link" label={ item.name } onClick={ handleClick }>
                { item.name }
            </Button>
        </div>
    );
};

export default PluginNameColumn;
