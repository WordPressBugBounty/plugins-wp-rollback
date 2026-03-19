/**
 * Rollback Confirmation Modal.
 * Uses RollbackContext for state management.
 *
 * @param {Object} props         Component properties
 * @param {Object} props.buttons Button configuration for the template
 * @return {JSX.Element} Confirmation template content
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { arrowRight, undo, info } from '@wordpress/icons';
import { useRollbackContext } from '../../../context/RollbackContext';
import { getVersionChangeType } from '../../../utils';
import RollbackButtons from '../RollbackButtons';

const changeTypeConfig = {
    rollback: {
        toLabel: __( 'Rolling Back To', 'wp-rollback' ),
    },
    update: {
        toLabel: __( 'Updating To', 'wp-rollback' ),
    },
    reinstall: {
        toLabel: __( 'Reinstalling', 'wp-rollback' ),
    },
};

const ConfirmTemplate = ( { buttons } ) => {
    const { rollbackInfo, rollbackVersion, currentVersion, type } = useRollbackContext();

    const rollbackName = decodeEntities( rollbackInfo?.name || __( 'Unknown Plugin', 'wp-rollback' ) );
    const changeType = getVersionChangeType( rollbackVersion, currentVersion );
    const config = changeTypeConfig[ changeType ];
    const typeLabel =
        type === 'plugin' ? __( 'WordPress Plugin', 'wp-rollback' ) : __( 'WordPress Theme', 'wp-rollback' );

    return (
        <>
            { /* Asset identity */ }
            <div className="wpr-confirm-asset">
                <span className="wpr-confirm-asset__type">{ typeLabel }</span>
                <strong className="wpr-confirm-asset__name">{ rollbackName }</strong>
            </div>

            { /* Version comparison */ }
            { changeType === 'reinstall' ? (
                <div className="wpr-version-compare wpr-version-compare--reinstall">
                    <div className="wpr-version-compare__card wpr-version-compare__card--to">
                        <span className="wpr-version-compare__label">{ config.toLabel }</span>
                        <span className="wpr-version-compare__number">{ rollbackVersion }</span>
                    </div>
                    <div className="wpr-version-compare__reinstall-icon">
                        <Icon icon={ undo } size={ 18 } />
                        { __( 'Same version will be reinstalled', 'wp-rollback' ) }
                    </div>
                </div>
            ) : (
                <div className="wpr-version-compare">
                    <div className="wpr-version-compare__card wpr-version-compare__card--from">
                        <span className="wpr-version-compare__label">{ __( 'Installed', 'wp-rollback' ) }</span>
                        <span className="wpr-version-compare__number">{ currentVersion }</span>
                    </div>
                    <div className="wpr-version-compare__arrow">
                        <Icon icon={ arrowRight } size={ 22 } />
                    </div>
                    <div className="wpr-version-compare__card wpr-version-compare__card--to">
                        <span className="wpr-version-compare__label">{ config.toLabel }</span>
                        <span className="wpr-version-compare__number">{ rollbackVersion }</span>
                    </div>
                </div>
            ) }

            { /* Compact warning */ }
            <div className="wpr-confirm-warning">
                <Icon icon={ info } size={ 16 } />
                <p>
                    { __(
                        'Back up your files and database before continuing. WP Rollback is not responsible for issues resulting from this action.',
                        'wp-rollback'
                    ) }
                </p>
            </div>

            <RollbackButtons buttons={ buttons } />
        </>
    );
};

export default ConfirmTemplate;
