const Subheader = ( { title, description } ) => {
    return (
        <div className="wpr-subheader">
            <h1>{ title }</h1>
            <p>{ description }</p>
        </div>
    );
};

export default Subheader;
