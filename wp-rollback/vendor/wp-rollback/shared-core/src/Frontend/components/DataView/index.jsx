import { useMemo } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews/wp';
import { __ } from '@wordpress/i18n';
import Loading from '../Loading';

const EmptyStateContent = () => (
    <div className="wpr-empty-state">
        <h2>{ __( 'No Activity Found', 'wp-rollback' ) }</h2>
        <p>{ __( 'Activity will be logged here.', 'wp-rollback' ) }</p>
    </div>
);

/**
 * DataView component for displaying data in a customizable view
 *
 * @param {Object}   props                      Component properties
 * @param {Array}    props.data                 Data to display
 * @param {boolean}  props.isLoading            Whether data is loading
 * @param {Array}    props.fields               Field definitions
 * @param {Object}   props.defaultLayouts       Default layout configurations
 * @param {Object}   props.paginationInfo       Pagination information
 * @param {Object}   props.view                 Current view settings
 * @param {Function} props.onChangeView         Callback for view changes
 * @param {Function} props.onNavigateToRollback Callback for rollback navigation
 * @return {JSX.Element}                        The rendered component
 */
const DataView = ( {
    data,
    isLoading,
    fields,
    defaultLayouts,
    paginationInfo = { totalItems: 0, totalPages: 1 },
    view,
    onChangeView,
    onNavigateToRollback,
} ) => {
    const { data: processedData } = useMemo( () => {
        if ( ! data ) {
            return { data: [] };
        }
        const dataWithIds = data.map( ( item, index ) => ( {
            ...item,
            id: item.id || `item-${ index }`,
        } ) );

        return { data: dataWithIds };
    }, [ data ] );

    // Process fields to inject onNavigateToRollback to render functions
    const processedFields = useMemo( () => {
        if ( ! fields ) {
            return [];
        }

        return fields.map( field => {
            // If this is a field with a render function that might need onNavigateToRollback
            if ( field.render && field.id === 'actions' ) {
                return {
                    ...field,
                    render: props =>
                        field.render( {
                            ...props,
                            onNavigateToRollback,
                        } ),
                };
            }
            return field;
        } );
    }, [ fields, onNavigateToRollback ] );

    if ( isLoading ) {
        return <Loading />;
    }

    // Show custom empty state when there's no data
    if ( ! processedData.length ) {
        return <EmptyStateContent />;
    }

    return (
        <DataViews
            data={ processedData }
            defaultLayouts={ defaultLayouts }
            fields={ processedFields }
            view={ view }
            onChangeView={ onChangeView }
            isLoading={ isLoading }
            paginationInfo={ paginationInfo }
            search={ false }
        />
    );
};

export default DataView;
