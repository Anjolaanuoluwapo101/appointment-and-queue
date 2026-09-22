import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';

export default function NotificationLogs() {
    const { filters, logs } = usePage().props;
    const [filtering, setFiltering] = useState(false);
    const [logFilters, setLogFilters] = useState({
        status: filters.status ?? '',
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
        router.get('/admin/notification-logs', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setFiltering(false),
        });
    };

    return (
        <main className="p-8 font-sans max-w-6xl mx-auto space-y-6">
            <h1 className="text-3xl font-bold text-gray-900">Notification Logs</h1>

            <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            value={logFilters.status}
                            onChange={(e) => updateFilter('status', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                        </select>
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
                {logs.length === 0 ? (
                    <p className="p-6 text-sm text-gray-500 italic">No notification delivery logs match the selected criteria.</p>
                ) : (
                    <ul className="divide-y divide-gray-100">
                        {logs.map((log) => (
                            <li key={log.id} className="p-4 hover:bg-gray-50 flex items-center justify-between text-sm">
                                <div className="space-y-1">
                                    <div className="flex items-center space-x-2">
                                        <span className="font-semibold text-gray-900">{log.recipient}</span>
                                        <span className="text-gray-400">•</span>
                                        <span className="text-xs uppercase bg-gray-100 px-2 py-0.5 rounded font-mono text-gray-600">{log.channel}</span>
                                        <span
                                            className={`px-2 py-0.5 rounded text-xs font-semibold uppercase ${
                                                log.status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                            }`}
                                        >
                                            {log.status}
                                        </span>
                                    </div>
                                    <p className="text-gray-600 text-xs font-medium">{log.subject}</p>
                                    {log.error && <p className="text-rose-600 text-xs font-mono">{log.error}</p>}
                                </div>
                                <span className="text-xs text-gray-400 font-mono">
                                    {log.created_at.slice(0, 19).replace('T', ' ')}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </main>
    );
}
