import { __ } from '@wordpress/i18n';
import TrunkPopover from './TrunkPopover';
import VersionBadges from './VersionBadges';
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
 * Badge rendering is delegated to VersionBadges. The Repo badge only shows
 * when a release date is present (indicating a WordPress.org version) — this
 * prevents premium plugin versions from incorrectly displaying the WP.org badge.
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

                                    { version === 'trunk' && <TrunkPopover /> }

                                    <VersionBadges
                                        versionData={ versionData }
                                        isCurrentVersion={ isCurrentVersion }
                                        releaseDate={ releaseDate }
                                    />
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
