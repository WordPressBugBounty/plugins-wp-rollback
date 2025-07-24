import { __ } from '@wordpress/i18n';

/**
 * VersionPill component
 *
 * Displays a version pill with the installed version of the plugin/theme
 *
 * @param {string} version - The version number to display
 */
const VersionPill = ( { version } ) => {
    return (
        <div className="wpr-pill wpr-pill__black">
            <span className="wpr-pill-text">
                { __( 'Installed version:', 'wp-rollback' ) } <strong>{ version }</strong>
            </span>
        </div>
    );
};

export default VersionPill;
