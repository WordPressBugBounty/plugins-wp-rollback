import { __ } from '@wordpress/i18n';

/**
 * DataViewBlankSlate component for displaying empty state
 *
 * @param {Object} props             Component properties
 * @param {string} props.title       Title text for empty state
 * @param {string} props.description Description text for empty state
 * @return {JSX.Element}             The rendered component
 */
const DataViewBlankSlate = ( { 
    title = __( 'No Data Found', 'wp-rollback' ),
    description = __( 'Data will appear here when available.', 'wp-rollback' )
} ) => {
    return (
        <div className="wpr-empty-state">
            <h2>{ title }</h2>
            <p>{ description }</p>
        </div>
    );
};

export default DataViewBlankSlate;
