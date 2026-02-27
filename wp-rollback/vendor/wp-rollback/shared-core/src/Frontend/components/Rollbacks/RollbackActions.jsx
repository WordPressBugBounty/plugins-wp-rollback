import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useRollbackContext } from '../../context/RollbackContext';
import { getVersionChangeType } from '../../utils';

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

    const changeType = getVersionChangeType( rollbackVersion, currentVersion );

    const buttonLabels = {
        reinstall: __( 'Reinstall', 'wp-rollback' ),
        update: __( 'Update', 'wp-rollback' ),
        rollback: __( 'Rollback', 'wp-rollback' ),
    };

    return (
        <div className="wpr-button-wrap">
            <Button variant="primary" onClick={ handleRollback } className="wpr-button-submit">
                { buttonLabels[ changeType ] }
            </Button>

            <Button variant="secondary" onClick={ handleCancel } className="wpr-button-cancel">
                { __( 'Cancel', 'wp-rollback' ) }
            </Button>
        </div>
    );
};

export default RollbackActions;
