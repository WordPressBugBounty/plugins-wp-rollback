const statusStyles = {
    success: 'green',
    failure: 'red',
    error: 'red',
    aborted: 'yellow',
};

const Badge = ( { status = 'default', children } ) => {
    const variant = statusStyles[ status ] || 'gray';

    return <span className={ `wpr-badge wpr-badge--${ variant }` }>{ children }</span>;
};

export default Badge;
