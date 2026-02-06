import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Hook for server-side DataView data fetching
 *
 * Handles fetching data from a REST API endpoint with pagination, sorting, and filtering
 *
 * @param {string} endpoint         API endpoint path
 * @param {Object} fieldToColumnMap Mapping of field IDs to database column names
 * @param {Object} initialView      Initial view configuration
 * @param {Object} additionalParams Additional query parameters
 * @return {Object} Object containing data, loading state, pagination, and view handlers
 */
export const useServerSideDataView = ( endpoint, fieldToColumnMap = {}, initialView = {}, additionalParams = {} ) => {
    const [ data, setData ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ paginationInfo, setPaginationInfo ] = useState( {
        totalItems: 0,
        totalPages: 1,
    } );

    const [ view, setView ] = useState( {
        type: 'table',
        page: 1,
        perPage: 10,
        sort: {
            field: 'created_at',
            direction: 'desc',
        },
        ...initialView,
    } );

    // Serialize complex objects for dependency tracking
    const additionalParamsKey = JSON.stringify( additionalParams );
    const fieldToColumnMapKey = JSON.stringify( fieldToColumnMap );

    // Fetch data when view or additional params change
    useEffect( () => {
        const fetchData = async () => {
            setIsLoading( true );
            try {
                // Map field ID to database column name
                const orderbyColumn = fieldToColumnMap[ view.sort.field ] || view.sort.field;

                const queryParams = {
                    page: view.page,
                    per_page: view.perPage,
                    orderby: orderbyColumn,
                    order: view.sort.direction.toUpperCase(),
                    ...( view.search && { search: view.search } ),
                    ...additionalParams,
                };

                const response = await apiFetch( {
                    path: addQueryArgs( endpoint, queryParams ),
                    method: 'GET',
                } );

                setData( response.items || [] );
                setPaginationInfo( {
                    totalItems: response.total || 0,
                    totalPages: response.pages || 1,
                } );
            } catch ( error ) {
                // eslint-disable-next-line no-console
                console.error( `Error fetching data from ${ endpoint }:`, error );
                setData( [] );
            } finally {
                setIsLoading( false );
            }
        };

        fetchData();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        endpoint,
        view.page,
        view.perPage,
        view.sort.field,
        view.sort.direction,
        view.search,
        additionalParamsKey,
        fieldToColumnMapKey,
    ] );

    // Handler for optimistic deletion
    const handleOptimisticDelete = useCallback(
        deletedItem => {
            setData( prevData => prevData.filter( item => item.id !== deletedItem.id ) );

            setPaginationInfo( prev => {
                const newTotal = Math.max( 0, prev.totalItems - 1 );
                const newTotalPages = Math.ceil( newTotal / view.perPage );

                return {
                    totalItems: newTotal,
                    totalPages: newTotalPages,
                };
            } );

            // Navigate to previous page if current page no longer exists
            setPaginationInfo( prev => {
                if ( view.page > Math.ceil( ( prev.totalItems - 1 ) / view.perPage ) && view.page > 1 ) {
                    setView( prevView => ( {
                        ...prevView,
                        page: Math.ceil( ( prev.totalItems - 1 ) / view.perPage ),
                    } ) );
                }
                return prev;
            } );
        },
        [ view.perPage, view.page, setView ]
    );

    return {
        data,
        isLoading,
        paginationInfo,
        view,
        setView,
        handleOptimisticDelete,
    };
};
