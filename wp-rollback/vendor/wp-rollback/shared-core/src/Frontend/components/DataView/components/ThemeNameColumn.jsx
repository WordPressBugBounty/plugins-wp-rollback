const ThemeNameColumn = ( { item } ) => {
    return (
        <div className="wpr-theme-name-column" style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
            <p className="wpr-name-column__text" title={ item.name.rendered }>
                { item.name.rendered }
            </p>
        </div>
    );
};

export default ThemeNameColumn;
