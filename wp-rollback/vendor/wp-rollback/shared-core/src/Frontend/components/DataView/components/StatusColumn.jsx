import { __ } from '@wordpress/i18n';
import Badge from '../../Badge';

const statusLabels = {
    active: __( 'Active', 'wp-rollback' ),
    inactive: __( 'Inactive', 'wp-rollback' ),
};

const StatusColumn = ( { item } ) => {
    const label = statusLabels[ item.status ] || statusLabels.default;

    return <Badge status={ item.status }>{ label }</Badge>;
};

export default StatusColumn;
