import { __, sprintf } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

/**
 * SecurityBadge component for displaying package validation status.
 *
 * @param {Object} props               Component properties
 * @param {string} props.status        Validation status: 'validated', 'warning', 'failed', 'checking'
 * @param {string} props.message       Optional custom message to display
 * @param {number} props.filesChecked  Number of files that were validated (optional)
 * @return {JSX.Element}               The rendered component
 */
const SecurityBadge = ( { status = 'checking', message = '', filesChecked = null } ) => {
    const getStatusConfig = () => {
        switch ( status ) {
            case 'validated':
                return {
                    icon: 'yes-alt',
                    className: 'wpr-security-badge--validated',
                    text: filesChecked 
                        ? sprintf(
                            /* translators: %d: Number of files validated */
                            __( 'Validated (%d files)', 'wp-rollback' ),
                            filesChecked
                        )
                        : __( 'Validated', 'wp-rollback' ),
                    title: __( 'Package validated using WordPress Core methods', 'wp-rollback' ),
                };
            case 'warning':
                return {
                    icon: 'warning',
                    className: 'wpr-security-badge--warning',
                    text: __( 'Warning', 'wp-rollback' ),
                    title: __( 'Package validation completed with warnings', 'wp-rollback' ),
                };
            case 'failed':
                return {
                    icon: 'dismiss',
                    className: 'wpr-security-badge--failed',
                    text: __( 'Failed', 'wp-rollback' ),
                    title: __( 'Package validation failed', 'wp-rollback' ),
                };
            case 'checking':
            default:
                return {
                    icon: 'clock',
                    className: 'wpr-security-badge--checking',
                    text: __( 'Validating...', 'wp-rollback' ),
                    title: __( 'Validating package using WordPress Core methods', 'wp-rollback' ),
                };
        }
    };

    const config = getStatusConfig();
    const displayText = message || config.text;

    return (
        <span 
            className={ `wpr-security-badge ${ config.className }` }
            title={ config.title }
        >
            <Icon 
                icon={ config.icon } 
                size={ 16 }
                className="wpr-security-badge__icon"
            />
            <span className="wpr-security-badge__text">
                { displayText }
            </span>
        </span>
    );
};

export default SecurityBadge; 