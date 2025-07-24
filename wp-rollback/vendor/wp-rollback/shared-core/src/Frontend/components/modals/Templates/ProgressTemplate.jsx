import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState, createInterpolateElement } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';
import { useRollbackContext } from '../../../context/RollbackContext';

/**
 * Progress template showing rollback progress with enhanced UI/UX.
 * Features artificial delays, animations, icons, and step-by-step visualization.
 *
 * @return {JSX.Element} Progress template content
 */
const ProgressTemplate = () => {
    const { setModalTemplate, rollbackInfo, rollbackVersion, type, slug, setErrorMessage, rollbackMeta } =
        useRollbackContext();

    const [ currentStep, setCurrentStep ] = useState( 0 );
    const [ steps, setSteps ] = useState( [] );
    const [ isComplete, setIsComplete ] = useState( false );
    const [ hasError, setHasError ] = useState( false );
    const [ progress, setProgress ] = useState( 0 );

    // Step status: 'pending', 'running', 'completed', 'error'
    const [ stepStatuses, setStepStatuses ] = useState( {} );

    const delay = ( ms ) => new Promise( resolve => setTimeout( resolve, ms ) );

    const updateStepStatus = ( stepIndex, status, message = '' ) => {
        setStepStatuses( prev => ({
            ...prev,
            [ stepIndex ]: { status, message }
        }) );
    };

    const getStepIcon = ( status ) => {
        switch ( status ) {
            case 'running':
                return (
                    <div className="wpr-step-icon wpr-step-icon--running">
                        <div className="wpr-spinner"></div>
                    </div>
                );
            case 'completed':
                return (
                    <div className="wpr-step-icon wpr-step-icon--completed">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>
                );
            case 'error':
                return (
                    <div className="wpr-step-icon wpr-step-icon--error">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>
                );
            default:
                return (
                    <div className="wpr-step-icon wpr-step-icon--pending">
                        <div className="wpr-step-dot"></div>
                    </div>
                );
        }
    };

    useEffect( () => {
        const performRollback = async () => {
            if ( ! rollbackVersion ) {
                setHasError( true );
                setErrorMessage( __( 'Rollback version not specified.', 'wp-rollback' ) );
                setModalTemplate( 'failed' );
                return;
            }

            try {
                // Artificial delay for initial preparation
                await delay( 800 );

                // Fetch available steps from the server
                const stepsResponse = await apiFetch( {
                    path: '/wp-rollback/v1/rollback-steps/',
                    method: 'GET',
                } );

                if ( ! stepsResponse.success || ! stepsResponse.steps ) {
                    throw new Error( __( 'Failed to fetch rollback steps.', 'wp-rollback' ) );
                }

                const rollbackSteps = stepsResponse.steps;
                setSteps( rollbackSteps );

                // Initialize all steps as pending
                const initialStatuses = {};
                rollbackSteps.forEach( ( step, index ) => {
                    initialStatuses[ index ] = { status: 'pending', message: '' };
                } );
                setStepStatuses( initialStatuses );

                // Base request data
                const requestData = {
                    version: rollbackVersion,
                    type,
                    slug,
                    nonce: rollbackInfo?.nonce || '',
                };

                // Allow plugins to modify the request data
                const modifiedRequestData = applyFilters( 'wpRollback.rollbackRequestBody', requestData, {
                    rollbackInfo,
                    rollbackVersion,
                    type,
                    slug,
                    meta: rollbackMeta,
                } );

                // Execute each step with delays and visual feedback
                for ( let i = 0; i < rollbackSteps.length; i++ ) {
                    const step = rollbackSteps[ i ];
                    setCurrentStep( i );
                    
                    // Update progress
                    const progressPercent = ( i / rollbackSteps.length ) * 100;
                    setProgress( progressPercent );

                    // Mark step as running
                    updateStepStatus( i, 'running', step.rollbackProcessingMessage );
                    
                    // Artificial delay to let user see the step start
                    await delay( 600 );

                    try {
                        // Execute the step
                        const response = await apiFetch( {
                            path: '/wp-rollback/v1/process-rollback',
                            method: 'POST',
                            data: {
                                ...modifiedRequestData,
                                step: step.id,
                                meta: rollbackMeta,
                            },
                        } );

                        if ( ! response.success ) {
                            throw new Error( response.message || __( 'An unknown error occurred.', 'wp-rollback' ) );
                        }

                        // Artificial delay for step completion visualization
                        await delay( 400 );

                        // Mark step as completed
                        const completionMessage = response.message || __( 'Step completed successfully', 'wp-rollback' );
                        updateStepStatus( i, 'completed', completionMessage );

                        // Additional delay to let user see completion
                        await delay( 300 );

                    } catch ( stepError ) {
                        updateStepStatus( i, 'error', stepError.message );
                        throw stepError;
                    }
                }

                // Final completion
                setProgress( 100 );
                setCurrentStep( rollbackSteps.length );
                setIsComplete( true );

                // Completion delay to show success state
                await delay( 800 );

            } catch ( error ) {
                setHasError( true );
                setErrorMessage( error.message || __( 'An unknown error occurred.', 'wp-rollback' ) );
                
                // Delay before showing error modal
                setTimeout( () => {
                    setModalTemplate( 'failed' );
                }, 1000 );
            }
        };

        performRollback();
    }, [ rollbackVersion, setModalTemplate ] ); // eslint-disable-line react-hooks/exhaustive-deps

    const introText = createInterpolateElement(
        __( 'Rolling <assetName/> back to version <assetVersion/>…', 'wp-rollback' ),
        {
            assetName: <strong>{ rollbackInfo.name }</strong>,
            assetVersion: <strong>{ rollbackVersion }</strong>,
        }
    );

    const getOverallStatus = () => {
        if ( hasError ) return 'error';
        if ( isComplete ) return 'completed';
        return 'running';
    };

    return (
        <div className={`wpr-progress-template wpr-progress-template--${getOverallStatus()}`}>
            <div className="wpr-progress-header">
                <p className="wpr-modal-intro">{ introText }</p>
                <p className="wpr-progress-subtitle" aria-live="polite">
                    { hasError 
                        ? __( 'An error occurred during the rollback process.', 'wp-rollback' )
                        : isComplete 
                            ? __( 'Rollback completed successfully! Click Continue to proceed.', 'wp-rollback' )
                            : __( 'Please wait while we safely rollback your asset.', 'wp-rollback' )
                    }
                </p>
            </div>

            {/* Progress Bar */}
            <div className="wpr-progress-bar-container">
                <div className={`wpr-progress-bar ${isComplete ? 'wpr-progress-bar--complete' : ''}`}>
                    <div 
                        className={`wpr-progress-bar-fill ${isComplete ? 'wpr-progress-bar-fill--complete' : ''}`}
                        style={{ width: `${progress}%` }}
                    ></div>
                </div>
                <span className="wpr-progress-percentage">{ Math.round( progress ) }%</span>
            </div>

            {/* Steps List */}
            <div className="wpr-steps-container">
                { steps.map( ( step, index ) => {
                    const status = stepStatuses[ index ] || { status: 'pending', message: '' };
                    const isActive = index === currentStep;
                    
                    return (
                        <div 
                            key={ step.id }
                            className={`wpr-step wpr-step--${status.status} ${isActive ? 'wpr-step--active' : ''}`}
                        >
                            { getStepIcon( status.status ) }
                            <div className="wpr-step-content">
                                <div className="wpr-step-title">
                                    { step.rollbackProcessingMessage || step.id }
                                </div>
                                { status.message && status.status === 'completed' && (
                                    <div className="wpr-step-message">
                                        { status.message }
                                    </div>
                                ) }
                            </div>
                        </div>
                    );
                } ) }
            </div>

            <div className="wpr-modal-button-wrap">
                <Button 
                    className={`wpr-progress-button wpr-progress-button--${getOverallStatus()}`}
                    variant="primary" 
                    disabled={ ! isComplete && ! hasError }
                    onClick={ () => {
                        if ( isComplete ) {
                            setModalTemplate( 'complete' );
                        }
                    } }
                >
                    { hasError 
                        ? __( 'Rollback Failed', 'wp-rollback' )
                        : isComplete 
                            ? __( 'Continue', 'wp-rollback' )
                            : __( 'Rollback in Progress…', 'wp-rollback' )
                    }
                </Button>
            </div>
        </div>
    );
};

export default ProgressTemplate;
