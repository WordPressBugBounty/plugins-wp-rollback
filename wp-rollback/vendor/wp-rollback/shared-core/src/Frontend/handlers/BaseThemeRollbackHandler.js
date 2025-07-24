/* global _wpThemeSettings */
import { createRoot } from '@wordpress/element';
import { UITextProvider } from '../context/UITextContext';
import ThemeRollbackButton from '../components/ThemeRollbackButton';

/**
 * Base Theme Rollback Handler
 * Contains common functionality for handling theme rollbacks
 */
export class BaseThemeRollbackHandler {
    constructor() {
        this.observerConfig = {
            childList: true,
            subtree: true,
        };
        this.initialized = false;
    }

    /**
     * Initialize the handler
     */
    initialize() {
        if ( this.initialized ) {
            return;
        }

        this.initialized = true;

        if ( document.readyState === 'loading' ) {
            document.addEventListener( 'DOMContentLoaded', () => this.setup() );
        } else {
            this.setup();
        }
    }

    /**
     * Set up the handler
     */
    setup() {
        this.setupThemeObserver();
        this.handleInitialTheme();
        this.setupThemeClickHandlers();
    }

    /**
     * Handle initial theme if one is already loaded
     */
    handleInitialTheme() {
        const themes = ( wp.themes = wp.themes || {} );
        const themeSettings = typeof _wpThemeSettings !== 'undefined' ? _wpThemeSettings : '';
        themes.data = themeSettings;

        // Handle single theme view
        if ( themes.data?.themes?.length === 1 ) {
            this.renderThemeRollback( themes.data.themes[ 0 ].id );
            return;
        }

        // Handle current theme if URL contains theme parameter
        const theme = this.getThemeFromUrl();
        if ( theme ) {
            this.renderThemeRollback( theme );
        }
    }

    /**
     * Set up click handlers for theme tiles
     */
    setupThemeClickHandlers() {
        document.querySelectorAll( '.theme' ).forEach( theme => {
            theme.addEventListener( 'click', () => {
                setTimeout( () => {
                    const themeSlug = this.getThemeSlug();
                    if ( themeSlug ) {
                        this.renderThemeRollback( themeSlug );
                    }
                }, 100 );
            } );
        } );
    }

    /**
     * Set up mutation observer for theme changes
     */
    setupThemeObserver() {
        const observer = new MutationObserver( mutations => {
            for ( const mutation of mutations ) {
                if ( mutation.type === 'childList' ) {
                    mutation.addedNodes.forEach( node => {
                        if ( this.isThemeOverlay( node ) && ! this.isRollbackButtonPresent() ) {
                            const theme = this.getThemeSlug();
                            if ( theme ) {
                                this.renderThemeRollback( theme );
                            }
                        }
                    } );
                }
            }
        } );

        const stableParent = document.querySelector( '.wrap' ) || document.body;
        observer.observe( stableParent, this.observerConfig );
    }

    /**
     * Check if node is or contains theme overlay
     * @param {Node} node DOM node to check
     * @return {boolean} Whether node is theme overlay
     */
    isThemeOverlay( node ) {
        return (
            ( node.matches && node.matches( '.theme-overlay' ) ) ||
            ( node.querySelector && node.querySelector( '.theme-overlay' ) )
        );
    }

    /**
     * Check if rollback button is already present
     * @return {boolean} Whether button exists
     */
    isRollbackButtonPresent() {
        return document.querySelector( '.wpr-theme-rollback' ) !== null;
    }

    /**
     * Get theme slug using multiple methods
     * @return {string|null} Theme slug
     */
    getThemeSlug() {
        // Try URL first
        let theme = this.getThemeFromUrl();

        // Try theme overlay data attribute
        if ( ! theme ) {
            const themeOverlay = document.querySelector( '.theme-overlay' );
            if ( themeOverlay?.dataset.theme ) {
                theme = themeOverlay.dataset.theme;
            }
        }

        // Try active theme
        if ( ! theme ) {
            const currentTheme = document.querySelector( '.theme.active' );
            if ( currentTheme?.dataset.slug ) {
                theme = currentTheme.dataset.slug;
            }
        }

        return theme;
    }

    /**
     * Get theme slug from URL
     * @return {string|null} Theme slug
     */
    getThemeFromUrl() {
        const queryArgs = new URLSearchParams( window.location.search );
        return queryArgs.get( 'theme' );
    }

    /**
     * Get theme data
     * @param {string} theme Theme slug
     * @return {Object|null} Theme data
     */
    getThemeData( theme ) {
        const themeData = wp.themes?.data?.themes;
        if ( ! Array.isArray( themeData ) ) {
            return null;
        }
        return themeData.find( t => t.id === theme ) || null;
    }

    /**
     * Render the rollback button
     * @param {string} theme Theme slug
     */
    renderThemeRollback( theme ) {
        if ( ! theme ) {
            return;
        }

        const themeActions = document.querySelector( '.theme-wrap .theme-actions' );
        if ( ! themeActions ) {
            return;
        }

        // Get theme data after we confirm we have a valid target
        const themeData = this.getThemeData( theme );

        // Remove existing container if present
        let container = document.getElementById( 'wpr-theme-rollback-container' );
        if ( container ) {
            container.remove();
        }

        // Create new container
        container = document.createElement( 'div' );
        container.id = 'wpr-theme-rollback-container';
        themeActions.appendChild( container );

        // Create root and render React component
        const root = createRoot( container );
        root.render(
            <UITextProvider>
                <ThemeRollbackButton theme={ theme } hasRollback={ this.shouldShowRollback( themeData ) } />
            </UITextProvider>
        );
    }

    /**
     * Determine if rollback should be shown
     * @param {Object|null} themeData Theme data
     * @return {boolean} Whether to show rollback
     */
    shouldShowRollback( themeData ) {
        return themeData !== null && themeData.hasRollback;
    }
}
