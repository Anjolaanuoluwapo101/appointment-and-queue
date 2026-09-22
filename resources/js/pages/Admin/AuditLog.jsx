import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';

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

    const updateFilter = (field, value) => {
        const nextFilters = { ...logFilters, [field]: value };
        setLogFilters(nextFilters);
        executeFilter(nextFilters);
    };

    const executeFilter = (params = logFilters) => {
        setFiltering(true);
        router.get('/admin/audit-log', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setFiltering(false),
        });
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
        <main className="p-8 font-sans max-w-6xl mx-auto space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold text-gray-900">System Audit Log</h1>
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

            <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Action Name</label>
                        <input
                            type="text"
                            value={logFilters.action}
                            onChange={(e) => setLogFilters({ ...logFilters, action: e.target.value })}
                            placeholder="Filter by action..."
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            type="date"
                            value={logFilters.from}
                            onChange={(e) => updateFilter('from', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            type="date"
                            value={logFilters.to}
                            onChange={(e) => updateFilter('to', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
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

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                {entries.length === 0 ? (
                    <p className="p-6 text-sm text-gray-500 italic">No audit log entries match the selected filters.</p>
                ) : (
                    <ul className="divide-y divide-gray-100">
                        {entries.map((e) => (
                            <li key={e.id} className="p-4 hover:bg-gray-50 flex items-center justify-between text-sm">
                                <div className="space-y-1">
                                    <div className="flex items-center space-x-2">
                                        <span className="font-semibold text-gray-900">{e.user?.name ?? 'System'}</span>
                                        <span className="text-gray-400">•</span>
                                        <span className="font-mono text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">{e.action}</span>
                                    </div>
                                    <p className="text-gray-500 text-xs">
                                        {e.subject_type ? `${e.subject_type.split('\\').pop()} #${e.subject_id}` : 'General System Action'}
                                    </p>
                                </div>
                                <span className="text-xs text-gray-400 font-mono">
                                    {e.created_at.slice(0, 19).replace('T', ' ')}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </main>
    );
}
