const Banner = ( { rollbackInfo, type } ) => {
    if (
        ! rollbackInfo.banners ||
        type !== 'plugin' ||
        ( ! rollbackInfo.banners.high && ! rollbackInfo.banners.low )
    ) {
        return null;
    }

    return (
        <div className="wpr-content-banner">
            <img
                src={ rollbackInfo.banners.high || rollbackInfo.banners.low }
                width={ 800 }
                height={ 'auto' }
                className="wpr-plugin-banner"
                alt={ rollbackInfo.name }
            />
        </div>
    );
};

export default Banner;
