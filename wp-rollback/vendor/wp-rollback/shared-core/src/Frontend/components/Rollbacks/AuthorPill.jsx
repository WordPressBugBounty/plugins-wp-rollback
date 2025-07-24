import { __ } from '@wordpress/i18n';

/**
 * AuthorPill component
 *
 * Displays a version pill with the installed version of the plugin/theme
 *
 * @param {string} type   - The type of asset (plugin or theme)
 * @param {string} author - The author of the asset
 */
const AuthorPill = ( { type = 'plugin', author } ) => {
    return (
        <div className="wpr-pill wpr-pill__author">
            <span className="wpr-pill-text">
                { type === 'plugin' ? __( 'Plugin author:', 'wp-rollback' ) : __( 'Theme author:', 'wp-rollback' ) }{ ' ' }
                <span
                    className="wpr-pill__link"
                    dangerouslySetInnerHTML={ {
                        __html: author,
                    } }
                />
            </span>
        </div>
    );
};

export default AuthorPill;
