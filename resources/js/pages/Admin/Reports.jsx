import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';

export default function Reports() {
    const { filters, report, departments, practitioners } = usePage().props;
    const [running, setRunning] = useState(false);
    const [exportingExcel, setExportingExcel] = useState(false);
    const [exportingPdf, setExportingPdf] = useState(false);
    const [formFilters, setFormFilters] = useState({
        from: filters.from ?? '',
        to: filters.to ?? '',
        department_id: filters.department_id ?? '',
        practitioner_id: filters.practitioner_id ?? '',
    });

    const updateFilter = (field, value) => {
        const nextFilters = { ...formFilters, [field]: value };
        setFormFilters(nextFilters);
        runReport(nextFilters);
    };

    const runReport = (params = formFilters) => {
        setRunning(true);
        router.get('/admin/reports', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setRunning(false),
        });
    };

    const handleExportExcel = () => {
        setExportingExcel(true);
        const params = new URLSearchParams({
            from: formFilters.from,
            to: formFilters.to,
            ...(formFilters.department_id ? { department_id: formFilters.department_id } : {}),
            ...(formFilters.practitioner_id ? { practitioner_id: formFilters.practitioner_id } : {}),
        });
        window.location.href = `/admin/reports/export?${params.toString()}`;
        setTimeout(() => setExportingExcel(false), 2000);
    };

    const handleExportPdf = () => {
        setExportingPdf(true);
        const params = new URLSearchParams({
            from: formFilters.from,
            to: formFilters.to,
            ...(formFilters.department_id ? { department_id: formFilters.department_id } : {}),
            ...(formFilters.practitioner_id ? { practitioner_id: formFilters.practitioner_id } : {}),
        });
        window.location.href = `/admin/reports/export-pdf?${params.toString()}`;
        setTimeout(() => setExportingPdf(false), 2000);
    };

    const group = (title, obj) => (
        <section className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 className="text-xl font-bold text-gray-800 mb-4">{title}</h2>
            <ul className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {Object.entries(obj ?? {}).map(([k, v]) => (
                    <li key={k} className="p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm">
                        <span className="font-medium text-gray-500 block uppercase text-xs">{k.replace('_', ' ')}</span>
                        <span className="font-bold text-gray-900 text-base">{typeof v === 'object' ? JSON.stringify(v) : String(v)}</span>
                    </li>
                ))}
            </ul>
        </section>
    );

    return (
        <main className="p-8 font-sans max-w-6xl mx-auto space-y-8">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
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
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            type="date"
                            value={formFilters.from}
                            onChange={(e) => updateFilter('from', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            type="date"
                            value={formFilters.to}
                            onChange={(e) => updateFilter('to', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select
                            value={formFilters.department_id}
                            onChange={(e) => updateFilter('department_id', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        >
                            <option value="">All Departments</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Practitioner</label>
                        <select
                            value={formFilters.practitioner_id}
                            onChange={(e) => updateFilter('practitioner_id', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        >
                            <option value="">All Practitioners</option>
                            {practitioners.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.full_name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="mt-4 flex justify-end">
                    <AsyncButton
                        onClick={() => runReport()}
                        loading={running}
                        loadingText="Running Report..."
                        variant="primary"
                    >
                        Refresh Report
                    </AsyncButton>
                </div>
            </div>

            {group('Appointments', report.appointments)}
            {group('Queue', report.queue)}
            {group('Operational', report.operational)}
            {group('Payments', report.payments)}
            {group('Patients', report.patients)}
            {group('Staff', report.staff)}
        </main>
    );
}
