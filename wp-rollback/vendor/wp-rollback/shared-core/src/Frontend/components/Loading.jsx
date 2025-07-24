/**
 * Loading component.
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Loading = () => (
    <div className="wpr-loading-content">
        <div className="wpr-loading-text">
            <Spinner style={ { height: 'calc(4px * 20)', width: 'calc(4px * 20)' } } />
            <p>{ __( 'Loading…', 'wp-rollback' ) }</p>
        </div>
    </div>
);

export default Loading;
