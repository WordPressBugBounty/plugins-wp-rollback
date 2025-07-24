/**
 * Frontend Utility Functions
 *
 * This file exports utility functions specific to the frontend UI.
 */

// Format version for display
export const formatDisplayVersion = version => {
    return version ? `v${ version }` : 'Unknown';
};

// Check if running in dev mode
export const isDevMode = () => {
    return process.env.NODE_ENV === 'development';
};

// Default export
export default {
    formatDisplayVersion,
    isDevMode,
};
