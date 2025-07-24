/**
 * Get template configuration.
 *
 * @return {Object} Template configuration
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';
import ConfirmTemplate from './ConfirmTemplate';
import ProgressTemplate from './ProgressTemplate';
import FailedTemplate from './FailedTemplate';
import ChangelogTemplate from './ChangelogTemplate';

const getTemplateConfig = () => {
    let templates = {
        confirm: {
            title: __( 'Confirm Rollback', 'wp-rollback' ),
            icon: <Dashicon icon="image-rotate" />,
            component: ConfirmTemplate,
            buttons: {
                confirm: {
                    title: __( 'Rollback Now', 'wp-rollback' ),
                    onClick: () => 'progress',
                    isProcessing: false,
                },
                cancel: {
                    title: __( 'Cancel', 'wp-rollback' ),
                },
            },
        },
        progress: {
            title: __( 'Rolling Back…', 'wp-rollback' ),
            icon: <Dashicon icon="update" />,
            component: ProgressTemplate,
        },
        failed: {
            title: __( 'Rollback Failed', 'wp-rollback' ),
            icon: <Dashicon icon="warning" />,
            component: FailedTemplate,
            buttons: {
                confirm: {
                    title: __( 'Try Again', 'wp-rollback' ),
                    onClick: () => 'confirm',
                    isProcessing: false,
                },
                cancel: {
                    title: __( 'Cancel', 'wp-rollback' ),
                    onClick: () => null, // Return null to close modal
                },
            },
        },
        changelog: {
            title: __( 'View Changelog', 'wp-rollback' ),
            icon: <Dashicon icon="media-text" />,
            component: ChangelogTemplate,
            buttons: {
                cancel: {
                    title: __( 'Close', 'wp-rollback' ),
                },
            },
        },
    };

    // Allow plugins to modify the templates
    templates = applyFilters( 'wpRollback.templates', templates );

    return templates;
};

export default getTemplateConfig;
