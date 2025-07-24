import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useRollbackContext } from '../../context/RollbackContext';

/**
 * Rollback actions component
 *
 * @return {JSX.Element} The rollback actions component
 */
const RollbackActions = () => {
    const { setIsModalOpen, setModalTemplate, rollbackVersion, currentVersion, handleCancel } = useRollbackContext();

    const handleRollback = () => {
        setModalTemplate( 'confirm' );
        setIsModalOpen( true );
    };

    // Determine if rollback button should be disabled
    const isRollbackDisabled = rollbackVersion === currentVersion;

    return (
        <div className="wpr-button-wrap">
            <Button
                variant="primary"
                onClick={ handleRollback }
                className="wpr-button-submit"
                disabled={ isRollbackDisabled } // Disable if rollbackVersion is the same as currentVersion
            >
                { __( 'Rollback', 'wp-rollback' ) }
            </Button>

            <Button variant="secondary" onClick={ handleCancel } className="wpr-button-cancel">
                { __( 'Cancel', 'wp-rollback' ) }
            </Button>
        </div>
    );
};

export default RollbackActions;
