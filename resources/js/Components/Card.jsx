import React from 'react';

export function Card({ children, className = '', ...props }) {
    return (
        <div className={`bg-white border border-zinc-200 rounded-lg p-5 shadow-sm ${className}`} {...props}>
            {children}
        </div>
    );
}

export function CardHeader({ children, className = '', ...props }) {
    return (
        <div className={`border-b border-zinc-100 pb-3 mb-4 flex items-center justify-between ${className}`} {...props}>
            {children}
        </div>
    );
}

export function CardTitle({ children, className = '', ...props }) {
    return (
        <h3 className={`text-base font-bold tracking-tight text-zinc-900 ${className}`} {...props}>
            {children}
        </h3>
    );
}

export function CardContent({ children, className = '', ...props }) {
    return (
        <div className={`space-y-4 ${className}`} {...props}>
            {children}
        </div>
    );
}

export default Card;
