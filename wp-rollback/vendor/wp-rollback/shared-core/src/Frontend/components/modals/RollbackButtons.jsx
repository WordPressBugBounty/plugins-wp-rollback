import { Button, Spinner } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useRollbackContext } from '../../context/RollbackContext';

/**
 * Rollback buttons component.
 * Uses RollbackContext for state management.
 *
 * @param {Object} props         Component properties
 * @param {Object} props.buttons Buttons configuration
 * @return {JSX.Element} The buttons component
 */
const RollbackButtons = ( { buttons } ) => {
    const { setModalTemplate, setIsModalOpen, type } = useRollbackContext();
    const typeTitle = type === 'plugin' ? __( 'Plugin', 'wp-rollback' ) : __( 'Theme', 'wp-rollback' );
    const buttonTitle = createInterpolateElement( buttons.confirm.title, {
        type: <>{ typeTitle }</>,
    } );

    return (
        <div className="wpr-modal-button-wrap">
            { buttons.confirm && (
                <Button
                    onClick={ () => {
                        const result = buttons.confirm.onClick( type );

                        if ( result ) {
                            setModalTemplate( result );
                        }
                    } }
                    variant="primary"
                    disabled={ buttons.confirm.isProcessing }
                >
                    { buttons.confirm.isProcessing ? <Spinner /> : buttonTitle }
                </Button>
            ) }
            { buttons.cancel && (
                <Button
                    onClick={ () => {
                        // Execute custom cancel logic if provided
                        if ( buttons.cancel.onClick ) {
                            const result = buttons.cancel.onClick( type );
                            
                            // Only set new template if a valid template is returned
                            if ( result && typeof result === 'string' ) {
                                setModalTemplate( result );
                                return;
                            }
                        }
                        
                        // Default behavior: close the modal
                        setIsModalOpen( false );
                    } }
                    variant="secondary"
                >
                    { buttons.cancel.title }
                </Button>
            ) }
        </div>
    );
};

export default RollbackButtons;
