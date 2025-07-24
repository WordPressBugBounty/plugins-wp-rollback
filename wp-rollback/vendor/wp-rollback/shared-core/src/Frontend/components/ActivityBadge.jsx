import { Icon } from '@wordpress/components';

const activityStyles = {
    Install: 'gray',
    Delete: 'gray',
    Deactivate: 'gray',
    Uninstall: 'gray',
    Activate: 'gray',
};

const ActivityBadge = ( { activity, icon, children } ) => {
    const variant = activityStyles[ activity ] || 'gray';

    return (
        <span className={ `wpr-activity-badge wpr-activity-badge--${ variant }` }>
            { icon && <Icon icon={ icon } className="wpr-activity-badge__icon" size={ 13 } /> }
            { children }
        </span>
    );
};

export default ActivityBadge;
