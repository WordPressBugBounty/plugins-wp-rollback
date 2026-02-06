import { useState, useMemo } from '@wordpress/element';

/**
 * Hook for managing DataView state
 *
 * Creates and manages the view state for DataViews components with sensible defaults
 *
 * @param {Object} config               DataView configuration object
 * @param {string} defaultSortField     Default field to sort by
 * @param {string} defaultSortDirection Default sort direction ('asc' or 'desc')
 * @return {Array}                      Tuple of [view, setView]
 */
export const useDataViewState = ( config, defaultSortField = 'name', defaultSortDirection = 'asc' ) => {
    const initialView = useMemo(
        () => ( {
            type: 'table',
            perPage: 10,
            layout: config.defaultLayouts.table?.layout,
            fields: config.fields.map( field => field.id ),
            sort: {
                field: defaultSortField,
                direction: defaultSortDirection,
            },
        } ),
        [ config, defaultSortField, defaultSortDirection ]
    );

    return useState( initialView );
};
