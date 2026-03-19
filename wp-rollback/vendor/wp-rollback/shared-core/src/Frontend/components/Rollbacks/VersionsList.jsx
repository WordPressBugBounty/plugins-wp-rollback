import { __ } from '@wordpress/i18n';
import { Dashicon, Tooltip } from '@wordpress/components';
import TrunkPopover from './TrunkPopover';
import { compareVersions } from '../../utils';

/**
 * Parse a release date from either a Unix timestamp (number) or ISO 8601 string.
 *
 * @param {number|string|null} released Raw release value from the API.
 * @return {string|null} Locale-formatted date string, or null.
 */
const formatReleaseDate = released => {
    if ( ! released ) {
        return null;
    }

    const date = typeof released === 'number' ? new Date( released * 1000 ) : new Date( released );

    return isNaN( date.getTime() ) ? null : date.toLocaleDateString();
};

/**
 * VersionsList component displays a list of available versions for rollback.
 *
 * Each version row shows a source badge based on the version's `source` field:
 * - `source: 'local'`  → "Local" badge  (backed-up on this site)
 * - `source: 'vault'`  → "Vault" badge  (approved version from the Plugin Vault)
 * - no source          → "Repo" badge   (version from the WordPress.org repository)
 *
 * @param {Object}   props                    Component properties
 * @param {Object}   props.versions           Object containing version information
 * @param {string}   props.rollbackVersion    Currently selected version for rollback
 * @param {Function} props.setRollbackVersion Function to set the rollback version
 * @param {string}   props.currentVersion     Currently installed version
 * @param {boolean}  props.disabled           Whether the versions list should be disabled
 * @return {JSX.Element} The versions list component
 */
const VersionsList = ( { versions, rollbackVersion, setRollbackVersion, currentVersion, disabled = false } ) => {
    if ( ! versions || typeof versions !== 'object' ) {
        return (
            <div className="wpr-versions-container">
                <div className="wpr-no-versions">{ __( 'No versions available', 'wp-rollback' ) }</div>
            </div>
        );
    }

    const sortedVersions = Object.keys( versions ).sort( ( a, b ) => compareVersions( b, a ) );

    const handleSelectionChange = version => {
        setRollbackVersion( version );
    };

    const versionsToDisplay = [ ...sortedVersions ];

    if ( ! versionsToDisplay.includes( currentVersion ) ) {
        versionsToDisplay.unshift( currentVersion );
    }

    if ( versions.trunk && ! versionsToDisplay.includes( 'trunk' ) ) {
        versionsToDisplay.push( 'trunk' );
    }

    return (
        <div className="wpr-versions-container">
            { versionsToDisplay.length === 0 ? (
                <div className="wpr-no-versions">{ __( 'No versions found', 'wp-rollback' ) }</div>
            ) : (
                versionsToDisplay.map( version => {
                    const versionData = versions[ version ] || {};
                    const releaseDate = formatReleaseDate( versionData.released );
                    const isCurrentVersion = currentVersion === version;
                    const isVault = versionData?.source === 'vault';
                    const isLocal = versionData?.source === 'local';
                    const isRepo = ! isVault && ! isLocal;

                    return (
                        <div
                            key={ version }
                            className={ `wpr-version-wrap ${ rollbackVersion === version ? 'wpr-active-row' : '' } ${
                                disabled ? 'wpr-version-option' : ''
                            }` }
                        >
                            <div className="wpr-version-radio-wrap">
                                <label htmlFor={ `version-${ version }` }>
                                    <input
                                        id={ `version-${ version }` }
                                        type="radio"
                                        name="version"
                                        value={ version }
                                        checked={ rollbackVersion === version }
                                        onChange={ () => ! disabled && handleSelectionChange( version ) }
                                        disabled={ disabled }
                                    />
                                    <span className="wpr-version-lineitem">{ version }</span>

                                    { isCurrentVersion && (
                                        <span className="wpr-version-lineitem-current">
                                            { __( 'Currently Installed', 'wp-rollback' ) }
                                        </span>
                                    ) }

                                    { version === 'trunk' && <TrunkPopover /> }

                                    <div className="wpr-version-badges">
                                        { isVault && (
                                            <Tooltip
                                                text={ __(
                                                    'Sourced from Plugin Vault — a shared library of plugin ZIPs contributed by WP Rollback Pro users and verified for integrity before distribution.',
                                                    'wp-rollback'
                                                ) }
                                            >
                                                <span className="wpr-version-source wpr-version-source--vault">
                                                    <Dashicon icon="cloud" />
                                                    { __( 'Vault', 'wp-rollback' ) }
                                                </span>
                                            </Tooltip>
                                        ) }

                                        { isLocal && (
                                            <Tooltip
                                                text={ __(
                                                    'Backed up locally on this site — restore from your own saved archive.',
                                                    'wp-rollback'
                                                ) }
                                            >
                                                <span className="wpr-version-source wpr-version-source--local">
                                                    <Dashicon icon="media-archive" />
                                                    { __( 'Local', 'wp-rollback' ) }
                                                </span>
                                            </Tooltip>
                                        ) }

                                        { isRepo && (
                                            <Tooltip
                                                text={ __(
                                                    'Available from the WordPress.org plugin repository.',
                                                    'wp-rollback'
                                                ) }
                                            >
                                                <span className="wpr-version-source wpr-version-source--repo">
                                                    <Dashicon icon="wordpress" />
                                                    { __( 'Repo', 'wp-rollback' ) }
                                                </span>
                                            </Tooltip>
                                        ) }

                                        { releaseDate && isRepo && (
                                            <span className="wpr-version-date">{ releaseDate }</span>
                                        ) }
                                    </div>
                                </label>
                            </div>
                        </div>
                    );
                } )
            ) }
        </div>
    );
};

export default VersionsList;
