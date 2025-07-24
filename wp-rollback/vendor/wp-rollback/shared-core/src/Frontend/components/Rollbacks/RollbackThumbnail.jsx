import useAssetImage from '../../hooks/useAssetImage';

const RollbackThumbnail = ( { rollbackInfo, type } ) => {
    const imageUrl = useAssetImage( rollbackInfo.slug, type );

    if ( type === 'theme' && rollbackInfo.screenshotUrl ) {
        return (
            <div className="wpr-content-banner wpr-content-banner__theme">
                {
                    <img
                        src={ rollbackInfo.screenshotUrl }
                        width="240"
                        height="180"
                        className="wpr-theme-screenshot"
                        alt={ rollbackInfo.name }
                    />
                }
            </div>
        );
    }

    if ( type === 'plugin' && imageUrl ) {
        return (
            <div className="wpr-plugin-avatar-wrap">
                <img
                    src={ imageUrl }
                    width={ 96 }
                    height={ 96 }
                    className="wpr-plugin-avatar"
                    alt={ rollbackInfo.name }
                />
            </div>
        );
    }

    return null;
};

export default RollbackThumbnail;
