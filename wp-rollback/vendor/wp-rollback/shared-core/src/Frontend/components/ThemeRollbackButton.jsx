import { useUIText } from '../context/UITextContext';

/**
 * Theme rollback button component
 *
 * @param {Object}  props             Component properties
 * @param {string}  props.theme       Theme slug
 * @param {boolean} props.hasRollback Whether the theme has rollback available
 * @return {JSX.Element} The theme rollback button component
 */
const ThemeRollbackButton = ( { theme, hasRollback } ) => {
    const { rollbackLabel, notRollbackable } = useUIText();

    // Detect if we're in the pro version by checking for the wpRollbackPro global variable
    const isProVersion = typeof window.wpRollbackPro !== 'undefined';

    // Determine the correct page URL based on pro/free version
    const pageSlug = isProVersion ? 'wp-rollback-pro' : 'wp-rollback';
    const rollbackUrl = `tools.php?page=${ pageSlug }#/rollback/theme/${ theme }`;

    if ( ! hasRollback ) {
        return (
            <span
                className="no-rollback"
                style={ {
                    position: 'absolute',
                    left: '23px',
                    bottom: '16px',
                    fontSize: '12px',
                    fontStyle: 'italic',
                    color: 'rgb(181, 181, 181)',
                } }
            >
                { notRollbackable }
            </span>
        );
    }

    return (
        <a href={ rollbackUrl } className="button wpr-theme-rollback">
            { rollbackLabel }
        </a>
    );
};

export default ThemeRollbackButton;
