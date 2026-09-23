import React from 'react';

export function Table({ children, className = '', ...props }) {
    return (
        <div className="border border-zinc-200 rounded-lg overflow-hidden bg-white shadow-sm w-full">
            <div className="overflow-x-auto w-full">
                <table className={`w-full text-left text-xs border-collapse min-w-[600px] ${className}`} {...props}>
                    {children}
                </table>
            </div>
        </div>
    );
}

export function TableHeader({ children, className = '', ...props }) {
    return (
        <thead className={`border-b border-zinc-200 bg-white text-[11px] font-semibold text-zinc-500 uppercase tracking-wider ${className}`} {...props}>
            {children}
        </thead>
    );
}

export function TableBody({ children, className = '', ...props }) {
    return (
        <tbody className={`divide-y divide-zinc-100 text-zinc-800 bg-white ${className}`} {...props}>
            {children}
        </tbody>
    );
}

export function TableRow({ children, className = '', ...props }) {
    return (
        <tr className={`hover:bg-zinc-50/80 transition-colors ${className}`} {...props}>
            {children}
        </tr>
    );
}

export function TableHead({ children, className = '', ...props }) {
    return (
        <th className={`py-3 px-4 font-semibold ${className}`} {...props}>
            {children}
        </th>
    );
}

export function TableCell({ children, className = '', ...props }) {
    return (
        <td className={`py-3 px-4 ${className}`} {...props}>
            {children}
        </td>
    );
}
