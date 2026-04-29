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
                    description={
                        <>
                            { __(
                                'All versions listed below are available directly from WordPress.org.',
                                'wp-rollback'
                            ) }
                            <br />
                            { __( 'Select a release to roll back.', 'wp-rollback' ) }
                        </>
                    }
                />
            ) }

            { 'theme' === type && (
                <Subheader
                    title={ __( 'Theme Rollback', 'wp-rollback' ) }
                    description={
                        <>
                            { __(
                                'All versions listed below are available directly from WordPress.org.',
                                'wp-rollback'
                            ) }
                            <br />
                            { __( 'Select a release to roll back.', 'wp-rollback' ) }
                        </>
                    }
                />
            ) }
        </>
    );
};

export default RollbackHeader;
