import { useMemo } from '@wordpress/element';
import { DataViews } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import Loading from '../Loading';
import DataViewBlankSlate from './DataViewBlankSlate';

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
 * @param {Function} props.onDelete             Callback for delete action
 * @param {string}   props.emptyStateTitle      Custom title for empty state
 * @param {string}   props.emptyStateDescription Custom description for empty state
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
    onDelete,
    emptyStateTitle,
    emptyStateDescription,
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

    // Process fields to inject onNavigateToRollback and onDelete to render functions
    const processedFields = useMemo( () => {
        if ( ! fields ) {
            return [];
        }

        return fields.map( field => {
            // If this is a field with a render function that might need onNavigateToRollback or onDelete
            if ( field.render && field.id === 'actions' ) {
                return {
                    ...field,
                    render: props =>
                        field.render( {
                            ...props,
                            onNavigateToRollback,
                            onDelete,
                        } ),
                };
            }
            return field;
        } );
    }, [ fields, onNavigateToRollback, onDelete ] );

    if ( isLoading ) {
        return <Loading />;
    }

    // Show custom empty state when there's no data
    if ( ! processedData.length ) {
        return (
            <DataViewBlankSlate 
                title={ emptyStateTitle }
                description={ emptyStateDescription }
            />
        );
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
