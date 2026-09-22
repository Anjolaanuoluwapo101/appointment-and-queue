import React from 'react';

/**
 * Reusable asynchronous loading button component with SVG spinner and dynamic feedback.
 */
export default function AsyncButton({
    children,
    loading = false,
    loadingText = 'Processing...',
    disabled = false,
    type = 'button',
    onClick,
    variant = 'primary',
    className = '',
    ...props
}) {
    const baseStyles = 'inline-flex items-center justify-center font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed rounded-md px-4 py-2 text-sm shadow-sm';

    const variants = {
        primary: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 active:scale-[0.98]',
        secondary: 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500 active:scale-[0.98]',
        success: 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500 active:scale-[0.98]',
        danger: 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500 active:scale-[0.98]',
        outline: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500 active:scale-[0.98]',
    };

    const currentVariant = variants[variant] || variants.primary;

    return (
        <button
            type={type}
            disabled={disabled || loading}
            onClick={onClick}
            className={`${baseStyles} ${currentVariant} ${className}`}
            aria-busy={loading}
            {...props}
        >
            {loading ? (
                <>
                    <svg
                        className="animate-spin -ml-1 mr-2 h-4 w-4 text-current inline-block"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span>{loadingText}</span>
                </>
            ) : (
                children
            )}
        </button>
    );
}
