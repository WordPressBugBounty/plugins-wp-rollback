/**
 * Frontend Utility Functions
 *
 * This file exports utility functions specific to the frontend UI.
 */

// Format version for display
export const formatDisplayVersion = version => {
    return version ? `v${ version }` : 'Unknown';
};

// Check if running in dev mode
export const isDevMode = () => {
    return process.env.NODE_ENV === 'development';
};

/**
 * Parse a version string into numeric segments and an optional pre-release label.
 *
 * Handles three common WordPress plugin patterns:
 *   - Hyphen-separated:  1.2.3-beta.1, 1.2.3-rc2
 *   - Inline suffix:     1.2.3beta, 1.2.3RC1, 1.2b2
 *   - Pure numeric:      1.2.3, 1.2, 1
 *
 * @param {string} ver Raw version string
 * @return {{ numbers: number[], preRelease: string|null }} Parsed version parts
 */
const parseVersionParts = ver => {
    // Normalise: trim whitespace, collapse spaces to hyphens
    const v = ver.trim().replace( /\s+/g, '-' );

    // Hyphen-separated pre-release: "1.2.3-beta", "1.2.3-rc.2"
    const hyphenIdx = v.indexOf( '-' );
    if ( hyphenIdx !== -1 ) {
        return {
            numbers: v
                .slice( 0, hyphenIdx )
                .split( '.' )
                .map( n => parseInt( n, 10 ) || 0 ),
            preRelease: v.slice( hyphenIdx + 1 ).toLowerCase(),
        };
    }

    // Inline pre-release suffix (no hyphen): "1.2.3beta", "1.2.3RC1", "1.2b2"
    const inlineMatch = v.match( /^([\d.]+?)([a-zA-Z].*)$/ );
    if ( inlineMatch ) {
        return {
            numbers: inlineMatch[ 1 ]
                .replace( /\.$/, '' )
                .split( '.' )
                .map( n => parseInt( n, 10 ) || 0 ),
            preRelease: inlineMatch[ 2 ].toLowerCase(),
        };
    }

    return {
        numbers: v.split( '.' ).map( n => parseInt( n, 10 ) || 0 ),
        preRelease: null,
    };
};

/**
 * Natural-sort two pre-release label strings.
 *
 * Splits each label into alternating alpha and numeric tokens so that
 * "beta10" correctly sorts after "beta9", and "rc" correctly sorts after "beta".
 * Comparison is already case-normalised before this function is called.
 *
 * @param {string} a First pre-release label (lowercase)
 * @param {string} b Second pre-release label (lowercase)
 * @return {number} 1 | -1 | 0
 */
const comparePreRelease = ( a, b ) => {
    const tokens = s => s.split( /(\d+)/ ).filter( Boolean );
    const tA = tokens( a );
    const tB = tokens( b );
    const len = Math.max( tA.length, tB.length );

    for ( let i = 0; i < len; i++ ) {
        const ta = tA[ i ] ?? '';
        const tb = tB[ i ] ?? '';
        const na = parseInt( ta, 10 );
        const nb = parseInt( tb, 10 );

        if ( ! isNaN( na ) && ! isNaN( nb ) ) {
            if ( na !== nb ) {
                return na > nb ? 1 : -1;
            }
        } else if ( ta !== tb ) {
            return ta > tb ? 1 : -1;
        }
    }

    return 0;
};

/**
 * Compare two version strings.
 *
 * Handles the full range of version formats used by WordPress plugin authors:
 * standard semver (1.2.3), partial (1.2), hyphenated pre-release (1.2.3-beta.1),
 * inline pre-release (1.2.3RC1, 1.2b2), date-based (20231015), and "trunk".
 *
 * Pre-release ordering: stable > rc > beta > alpha (alphabetical, case-insensitive,
 * with numeric suffixes compared numerically so beta10 > beta9).
 *
 * @param {string} a First version string
 * @param {string} b Second version string
 * @return {number} 1 if a > b, -1 if a < b, 0 if equal
 */
export const compareVersions = ( a, b ) => {
    if ( ! a || ! b ) {
        return 0;
    }
    if ( a === b ) {
        return 0;
    }
    if ( a === 'trunk' ) {
        return 1;
    }
    if ( b === 'trunk' ) {
        return -1;
    }

    const vA = parseVersionParts( a );
    const vB = parseVersionParts( b );
    const maxLen = Math.max( vA.numbers.length, vB.numbers.length );

    for ( let i = 0; i < maxLen; i++ ) {
        const nA = vA.numbers[ i ] || 0;
        const nB = vB.numbers[ i ] || 0;
        if ( nA > nB ) {
            return 1;
        }
        if ( nA < nB ) {
            return -1;
        }
    }

    // Same numeric base: stable release beats any pre-release
    if ( ! vA.preRelease && vB.preRelease ) {
        return 1;
    }
    if ( vA.preRelease && ! vB.preRelease ) {
        return -1;
    }
    if ( vA.preRelease && vB.preRelease ) {
        return comparePreRelease( vA.preRelease, vB.preRelease );
    }

    return 0;
};

/**
 * Determine the type of version change between the selected and installed versions.
 *
 * @param {string} selectedVersion  The version the user has selected
 * @param {string} installedVersion The currently installed version
 * @return {'reinstall'|'rollback'|'update'} The type of version change
 */
export const getVersionChangeType = ( selectedVersion, installedVersion ) => {
    const result = compareVersions( selectedVersion, installedVersion );
    if ( result === 0 ) {
        return 'reinstall';
    }
    if ( result > 0 ) {
        return 'update';
    }
    return 'rollback';
};

// Default export
export default {
    formatDisplayVersion,
    isDevMode,
    compareVersions,
    getVersionChangeType,
};
