import { useMemo } from '@wordpress/element';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews';
import Loading from '../Loading';
import DataViewBlankSlate from './DataViewBlankSlate';

/**
 * DataView component for displaying data in a customizable view
 *
 * Supports both client-side and server-side pagination:
 * - Client-side: Pass data without paginationInfo. Filtering, sorting, and pagination
 *   are handled automatically using WordPress DataViews utilities.
 * - Server-side: Pass paginationInfo from API. Only sorting/filtering is applied client-side.
 *
 * @param {Object}   props                       Component properties
 * @param {Array}    props.data                  Data to display
 * @param {boolean}  props.isLoading             Whether data is loading
 * @param {Array}    props.fields                Field definitions
 * @param {Object}   props.defaultLayouts        Default layout configurations
 * @param {Object}   props.paginationInfo        Optional pagination info for server-side pagination
 * @param {Object}   props.view                  Current view settings (includes sort, filters, pagination)
 * @param {Function} props.onChangeView          Callback for view changes
 * @param {Function} props.onNavigateToRollback  Callback for rollback navigation
 * @param {Function} props.onDelete              Callback for delete action
 * @param {string}   props.emptyStateTitle       Custom title for empty state
 * @param {string}   props.emptyStateDescription Custom description for empty state
 * @return {JSX.Element}                         The rendered component
 */
const DataView = ( {
    data,
    isLoading,
    fields,
    defaultLayouts,
    paginationInfo,
    view,
    onChangeView,
    onNavigateToRollback,
    onDelete,
    emptyStateTitle,
    emptyStateDescription,
} ) => {
    // Ensure all items have IDs (fallback for data without IDs)
    const dataWithIds = useMemo( () => {
        if ( ! data?.length ) {
            return [];
        }

        // Check if any items are missing IDs
        if ( data.every( item => item.id ) ) {
            return data;
        }

        // Add fallback IDs only to items that need them
        return data.map( ( item, index ) => ( {
            ...item,
            id: item.id || `item-${ index }`,
        } ) );
    }, [ data ] );

    // Process fields to inject onNavigateToRollback and onDelete to render functions
    const processedFields = useMemo( () => {
        if ( ! fields?.length ) {
            return [];
        }

        // Only modify the actions field, return others as-is
        return fields.map( field =>
            field.id === 'actions' && field.render
                ? {
                      ...field,
                      render: props =>
                          field.render( {
                              ...props,
                              onNavigateToRollback,
                              onDelete,
                          } ),
                  }
                : field
        );
    }, [ fields, onNavigateToRollback, onDelete ] );

    // Apply filtering, sorting, and pagination
    const { data: processedData, paginationInfo: finalPaginationInfo } = useMemo( () => {
        // Empty state
        if ( ! dataWithIds?.length ) {
            return {
                data: [],
                paginationInfo: paginationInfo || { totalItems: 0, totalPages: 0 },
            };
        }

        // Server-side pagination: data already filtered/sorted/paginated
        if ( paginationInfo ) {
            return { data: dataWithIds, paginationInfo };
        }

        // Client-side: apply filtering, sorting, and pagination
        return filterSortAndPaginate( dataWithIds, view, processedFields );
    }, [ dataWithIds, view, processedFields, paginationInfo ] );

    if ( isLoading ) {
        return <Loading />;
    }

    // Show custom empty state when there's no data
    if ( ! dataWithIds.length ) {
        return <DataViewBlankSlate title={ emptyStateTitle } description={ emptyStateDescription } />;
    }

    return (
        <DataViews
            data={ processedData }
            defaultLayouts={ defaultLayouts }
            fields={ processedFields }
            view={ view }
            onChangeView={ onChangeView }
            isLoading={ isLoading }
            paginationInfo={ finalPaginationInfo }
            search={ false }
        />
    );
};

export default DataView;
