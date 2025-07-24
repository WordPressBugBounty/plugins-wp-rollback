import { plugins, brush } from '@wordpress/icons';
import { Icon } from '@wordpress/components';

export const AssetImage = ( { type = 'plugin', imageUrl = null, width = 48, height = 48, className = '' } ) => {
    const defaultIcon = type === 'plugin' ? plugins : brush;

    return (
        <div
            className={ `wpr-${ type }-image ${ className }` }
            style={ {
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: typeof width === 'number' ? `${ width }px` : width,
                height: typeof height === 'number' ? `${ height }px` : height,
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
                <Icon icon={ defaultIcon } size={ Math.min( width, height ) * 0.75 } />
            ) }
        </div>
    );
};
