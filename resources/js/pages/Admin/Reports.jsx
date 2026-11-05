import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';
import AppLayout from '../../Layouts/AppLayout';

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

    const runReport = (params = formFilters) => {
        setRunning(true);
        router.get('/admin/reports', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setRunning(false),
        });
    };

    const updateFilter = (field, value) => {
        const nextFilters = { ...formFilters, [field]: value };
        setFormFilters(nextFilters);
        runReport(nextFilters);
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

    const label = (k) => k.replaceAll('_', ' ');

    const value = (v) => {
        if (v !== null && typeof v === 'object') {
            const rows = Object.entries(v);
            if (rows.length === 0) {
                return <span className="text-zinc-400">—</span>;
            }
            return (
                <ul className="mt-1 space-y-1">
                    {rows.map(([sk, sv]) => (
                        <li key={sk} className="flex justify-between gap-2 text-sm">
                            <span className="text-zinc-500">{label(String(sk))}</span>
                            <span className="font-semibold text-zinc-800">{String(sv)}</span>
                        </li>
                    ))}
                </ul>
            );
        }
        return <span className="font-bold text-zinc-900 text-base">{String(v)}</span>;
    };

    const group = (title, obj) => (
        <section className="bg-white p-6 rounded-xl border border-zinc-200 shadow-sm">
            <h2 className="text-xl font-bold text-zinc-800 mb-4">{title}</h2>
            <ul className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {Object.entries(obj ?? {}).map(([k, v]) => (
                    <li key={k} className="p-3 bg-zinc-50 rounded-lg border border-zinc-100 text-sm">
                        <span className="font-medium text-zinc-500 block uppercase text-xs">{label(k)}</span>
                        {value(v)}
                    </li>
                ))}
            </ul>
        </section>
    );

    const inputClass =
        'w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm';

    return (
        <AppLayout>
            <div className="max-w-6xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Analytics &amp; Reports</h1>
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
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label htmlFor="from_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                From Date
                            </label>
                            <input
                                id="from_filter"
                                type="date"
                                value={formFilters.from}
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
                                value={formFilters.to}
                                onChange={(e) => updateFilter('to', e.target.value)}
                                className={inputClass}
                            />
                        </div>
                        <div>
                            <label htmlFor="department_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                Department
                            </label>
                            <select
                                id="department_filter"
                                value={formFilters.department_id}
                                onChange={(e) => updateFilter('department_id', e.target.value)}
                                className={inputClass}
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
                            <label htmlFor="practitioner_filter" className="block text-xs font-semibold text-zinc-700 mb-1">
                                Practitioner
                            </label>
                            <select
                                id="practitioner_filter"
                                value={formFilters.practitioner_id}
                                onChange={(e) => updateFilter('practitioner_id', e.target.value)}
                                className={inputClass}
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
            </div>
        </AppLayout>
    );
}
