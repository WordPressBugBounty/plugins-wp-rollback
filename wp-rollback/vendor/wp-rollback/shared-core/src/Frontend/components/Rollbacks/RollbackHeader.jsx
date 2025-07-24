import { __ } from '@wordpress/i18n';
import Subheader from '../Subheader';
import { useRollbackContext } from '../../context/RollbackContext';

/**
 * Rollback header component
 *
 * @return {JSX.Element} The header component
 */
const RollbackHeader = () => {
    const { type } = useRollbackContext();

    return (
        <>
            { 'plugin' === type && (
                <Subheader
                    title={ __( 'Plugin Rollback', 'wp-rollback' ) }
                    description={ __(
                        'Select which plugin version you would like to rollback to from the releases listed below.',
                        'wp-rollback'
                    ) }
                />
            ) }

            { 'theme' === type && (
                <Subheader
                    title={ __( 'Theme Rollback', 'wp-rollback' ) }
                    description={ __(
                        'Select which theme version you would like to rollback to from the releases listed below.',
                        'wp-rollback'
                    ) }
                />
            ) }
        </>
    );
};

export default RollbackHeader;
