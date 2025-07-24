import useAssetImage from '../../../hooks/useAssetImage';
import { AssetImage } from '../../AssetImage';

const PluginNameColumn = ( { item } ) => {
    const imageUrl = useAssetImage( item.slug, 'plugin' );

    return (
        <div style={ { display: 'flex', alignItems: 'center', gap: '15px' } }>
            <AssetImage slug={ item.slug } type="plugin" imageUrl={ imageUrl } width={ 48 } height={ 48 } />
            <p>{ item.name }</p>
        </div>
    );
};

export default PluginNameColumn;
