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

	/**
	 * Compare two version strings for sorting (descending order - newest first)
	 *
	 * @param {string} a First version
	 * @param {string} b Second version
	 * @return {number} Sort order
	 */
	const compareVersions = ( a, b ) => {
		// Trunk always goes last
		if ( a === 'trunk' ) {
			return 1;
		}
		if ( b === 'trunk' ) {
			return -1;
		}

		// Parse version strings
		const parseVersion = ver => {
			const parts = ver.split( '-' );
			const numbers = parts[ 0 ].split( '.' ).map( num => parseInt( num, 10 ) || 0 );
			const preRelease = parts.slice( 1 ).join( '-' ) || null;
			return { numbers, preRelease };
		};

		const versionA = parseVersion( a );
		const versionB = parseVersion( b );

		// Compare version numbers part by part
		const maxLen = Math.max( versionA.numbers.length, versionB.numbers.length );
		
		for ( let i = 0; i < maxLen; i++ ) {
			const numA = versionA.numbers[ i ] || 0;
			const numB = versionB.numbers[ i ] || 0;

			if ( numA > numB ) {
				return -1; // A is newer, should come first
			}
			if ( numA < numB ) {
				return 1; // B is newer, should come first
			}
		}

		// Base versions are equal, check pre-release tags
		// Stable versions (no pre-release) should come before pre-release
		if ( ! versionA.preRelease && versionB.preRelease ) {
			return -1;
		}
		if ( versionA.preRelease && ! versionB.preRelease ) {
			return 1;
		}

		// Both have pre-release, compare alphabetically in reverse
		if ( versionA.preRelease && versionB.preRelease ) {
			return versionB.preRelease.localeCompare( versionA.preRelease );
		}

		return 0;
	};

	const sortedAndFilteredVersions = Object.keys( versions )
		.filter( version => version.toLowerCase().includes( searchTerm.toLowerCase() ) )
		.sort( compareVersions );

	const handleSelectionChange = version => {
		setRollbackVersion( version );
	};

	// Ensure currentVersion and trunk are always in the list
	const versionsToDisplay = [ ...sortedAndFilteredVersions ];

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
