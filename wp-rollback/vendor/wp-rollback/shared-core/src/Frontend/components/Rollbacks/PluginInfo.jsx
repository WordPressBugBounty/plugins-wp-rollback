import { __, sprintf } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import ExpandableText from './ExpandableText';
import VersionPill from './VersionPill';
import AuthorPill from './AuthorPill';

const PluginInfo = ( { rollbackInfo, type, currentVersion } ) => {
    return (
        <div className="wpr-plugin-info">
            <h2 className="wpr-plugin-name">
                { type === 'plugin' && (
                    <a
                        href={ `https://wordpress.org/plugins/${ rollbackInfo.slug }/` }
                        target={ '_blank' }
                        className={ 'wpr-heading-link' }
                        // translators: %s Plugin or Theme name.
                        title={ sprintf( __( 'View %s on WordPress.org', 'wp-rollback' ), rollbackInfo.name ) }
                        rel="noreferrer"
                    >
                        { decodeEntities( rollbackInfo.name ) }
                        <Dashicon icon="external" />
                    </a>
                ) }
                { type === 'theme' && (
                    <a
                        href={ rollbackInfo.homepage }
                        target={ '_blank' }
                        className={ 'wpr-heading-link' }
                        // translators: %s Plugin or Theme name.
                        title={ sprintf( __( 'View %s on WordPress.org', 'wp-rollback' ), rollbackInfo.name ) }
                        rel="noreferrer"
                    >
                        { decodeEntities( rollbackInfo.name ) }
                        <Dashicon icon="external" />
                    </a>
                ) }
            </h2>

            { type === 'theme' && rollbackInfo.description && (
                <div className={ 'wpr-theme-description' }>
                    <ExpandableText text={ rollbackInfo.description } />
                </div>
            ) }

            <div className="wpr-pill-wrap">
                <VersionPill version={ currentVersion } />

                { type === 'plugin' && <AuthorPill author={ rollbackInfo.author } /> }
            </div>
        </div>
    );
};

export default PluginInfo;
