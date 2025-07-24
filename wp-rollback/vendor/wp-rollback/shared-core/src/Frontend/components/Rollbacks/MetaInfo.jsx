import { __ } from '@wordpress/i18n';
import { Button, Dashicon } from '@wordpress/components';
import { humanTimeDiff } from '@wordpress/date';

const MetaInfo = ( { rollbackInfo, type, setIsModalOpen, setModalTemplate } ) => {
    const handleChangelogClick = () => {
        setIsModalOpen( true );
        setModalTemplate( 'changelog' );
    };

    return (
        <div className="wpr-meta-wrap">
            { type === 'theme' && (
                <div className="wpr-meta-item wpr-meta-item__author-wrap">
                    <h3>{ __( 'Theme Author', 'wp-rollback' ) }</h3>
                    <div className="wpr-theme-author-inner">
                        <div>
                            <img
                                src={ rollbackInfo.authorAvatar }
                                width={ 64 }
                                height={ 64 }
                                alt={ rollbackInfo.author.display_name }
                            />
                            <div className="wpr-theme-author-info">
                                <a href={ rollbackInfo.authorAvatar } target="_blank" rel="noopener noreferrer">
                                    { rollbackInfo.author }
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            ) }

            { type === 'plugin' && (
                <div className="wpr-meta-wrap__plugins">
                    <div className="wpr-view-changelog">
                        <Button variant="secondary" onClick={ handleChangelogClick } className="wpr-version-changelog">
                            { __( 'View Changelog', 'wp-rollback' ) }
                        </Button>
                    </div>
                    <h3>{ __( 'Last Updated', 'wp-rollback' ) }</h3>
                    <div className="wpr-updater-info">
                        <Dashicon icon="clock" />
                        <span className="wpr-plugin-lastupdate">
                            { rollbackInfo.lastUpdated ? humanTimeDiff( rollbackInfo.lastUpdated ) : '' }
                        </span>
                    </div>
                </div>
            ) }
        </div>
    );
};

export default MetaInfo;
