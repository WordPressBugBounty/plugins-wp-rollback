import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

/**
 * DataViewBlankSlate component for displaying empty state
 *
 * @param {Object}        props             Component properties
 * @param {string}        props.title       Title text for empty state
 * @param {string}        props.description Description text for empty state
 * @param {IconType|null} props.icon        Optional icon to display above the title
 * @return {JSX.Element}                    The rendered component
 */
const DataViewBlankSlate = ( {
    title = __( 'No Data Found', 'wp-rollback' ),
    description = __( 'Data will appear here when available.', 'wp-rollback' ),
    icon = null,
} ) => {
    return (
        <div className="wpr-empty-state">
            { icon && (
                <div className="wpr-empty-state__icon">
                    <Icon icon={ icon } size={ 32 } />
                </div>
            ) }
            <h2>{ title }</h2>
            <p>{ description }</p>
        </div>
    );
};

export default DataViewBlankSlate;
