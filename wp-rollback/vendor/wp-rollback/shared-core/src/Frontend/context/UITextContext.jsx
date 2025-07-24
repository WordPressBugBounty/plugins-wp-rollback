import { createContext, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const UITextContext = createContext();

/**
 * Provider component for UI text strings
 *
 * @param {Object}                    props          Component properties
 * @param {JSX.Element|JSX.Element[]} props.children Child components
 * @return {JSX.Element} Context provider component
 */
export const UITextProvider = ( { children } ) => {
    const value = {
        rollbackLabel: __( 'Rollback', 'wp-rollback' ),
        notRollbackable: __( 'Rollback not available', 'wp-rollback' ),
    };

    return <UITextContext.Provider value={ value }>{ children }</UITextContext.Provider>;
};

/**
 * Hook to access UI text strings
 *
 * @return {Object} UI text strings
 */
export const useUIText = () => {
    const context = useContext( UITextContext );
    if ( context === undefined ) {
        throw new Error( 'useUIText must be used within a UITextProvider' );
    }
    return context;
};

export default UITextContext;
