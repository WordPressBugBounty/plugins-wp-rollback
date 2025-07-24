import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import TrunkPopover from './TrunkPopover';

/**
 * VersionsList component displays a list of available versions for rollback
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
    const [ searchTerm, setSearchTerm ] = useState( '' );

    // Validate versions prop
    if ( ! versions || typeof versions !== 'object' ) {
        return (
            <div className="wpr-versions-container">
                <div className="wpr-no-versions">{ __( 'No versions available', 'wp-rollback' ) }</div>
            </div>
        );
    }

    const sortedAndFilteredVersions = Object.keys( versions )
        .filter( version => version.toLowerCase().includes( searchTerm.toLowerCase() ) )
        .sort( ( a, b ) => {
            if ( a === 'trunk' ) {
                return 1;
            }

            if ( b === 'trunk' ) {
                return -1;
            }
            return b.localeCompare( a, undefined, {
                numeric: true,
                sensitivity: 'base',
            } );
        } );

    const handleSelectionChange = version => {
        setRollbackVersion( version );
    };

    // Ensure currentVersion is in the list and selected by default
    const versionsToDisplay = sortedAndFilteredVersions.includes( currentVersion )
        ? sortedAndFilteredVersions
        : [ currentVersion, ...sortedAndFilteredVersions ];

    return (
        <div className="wpr-versions-container">
            { versionsToDisplay.length === 0 ? (
                <div className="wpr-no-versions">{ __( 'No versions found', 'wp-rollback' ) }</div>
            ) : (
                versionsToDisplay.map( version => {
                    const versionData = versions[ version ] || {};
                    const releaseDate = versionData.released
                        ? new Date( versionData.released * 1000 ).toLocaleDateString()
                        : null;

                    return (
                        <div
                            key={ version }
                            className={ `wpr-version-wrap ${ rollbackVersion === version ? 'wpr-active-row' : '' } ${ disabled ? 'wpr-version-option' : '' }` }
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
                                    { currentVersion === version && (
                                        <span className="wpr-version-lineitem-current">
                                            { __( 'Currently Installed', 'wp-rollback' ) }
                                        </span>
                                    ) }
                                    { version === 'trunk' && <TrunkPopover /> }
                                </label>
                            </div>

                            { releaseDate && <span className="wpr-version-date">{ releaseDate }</span> }
                        </div>
                    );
                } )
            ) }
        </div>
    );
};

export default VersionsList;
