import React from 'react';

export function Badge({ children, variant = 'default', className = '', ...props }) {
    const variants = {
        default: 'bg-zinc-100 text-zinc-900 border-zinc-200',
        secondary: 'bg-zinc-800 text-white border-zinc-700',
        outline: 'bg-white text-zinc-700 border-zinc-300',
        success: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        amber: 'bg-amber-50 text-amber-800 border-amber-200',
        destructive: 'bg-rose-50 text-rose-800 border-rose-200',
    };

    const currentVariant = variants[variant] || variants.default;

    return (
        <span
            className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border ${currentVariant} ${className}`}
            {...props}
        >
            {children}
        </span>
    );
}
