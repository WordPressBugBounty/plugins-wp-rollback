import { useState } from '@wordpress/element';
import { Popover } from '@wordpress/components';

/**
 * Generic hover-triggered popover wrapper.
 *
 * Wraps any child element so that mousing over it reveals a Popover containing
 * the provided text. Uses the same Popover component and pattern as TrunkPopover.
 *
 * @param {Object} props          Component properties
 * @param {string} props.text     Text to display inside the popover.
 * @param {*}      props.children The trigger element.
 * @return {JSX.Element} The wrapped trigger with a conditionally-rendered popover.
 */
function HoverPopover( { text, children } ) {
    const [ isVisible, setIsVisible ] = useState( false );

    return (
        <div
            className="wpr-popover-wrap"
            onMouseEnter={ () => setIsVisible( true ) }
            onMouseLeave={ () => setIsVisible( false ) }
        >
            { children }
            { isVisible && (
                <Popover
                    position="top"
                    className="wpr-popover"
                    variant="unstyled"
                    onClose={ () => setIsVisible( false ) }
                    noArrow={ false }
                >
                    { text }
                </Popover>
            ) }
        </div>
    );
}

export default HoverPopover;
