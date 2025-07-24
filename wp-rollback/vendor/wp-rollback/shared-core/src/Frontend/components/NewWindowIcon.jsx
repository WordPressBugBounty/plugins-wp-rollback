const NewWindowIcon = ( { classNames = '' } ) => (
    <svg
        className={ `sp-ml-1 sp-text-gray-500 sp-h-4 sp-w-4 sp-inline-block sp-align-middle ${ classNames }` }
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
    >
        <path d="M7 17L17 7M17 7H8M17 7V16" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
);

export default NewWindowIcon;
