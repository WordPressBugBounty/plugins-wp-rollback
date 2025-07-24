/**
 * Rollback Failed Modal.
 * Uses RollbackContext for state management.
 *
 * @param {Object} props         Component properties
 * @param {Object} props.buttons Button configuration for the template
 * @return {JSX.Element} Failed template content
 */
import { Notice, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { useRollbackContext } from '../../../context/RollbackContext';

import RollbackButtons from '../RollbackButtons';

const FailedTemplate = ( { buttons } ) => {
    const { rollbackInfo, type, errorMessage, rollbackNonce } = useRollbackContext();

    const rollbackErrorMessage = createInterpolateElement(
        __( 'An error occurred while attempting to rollback <rollbackName/>:', 'wp-rollback' ),
        {
            rollbackName: <strong>{ decodeEntities( rollbackInfo.name ) }</strong>,
        }
    );

    return (
        <>
            <p className="wpr-modal-intro">{ rollbackErrorMessage }</p>

            <div className="wpr-modal-content">
                <Notice status="error" isDismissible={ false }>
                    <strong>{ __( 'Error:', 'wp-rollback' ) }</strong>{ ' ' }
                    { errorMessage || __( 'An unknown error occurred', 'wp-rollback' ) }
                </Notice>

                <p>
                    { __( 'Need help?', 'wp-rollback' ) }&nbsp;
                    <ExternalLink href="https://wprollback.com/why-rollbacks-can-fail/">
                        { __( 'Read our guide on why rollbacks can fail', 'wp-rollback' ) }
                    </ExternalLink>
                </p>
            </div>

            <form className="rollback-form">
                <input type="hidden" name="page" value="wp-rollback" />
                { rollbackNonce && <input type="hidden" name="_wpnonce" value={ rollbackNonce } /> }
                <RollbackButtons buttons={ buttons } />
            </form>
        </>
    );
};

export default FailedTemplate;
