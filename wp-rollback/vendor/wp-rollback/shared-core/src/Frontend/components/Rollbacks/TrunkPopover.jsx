import { Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import HoverPopover from './HoverPopover';

function TrunkPopover() {
    return (
        <HoverPopover
            text={ __(
                'The active development version — latest code changes not yet released to the public. Intended for developers and testers.',
                'wp-rollback'
            ) }
        >
            <Dashicon icon="info" />
        </HoverPopover>
    );
}

export default TrunkPopover;
