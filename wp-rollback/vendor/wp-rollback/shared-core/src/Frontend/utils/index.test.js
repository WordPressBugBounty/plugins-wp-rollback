// eslint-disable-next-line import/no-unresolved
import { describe, it, expect } from 'bun:test';
import { compareVersions, getVersionChangeType } from './index.js';

// ---------------------------------------------------------------------------
// compareVersions
// ---------------------------------------------------------------------------

describe( 'compareVersions', () => {
    // -----------------------------------------------------------------------
    // Null / undefined guards (initial render before data loads)
    // -----------------------------------------------------------------------
    describe( 'null / undefined inputs', () => {
        it( 'returns 0 when both arguments are null', () => {
            expect( compareVersions( null, null ) ).toBe( 0 );
        } );
        it( 'returns 0 when a is null', () => {
            expect( compareVersions( null, '1.0.0' ) ).toBe( 0 );
        } );
        it( 'returns 0 when b is null', () => {
            expect( compareVersions( '1.0.0', null ) ).toBe( 0 );
        } );
        it( 'returns 0 when both arguments are undefined', () => {
            expect( compareVersions( undefined, undefined ) ).toBe( 0 );
        } );
        it( 'returns 0 when a is undefined', () => {
            expect( compareVersions( undefined, '1.0.0' ) ).toBe( 0 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Equality
    // -----------------------------------------------------------------------
    describe( 'equality', () => {
        it( 'returns 0 for identical versions', () => {
            expect( compareVersions( '1.0.0', '1.0.0' ) ).toBe( 0 );
        } );
        it( 'returns 0 for identical two-part versions', () => {
            expect( compareVersions( '2.3', '2.3' ) ).toBe( 0 );
        } );
        it( 'returns 0 for trunk vs trunk', () => {
            expect( compareVersions( 'trunk', 'trunk' ) ).toBe( 0 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Standard numeric comparisons
    // -----------------------------------------------------------------------
    describe( 'standard numeric versions', () => {
        it( 'detects a higher major version', () => {
            expect( compareVersions( '2.0.0', '1.0.0' ) ).toBe( 1 );
        } );
        it( 'detects a lower major version', () => {
            expect( compareVersions( '1.0.0', '2.0.0' ) ).toBe( -1 );
        } );
        it( 'detects a higher minor version', () => {
            expect( compareVersions( '1.2.0', '1.1.0' ) ).toBe( 1 );
        } );
        it( 'detects a higher patch version', () => {
            expect( compareVersions( '1.0.1', '1.0.0' ) ).toBe( 1 );
        } );
        it( 'handles two-part versions', () => {
            expect( compareVersions( '1.2', '1.1' ) ).toBe( 1 );
        } );
        it( 'handles single-digit versions', () => {
            expect( compareVersions( '2', '1' ) ).toBe( 1 );
        } );
        it( 'treats a two-part version as equal to the same three-part version with zero patch', () => {
            expect( compareVersions( '1.2', '1.2.0' ) ).toBe( 0 );
        } );
        it( 'handles date-based versions', () => {
            expect( compareVersions( '20231015', '20230901' ) ).toBe( 1 );
        } );
    } );

    // -----------------------------------------------------------------------
    // trunk
    // -----------------------------------------------------------------------
    describe( 'trunk', () => {
        it( 'trunk is greater than any numeric version', () => {
            expect( compareVersions( 'trunk', '99.99.99' ) ).toBe( 1 );
        } );
        it( 'any numeric version is less than trunk', () => {
            expect( compareVersions( '99.99.99', 'trunk' ) ).toBe( -1 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Hyphen-separated pre-release (1.2.3-beta)
    // -----------------------------------------------------------------------
    describe( 'hyphen-separated pre-release', () => {
        it( 'stable is greater than pre-release with the same numeric base', () => {
            expect( compareVersions( '1.0.0', '1.0.0-beta' ) ).toBe( 1 );
        } );
        it( 'pre-release is less than stable with the same numeric base', () => {
            expect( compareVersions( '1.0.0-beta', '1.0.0' ) ).toBe( -1 );
        } );
        it( 'rc is greater than beta (alphabetical ordering)', () => {
            expect( compareVersions( '1.0.0-rc', '1.0.0-beta' ) ).toBe( 1 );
        } );
        it( 'beta is greater than alpha', () => {
            expect( compareVersions( '1.0.0-beta', '1.0.0-alpha' ) ).toBe( 1 );
        } );
        it( 'rc.2 is greater than rc.1', () => {
            expect( compareVersions( '1.0.0-rc.2', '1.0.0-rc.1' ) ).toBe( 1 );
        } );
        it( 'beta10 is greater than beta9 (numeric suffix comparison)', () => {
            expect( compareVersions( '1.0.0-beta10', '1.0.0-beta9' ) ).toBe( 1 );
        } );
        it( 'beta2 is less than beta10', () => {
            expect( compareVersions( '1.0.0-beta2', '1.0.0-beta10' ) ).toBe( -1 );
        } );
        it( 'identical pre-release labels return 0', () => {
            expect( compareVersions( '1.0.0-beta', '1.0.0-beta' ) ).toBe( 0 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Inline pre-release without hyphen (1.2.3beta, 1.2b2, 1.2.3RC1)
    // -----------------------------------------------------------------------
    describe( 'inline pre-release (no hyphen)', () => {
        it( 'stable is greater than inline beta', () => {
            expect( compareVersions( '1.0.0', '1.0.0beta' ) ).toBe( 1 );
        } );
        it( 'inline beta is less than stable', () => {
            expect( compareVersions( '1.0.0beta', '1.0.0' ) ).toBe( -1 );
        } );
        it( 'inline RC is less than stable', () => {
            expect( compareVersions( '1.0.0RC1', '1.0.0' ) ).toBe( -1 );
        } );
        it( 'inline RC is greater than inline beta (same numeric base)', () => {
            expect( compareVersions( '1.0.0RC1', '1.0.0beta1' ) ).toBe( 1 );
        } );
        it( 'two-part inline pre-release is less than stable', () => {
            expect( compareVersions( '1.0b2', '1.0.0' ) ).toBe( -1 );
        } );
        it( 'higher numeric base beats pre-release regardless of suffix', () => {
            expect( compareVersions( '1.1.0beta', '1.0.0' ) ).toBe( 1 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Case insensitivity
    // -----------------------------------------------------------------------
    describe( 'case insensitivity', () => {
        it( 'RC1 and rc1 are treated as equal', () => {
            expect( compareVersions( '1.0.0-RC1', '1.0.0-rc1' ) ).toBe( 0 );
        } );
        it( 'RC is greater than beta regardless of case', () => {
            expect( compareVersions( '1.0.0-RC1', '1.0.0-beta1' ) ).toBe( 1 );
        } );
        it( 'mixed-case inline suffix is handled correctly', () => {
            expect( compareVersions( '1.0.0Beta', '1.0.0' ) ).toBe( -1 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Whitespace normalisation
    // -----------------------------------------------------------------------
    describe( 'whitespace normalisation', () => {
        it( 'trims leading/trailing whitespace', () => {
            expect( compareVersions( ' 1.0.0 ', '1.0.0' ) ).toBe( 0 );
        } );
        it( 'treats "1.0 beta" as a pre-release of 1.0', () => {
            expect( compareVersions( '1.0.0', '1.0 beta' ) ).toBe( 1 );
        } );
    } );

    // -----------------------------------------------------------------------
    // Mixed scenarios
    // -----------------------------------------------------------------------
    describe( 'mixed scenarios', () => {
        it( 'trunk is greater than a pre-release version', () => {
            expect( compareVersions( 'trunk', '1.0.0-beta' ) ).toBe( 1 );
        } );
        it( 'a pre-release version is less than trunk', () => {
            expect( compareVersions( '1.0.0-rc1', 'trunk' ) ).toBe( -1 );
        } );
        it( 'hyphen pre-release and inline pre-release are comparable', () => {
            // 1.0.0-rc vs 1.0.0RC1 → both have base 1.0.0, preRelease 'rc' vs 'rc1'
            // 'rc' tokens: ['rc'] vs ['rc', '1'] → 'rc'='rc', then tb='1' numeric, ta='' non-numeric → tb > ta → b > a → -1
            expect( compareVersions( '1.0.0-rc', '1.0.0RC1' ) ).toBe( -1 );
        } );
    } );
} );

// ---------------------------------------------------------------------------
// getVersionChangeType
// ---------------------------------------------------------------------------

describe( 'getVersionChangeType', () => {
    describe( 'null / undefined inputs', () => {
        it( 'returns "reinstall" when both versions are null (initial render)', () => {
            expect( getVersionChangeType( null, null ) ).toBe( 'reinstall' );
        } );
        it( 'returns "reinstall" when selected version is null', () => {
            expect( getVersionChangeType( null, '1.0.0' ) ).toBe( 'reinstall' );
        } );
    } );

    describe( 'reinstall', () => {
        it( 'returns "reinstall" when selecting the same stable version', () => {
            expect( getVersionChangeType( '1.0.0', '1.0.0' ) ).toBe( 'reinstall' );
        } );
        it( 'returns "reinstall" when selecting the same pre-release version', () => {
            expect( getVersionChangeType( '1.0.0-beta', '1.0.0-beta' ) ).toBe( 'reinstall' );
        } );
    } );

    describe( 'rollback', () => {
        it( 'returns "rollback" when selecting a lower version', () => {
            expect( getVersionChangeType( '1.0.0', '2.0.0' ) ).toBe( 'rollback' );
        } );
        it( 'returns "rollback" when selecting a pre-release of the installed stable', () => {
            expect( getVersionChangeType( '1.0.0-beta', '1.0.0' ) ).toBe( 'rollback' );
        } );
        it( 'returns "rollback" when selecting an older pre-release', () => {
            expect( getVersionChangeType( '1.0.0-alpha', '1.0.0-beta' ) ).toBe( 'rollback' );
        } );
        it( 'returns "rollback" when current is trunk and a numeric version is selected', () => {
            expect( getVersionChangeType( '1.0.0', 'trunk' ) ).toBe( 'rollback' );
        } );
    } );

    describe( 'update', () => {
        it( 'returns "update" when selecting a higher version', () => {
            expect( getVersionChangeType( '2.0.0', '1.0.0' ) ).toBe( 'update' );
        } );
        it( 'returns "update" when selecting the stable release of the installed pre-release', () => {
            expect( getVersionChangeType( '1.0.0', '1.0.0-beta' ) ).toBe( 'update' );
        } );
        it( 'returns "update" when selecting a later pre-release (rc after beta)', () => {
            expect( getVersionChangeType( '1.0.0-rc', '1.0.0-beta' ) ).toBe( 'update' );
        } );
        it( 'returns "update" when selecting trunk', () => {
            expect( getVersionChangeType( 'trunk', '1.0.0' ) ).toBe( 'update' );
        } );
        it( 'returns "update" for inline RC over installed beta', () => {
            expect( getVersionChangeType( '1.0.0RC1', '1.0.0beta' ) ).toBe( 'update' );
        } );
    } );
} );
