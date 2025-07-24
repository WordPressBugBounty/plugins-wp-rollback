/**
 * Changelog Modal.
 * Uses RollbackContext for accessing rollback info and slug.
 *
 * @return {JSX.Element} Changelog template content
 */
import { __, sprintf } from '@wordpress/i18n';
import { useRollbackContext } from '../../../context/RollbackContext';

const ChangelogTemplate = () => {
    const { rollbackInfo, slug } = useRollbackContext();

    console.log( 'Changelog template:', { rollbackInfo, slug } );

    const noChangelog = sprintf(
        /* translators: 1: Asset slug */
        __(
            'Sorry, we could not find a changelog entry for this version. Try checking the <a href="https://wordpress.org/plugins/%s/#developers" target="_blank">Development tab</a> on WP.org.',
            'wp-rollback'
        ),
        slug
    );

    return (
        <>
            <div
                className={ 'wpr-modal-intro' }
                dangerouslySetInnerHTML={ {
                    __html: rollbackInfo.changelog || noChangelog,
                } }
            ></div>
        </>
    );
};

export default ChangelogTemplate;
