/**
 * Rollback Confirmation Modal.
 * Uses RollbackContext for state management.
 *
 * @param {Object} props         Component properties
 * @param {Object} props.buttons Button configuration for the template
 * @return {JSX.Element} Confirmation template content
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { useRollbackContext } from '../../../context/RollbackContext';

import RollbackButtons from '../RollbackButtons';

const ConfirmTemplate = ( { buttons } ) => {
    const { rollbackInfo, rollbackVersion, currentVersion, type } = useRollbackContext();

    const rollbackName = decodeEntities( rollbackInfo?.name || __( 'Unknown Plugin', 'wp-rollback' ) );
    const translatedIntroduction = createInterpolateElement(
        __(
            'You are about to rollback <rollbackName/> from version <currentVersion/> to <rollbackVersion/>. Please confirm you would like to proceed.',
            'wp-rollback'
        ),
        {
            rollbackName: <strong>{ rollbackName }</strong>,
            currentVersion: <strong>{ currentVersion }</strong>,
            rollbackVersion: <strong>{ rollbackVersion }</strong>,
        }
    );

    return (
        <>
            { /* Modal Intro */ }
            <p className="wpr-modal-intro">{ translatedIntroduction }</p>

            { /* Rollback Details */ }
            <div className="rollback-details">
                <table className="widefat">
                    <tbody>
                        <tr>
                            <td className="row-title">
                                <label htmlFor="tablecell">
                                    { type === 'plugin'
                                        ? __( 'Plugin Name:', 'wp-rollback' )
                                        : __( 'Theme Name:', 'wp-rollback' ) }
                                </label>
                            </td>
                            <td>
                                <span className="wpr-plugin-name">{ rollbackName }</span>
                            </td>
                        </tr>
                        <tr className="alternate">
                            <td className="row-title">
                                <label htmlFor="tablecell">{ __( 'Installed Version:', 'wp-rollback' ) }</label>
                            </td>
                            <td>
                                <span className="wpr-installed-version">{ currentVersion }</span>
                            </td>
                        </tr>
                        <tr>
                            <td className="row-title">
                                <label htmlFor="tablecell">{ __( 'New Version:', 'wp-rollback' ) }</label>
                            </td>
                            <td>
                                <span className="wpr-new-version">{ rollbackVersion }</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            { /* Warning Notice */ }
            <Notice status={ 'warning' } isDismissible={ false }>
                <strong>{ __( 'Notice:', 'wp-rollback' ) }</strong>{ ' ' }
                { __(
                    'We strongly recommend you create a complete backup of your WordPress files and database prior to performing a rollback. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue resulting from the use of this plugin.',
                    'wp-rollback'
                ) }
            </Notice>

            { /* Buttons */ }
            <RollbackButtons buttons={ buttons } />
        </>
    );
};

export default ConfirmTemplate;
