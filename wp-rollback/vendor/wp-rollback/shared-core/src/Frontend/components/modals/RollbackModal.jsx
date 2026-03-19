import { Modal, Dashicon } from '@wordpress/components';
import getTemplateConfig from './Templates/getTemplateConfig';
import { createInterpolateElement } from '@wordpress/element';
import { useRollbackContext } from '../../context/RollbackContext';

/**
 * Rollback modal component
 *
 * @param {Object} [props.queryArgs]         Additional query arguments
 * @param          props.queryArgs.queryArgs
 * @return {JSX.Element|null} Modal component or null if not open
 */
const RollbackModal = ( { queryArgs = {} } ) => {
    const {
        isModalOpen,
        setIsModalOpen,
        modalTemplate = 'failed',
        rollbackInfo,
        type,
        isProgressComplete,
    } = useRollbackContext();

    // Check both modal state and required info
    if ( ! isModalOpen || ! rollbackInfo?.name ) {
        return null;
    }

    const TEMPLATES = getTemplateConfig();

    const {
        component: TemplateComponent,
        title: TemplateTitle,
        icon: templateConfigIcon,
        buttons,
    } = TEMPLATES[ modalTemplate ] || TEMPLATES.failed;

    // Stop the spinning update icon once the rollback steps are done
    const TemplateIcon =
        modalTemplate === 'progress' && isProgressComplete ? <Dashicon icon="yes-alt" /> : templateConfigIcon;

    const typeText = type === 'plugin' ? 'Plugin' : 'Theme';
    const FilteredTitle = createInterpolateElement( TemplateTitle, {
        type: <>{ typeText }</>,
    } );

    return (
        <Modal
            title={ FilteredTitle }
            className={ `wpr-modal wpr-modal--${ modalTemplate }` }
            shouldCloseOnClickOutside={ false }
            onRequestClose={ () => setIsModalOpen( false ) }
            icon={ TemplateIcon }
            isDismissible={ modalTemplate !== 'progress' } // Make progress modal non-dismissible
        >
            <TemplateComponent buttons={ buttons } queryArgs={ queryArgs } />
        </Modal>
    );
};

export default RollbackModal;
