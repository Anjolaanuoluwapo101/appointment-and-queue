import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';
import AppLayout from '../../Layouts/AppLayout';

export default function AuditLog() {
    const { filters, entries } = usePage().props;
    const [filtering, setFiltering] = useState(false);
    const [exportingExcel, setExportingExcel] = useState(false);
    const [exportingPdf, setExportingPdf] = useState(false);
    const [logFilters, setLogFilters] = useState({
        action: filters.action ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    const executeFilter = (params = logFilters) => {
        setFiltering(true);
        router.get('/admin/audit-log', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setFiltering(false),
        });
    };

    const updateFilter = (field, value) => {
        const nextFilters = { ...logFilters, [field]: value };
        setLogFilters(nextFilters);
        executeFilter(nextFilters);
    };

    const handleExportExcel = () => {
        setExportingExcel(true);
        const params = new URLSearchParams({
            ...(logFilters.action ? { action: logFilters.action } : {}),
            ...(logFilters.from ? { from: logFilters.from } : {}),
            ...(logFilters.to ? { to: logFilters.to } : {}),
        });
        window.location.href = `/admin/audit-log/export?${params.toString()}`;
        setTimeout(() => setExportingExcel(false), 2000);
    };

    const handleExportPdf = () => {
        setExportingPdf(true);
        const params = new URLSearchParams({
            ...(logFilters.action ? { action: logFilters.action } : {}),
            ...(logFilters.from ? { from: logFilters.from } : {}),
            ...(logFilters.to ? { to: logFilters.to } : {}),
        });
        window.location.href = `/admin/audit-log/export-pdf?${params.toString()}`;
        setTimeout(() => setExportingPdf(false), 2000);
    };

    return (
        <AppLayout>
            <div className="max-w-6xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">System Audit Log</h1>
                    <div className="flex space-x-3">
                        <AsyncButton
                            onClick={handleExportExcel}
                            loading={exportingExcel}
                            loadingText="Exporting..."
                            variant="outline"
                        >
                            Export Excel
                        </AsyncButton>
                        <AsyncButton
                            onClick={handleExportPdf}
                            loading={exportingPdf}
                            loadingText="Generating PDF..."
                            variant="secondary"
                        >
                            Export PDF
                        </AsyncButton>
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl border border-zinc-200 shadow-sm">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label htmlFor="action_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                Action Name
                            </label>
                            <input
                                id="action_filter"
                                type="text"
                                value={logFilters.action}
                                onChange={(e) => updateFilter('action', e.target.value)}
                                placeholder="Filter by action..."
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                        </div>
                        <div>
                            <label htmlFor="from_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                From Date
                            </label>
                            <input
                                id="from_filter"
                                type="date"
                                value={logFilters.from}
                                onChange={(e) => updateFilter('from', e.target.value)}
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                        </div>
                        <div>
                            <label htmlFor="to_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                To Date
                            </label>
                            <input
                                id="to_filter"
                                type="date"
                                value={logFilters.to}
                                onChange={(e) => updateFilter('to', e.target.value)}
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                        </div>
                    </div>

                    <div className="mt-4 flex justify-end">
                        <AsyncButton
                            onClick={() => executeFilter()}
                            loading={filtering}
                            loadingText="Filtering..."
                            variant="primary"
                        >
                            Apply Filter
                        </AsyncButton>
                    </div>
                </div>

                <div className="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                    {entries.length === 0 ? (
                        <p className="p-6 text-sm text-zinc-500 italic">No audit log entries match the selected filters.</p>
                    ) : (
                        <ul className="divide-y divide-zinc-100">
                            {entries.map((e) => (
                                <li key={e.id} className="p-4 hover:bg-zinc-50 flex items-center justify-between text-sm">
                                    <div className="space-y-1">
                                        <div className="flex items-center space-x-2">
                                            <span className="font-semibold text-zinc-900">{e.user?.name ?? 'System'}</span>
                                            <span className="text-zinc-400">•</span>
                                            <span className="font-mono text-xs text-zinc-800 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200">{e.action}</span>
                                        </div>
                                        <p className="text-zinc-500 text-xs">
                                            {e.subject_type ? `${e.subject_type.split('\\').pop()} #${e.subject_id}` : 'General System Action'}
                                        </p>
                                    </div>
                                    <span className="text-xs text-zinc-400 font-mono">
                                        {e.created_at.slice(0, 19).replace('T', ' ')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
