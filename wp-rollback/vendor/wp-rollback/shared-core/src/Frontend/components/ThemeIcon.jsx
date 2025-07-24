import { brush } from '@wordpress/icons';
import { Icon } from '@wordpress/components';

export const ThemeIcon = ( { imageUrl } ) => {
    return (
        <div
            className="wpr-theme-icon"
            style={ {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: '48px',
                height: '48px',
                borderRadius: '5px',
                border: '1px solid #DDD',
                backgroundColor: '#f0f0f0',
                fill: '#949494',
                overflow: 'hidden',
            } }
        >
            { imageUrl ? (
                <img
                    src={ imageUrl }
                    alt=""
                    style={ {
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover',
                    } }
                />
            ) : (
                <Icon icon={ brush } size={ 36 } />
            ) }
        </div>
    );
};
