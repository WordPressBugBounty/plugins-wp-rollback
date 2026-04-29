import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import HoverPopover from './HoverPopover';

/**
 * Renders the right-side badge strip for a single version row.
 *
 * Badge logic:
 * - "Currently Installed" — always shown when isCurrentVersion is true.
 * - "Vault" — version.source === 'vault'
 * - "Local" — version.source === 'local'
 * - "Repo" — no explicit source AND downloadUrl is non-empty. The PHP API
 *   sets a real URL for WP.org versions and '' for synthetically-added
 *   current versions of premium plugins. (released is always null from API.)
 *
 * @param {Object}      props
 * @param {Object}      props.versionData      The version data object (source, released, …)
 * @param {boolean}     props.isCurrentVersion Whether this row is the currently installed version.
 * @param {string|null} props.releaseDate      Pre-formatted release date string, or null.
 * @return {JSX.Element|null} Null when there are no badges to display.
 */
const VersionBadges = ( { versionData = {}, isCurrentVersion = false, releaseDate = null } ) => {
    const source = versionData?.source;
    const isVault = source === 'vault';
    const isLocal = source === 'local';
    // Use downloadUrl as the Repo indicator: the PHP API sets a real URL for
    // WP.org versions and '' for synthetically-added premium plugin versions.
    // released is always null in the API so it cannot be used here.
    const isRepo = ! isVault && ! isLocal && !! versionData?.downloadUrl;

    if ( ! isCurrentVersion && ! isVault && ! isLocal && ! isRepo ) {
        return null;
    }

    return (
        <div className="wpr-version-badges">
            { isCurrentVersion && (
                <span className="wpr-version-source wpr-version-source--installed">
                    <Dashicon icon="yes-alt" />
                    { __( 'Installed', 'wp-rollback' ) }
                </span>
            ) }

            { isVault && (
                <HoverPopover
                    text={ __(
                        'From Plugin Vault — community-contributed ZIPs verified for integrity.',
                        'wp-rollback'
                    ) }
                >
                    <span className="wpr-version-source wpr-version-source--vault">
                        <Dashicon icon="cloud" />
                        { __( 'Vault', 'wp-rollback' ) }
                    </span>
                </HoverPopover>
            ) }

            { isLocal && (
                <HoverPopover text={ __( 'A local backup saved on this site.', 'wp-rollback' ) }>
                    <span className="wpr-version-source wpr-version-source--local">
                        <Dashicon icon="media-archive" />
                        { __( 'Local', 'wp-rollback' ) }
                    </span>
                </HoverPopover>
            ) }

            { isRepo && (
                <HoverPopover text={ __( 'Available on WordPress.org.', 'wp-rollback' ) }>
                    <span className="wpr-version-source wpr-version-source--repo">
                        <Dashicon icon="wordpress" />
                        { __( 'Repo', 'wp-rollback' ) }
                    </span>
                </HoverPopover>
            ) }

            { releaseDate && isRepo && <span className="wpr-version-date">{ releaseDate }</span> }
        </div>
    );
};

export default VersionBadges;
