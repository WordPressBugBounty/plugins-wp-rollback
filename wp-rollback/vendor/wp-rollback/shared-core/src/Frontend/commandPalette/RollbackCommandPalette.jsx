import { useMemo } from '@wordpress/element';
import { rotateRight } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Dynamic command loader hook surfacing "Rollback plugin/theme" entries in the
 * WordPress Command Palette. Called by the command palette's React context —
 * not by our own component — so React hooks are valid here.
 *
 * @param {Object} props        Hook arguments passed by the command palette
 * @param {string} props.search Current search string typed by the user
 * @return {{ commands: Array, isLoading: boolean }} Commands matching the search
 */
export function useRollbackCommandLoader( { search } ) {
    const { plugins = [], themes = [], adminUrl = '' } = window.wprCommandPaletteData || {};

    const commands = useMemo( () => {
        const s = search.toLowerCase().trim();
        const results = [];

        /**
         * Parse rollback-prefixed searches so typing beyond "rollback" keeps
         * filtering rather than blanking results. Examples:
         *
         *   "rollback"                → type=null,     nameFilter=""  (show all)
         *   "rollback "               → same after trim
         *   "rollback plugin"         → type="plugin", nameFilter=""  (plugins only)
         *   "rollback plugin akismet" → type="plugin", nameFilter="akismet"
         *   "rollback theme"          → type="theme",  nameFilter=""  (themes only)
         *   "rollback theme twenty"   → type="theme",  nameFilter="twenty"
         *   "rollback akismet"        → type=null,     nameFilter="akismet"
         *   "akismet"                 → not a rollback search, filter by name directly
         */
        const rollbackMatch = s.match( /^rollback(?:\s+(plugin|theme))?(?:\s+(.+))?$/ );
        const isRollbackSearch = s === '' || rollbackMatch !== null;
        const rollbackType = rollbackMatch?.[ 1 ] ?? null; // "plugin", "theme", or null
        const nameFilter = ( rollbackMatch?.[ 2 ] ?? '' ).trim();

        // Suppress plugins when user has typed "rollback theme …"
        const includePlugins = ! isRollbackSearch || rollbackType !== 'theme';
        // Suppress themes when user has typed "rollback plugin …"
        const includeThemes = ! isRollbackSearch || rollbackType !== 'plugin';

        if ( includePlugins ) {
            plugins
                .filter( p => {
                    if ( isRollbackSearch ) {
                        return nameFilter === '' || p.name.toLowerCase().includes( nameFilter );
                    }
                    return p.name.toLowerCase().includes( s );
                } )
                .slice( 0, 10 )
                .forEach( p =>
                    results.push( {
                        name: `wp-rollback/plugin-${ p.slug }`,
                        label: sprintf(
                            // translators: %s: Plugin name
                            __( 'Rollback plugin: %s', 'wp-rollback' ),
                            p.name
                        ),
                        icon: rotateRight,
                        callback: ( { close } ) => {
                            document.location.href = `${ adminUrl }#/rollback/plugin/${ p.slug }`;
                            close();
                        },
                    } )
                );
        }

        if ( includeThemes ) {
            themes
                .filter( t => {
                    if ( isRollbackSearch ) {
                        return nameFilter === '' || t.name.toLowerCase().includes( nameFilter );
                    }
                    return t.name.toLowerCase().includes( s );
                } )
                .slice( 0, 5 )
                .forEach( t =>
                    results.push( {
                        name: `wp-rollback/theme-${ t.slug }`,
                        label: sprintf(
                            // translators: %s: Theme name
                            __( 'Rollback theme: %s', 'wp-rollback' ),
                            t.name
                        ),
                        icon: rotateRight,
                        callback: ( { close } ) => {
                            document.location.href = `${ adminUrl }#/rollback/theme/${ t.slug }`;
                            close();
                        },
                    } )
                );
        }

        return results;
    }, [ search, plugins, themes, adminUrl ] );

    return { commands, isLoading: false };
}
