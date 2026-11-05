import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';
import AppLayout from '../../Layouts/AppLayout';

export default function NotificationLogs() {
    const { filters, logs } = usePage().props;
    const [filtering, setFiltering] = useState(false);
    const [logFilters, setLogFilters] = useState({
        status: filters.status ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
    });

    const executeFilter = (params = logFilters) => {
        setFiltering(true);
        router.get('/admin/notification-logs', params, {
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

    const inputClass =
        'w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm';

    return (
        <AppLayout>
            <div className="max-w-6xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Notification Logs</h1>

                <div className="bg-white p-6 rounded-xl border border-zinc-200 shadow-sm">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label htmlFor="status_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                Status
                            </label>
                            <select
                                id="status_filter"
                                value={logFilters.status}
                                onChange={(e) => updateFilter('status', e.target.value)}
                                className={inputClass}
                            >
                                <option value="">All Statuses</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                            </select>
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
                                className={inputClass}
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
                                className={inputClass}
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
                    {logs.length === 0 ? (
                        <p className="p-6 text-sm text-zinc-500 italic">No notification delivery logs match the selected criteria.</p>
                    ) : (
                        <ul className="divide-y divide-zinc-100">
                            {logs.map((log) => (
                                <li key={log.id} className="p-4 hover:bg-zinc-50 flex items-center justify-between text-sm">
                                    <div className="space-y-1">
                                        <div className="flex items-center space-x-2">
                                            <span className="font-semibold text-zinc-900">{log.recipient}</span>
                                            <span className="text-zinc-400">•</span>
                                            <span className="text-xs uppercase bg-zinc-100 px-2 py-0.5 rounded font-mono text-zinc-600 border border-zinc-200">{log.channel}</span>
                                            <span
                                                className={`px-2 py-0.5 rounded text-xs font-semibold uppercase ${
                                                    log.status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                                }`}
                                            >
                                                {log.status}
                                            </span>
                                        </div>
                                        <p className="text-zinc-600 text-xs font-medium">{log.subject}</p>
                                        {log.error && <p className="text-rose-600 text-xs font-mono">{log.error}</p>}
                                    </div>
                                    <span className="text-xs text-zinc-400 font-mono">
                                        {log.created_at.slice(0, 19).replace('T', ' ')}
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
