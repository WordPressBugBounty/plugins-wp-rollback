import { createContext, useContext, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const RollbackContext = createContext();

/**
 * Provider component for rollback related state management
 *
 * @param {Object}                    props            Component properties
 * @param {JSX.Element|JSX.Element[]} props.children   Child components
 * @param {string}                    props.type       Asset type (plugin or theme)
 * @param {string}                    props.slug       Asset slug
 * @param {Function}                  [props.onCancel] Optional custom cancel handler
 * @return {JSX.Element} Context provider component
 */
export const RollbackProvider = ( { children, type, slug, onCancel } ) => {
    // API data state
    const [ isLoading, setIsLoading ] = useState( true );
    const [ rollbackInfo, setRollbackInfo ] = useState( false );
    const [ currentVersion, setCurrentVersion ] = useState( null );
    const [ error, setError ] = useState( null );
    const [ isPremiumAsset, setIsPremiumAsset ] = useState( false );
    const [ rollbackSteps, setRollbackSteps ] = useState( [] );

    // Modal state
    const [ isModalOpen, setIsModalOpen ] = useState( false );
    const [ modalTemplate, setModalTemplate ] = useState( 'failed' );
    const [ errorMessage, setErrorMessage ] = useState( null );

    // Rollback version state
    const [ rollbackVersion, setRollbackVersion ] = useState( null );

    // Rollback meta data state
    const [ rollbackMeta, setRollbackMeta ] = useState( {} );

    // Helper function to update specific meta fields
    const updateRollbackMeta = ( key, value ) => {
        setRollbackMeta( prevMeta => ( {
            ...prevMeta,
            [ key ]: value,
        } ) );
    };

    // Fetch rollback steps from the API
    useEffect( () => {
        const fetchRollbackSteps = async () => {
            try {
                const response = await apiFetch( {
                    path: '/wp-rollback/v1/rollback-steps/',
                } );

                if ( response && Array.isArray( response.steps ) ) {
                    setRollbackSteps( response.steps );
                }
            } catch ( stepError ) {
                // Silently fail - the steps will be empty array which is handled in the UI
                console.error( 'Failed to fetch rollback steps:', stepError );
            }
        };

        fetchRollbackSteps();
    }, [] );

    // API data fetching
    useEffect( () => {
        const fetchData = async () => {
            try {
                const data = await apiFetch( {
                    path: addQueryArgs( '/wp-rollback/v1/fetch-info/', {
                        type,
                        slug,
                    } ),
                } );

                if ( ! data ) {
                    throw new Error( __( 'Failed to fetch rollback data.', 'wp-rollback' ) );
                }

                // Destructure the data property from the response
                const { data: rollbackData } = data;

                setRollbackInfo( rollbackData );
                setCurrentVersion( rollbackData?.currentVersion );

                // Apply a filter to the isPremiumAsset state, allowing pro plugin to override
                const assetIsPremium = applyFilters(
                    'wp_rollback_is_premium_asset',
                    rollbackData.isPro || false,
                    type,
                    slug
                );
                setIsPremiumAsset( assetIsPremium );
            } catch ( apiError ) {
                setError( apiError?.message || __( 'Error fetching rollback data', 'wp-rollback' ) );
            } finally {
                setIsLoading( false );
            }
        };

        if ( type && slug ) {
            fetchData();
        }
    }, [ type, slug ] );

    // Initialize rollbackVersion to currentVersion when data is loaded
    useEffect( () => {
        if ( ! isLoading && currentVersion && ! rollbackVersion && rollbackInfo?.versions ) {
            setRollbackVersion( currentVersion );
        }
    }, [ isLoading, currentVersion, rollbackVersion, rollbackInfo?.versions ] );

    // Handle navigation back to home or custom cancel action
    const handleCancel = () => {
        if ( typeof onCancel === 'function' ) {
            onCancel();
        } else {
            // Default behavior - go to home page
            window.location.href = '/';
        }
    };

    const value = {
        // Route params
        type,
        slug,

        // Modal state
        isModalOpen,
        setIsModalOpen,
        modalTemplate,
        setModalTemplate,
        errorMessage,
        setErrorMessage,

        // Rollback data
        isLoading,
        rollbackInfo,
        currentVersion,
        setCurrentVersion,
        error,
        isPremiumAsset,
        rollbackSteps,

        // Rollback version
        rollbackVersion,
        setRollbackVersion,

        // Rollback meta data
        rollbackMeta,
        setRollbackMeta,
        updateRollbackMeta,

        // Actions
        handleCancel,
    };

    return <RollbackContext.Provider value={ value }>{ children }</RollbackContext.Provider>;
};

/**
 * Hook to access rollback context
 *
 * @return {Object} Rollback context values
 */
export const useRollbackContext = () => {
    const context = useContext( RollbackContext );
    if ( context === undefined ) {
        throw new Error( 'useRollbackContext must be used within a RollbackProvider' );
    }
    return context;
};

export default RollbackContext;
