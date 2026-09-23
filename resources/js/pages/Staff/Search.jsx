import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../Components/Card';
import AsyncButton from '../../Components/AsyncButton';
import { Badge } from '../../Components/Badge';

export default function Search() {
    const { filters, patients, appointments, queue_entries, departments, practitioners } = usePage().props;
    const [searching, setSearching] = useState(false);
    const [searchForm, setSearchForm] = useState({
        q: filters.q ?? '',
        department_id: filters.department_id ?? '',
        practitioner_id: filters.practitioner_id ?? '',
        appointment_status: filters.appointment_status ?? '',
        date: filters.date ?? '',
    });

    const updateFilter = (field, value) => {
        const nextForm = { ...searchForm, [field]: value };
        setSearchForm(nextForm);
        executeAsyncSearch(nextForm);
    };

    const executeAsyncSearch = (formData = searchForm) => {
        setSearching(true);
        router.get(
            '/staff/search',
            formData,
            {
                preserveState: true,
                preserveScroll: true,
                only: ['filters', 'patients', 'appointments', 'queue_entries'],
                onFinish: () => setSearching(false),
            }
        );
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        executeAsyncSearch();
    };

    return (
        <AppLayout activeRoute="staff.search">
            <div className="space-y-6 max-w-5xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Staff Search & Directory Lookup</h1>
                    <p className="text-xs text-zinc-500 mt-1">Cross-lookup patients by name, phone, hospital no., queue token, or appointment ID.</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Search Criteria & Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-zinc-700 mb-1">
                                    Search Term (Name, Phone, Hospital No., Appointment #, Queue Token)
                                </label>
                                <input
                                    type="text"
                                    value={searchForm.q}
                                    onChange={(e) => setSearchForm({ ...searchForm, q: e.target.value })}
                                    placeholder="e.g. PAT-2026-00001, Jane Doe, 08012345678, or #42..."
                                    className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm font-mono"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-zinc-700 mb-1">Department</label>
                                    <select
                                        value={searchForm.department_id}
                                        onChange={(e) => updateFilter('department_id', e.target.value)}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
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
                                    <label className="block text-xs font-semibold text-zinc-700 mb-1">Practitioner</label>
                                    <select
                                        value={searchForm.practitioner_id}
                                        onChange={(e) => updateFilter('practitioner_id', e.target.value)}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="">All Practitioners</option>
                                        {practitioners.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.full_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-zinc-700 mb-1">Appointment Status</label>
                                    <select
                                        value={searchForm.appointment_status}
                                        onChange={(e) => updateFilter('appointment_status', e.target.value)}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    >
                                        <option value="">Any Status</option>
                                        {['scheduled', 'pending_clearance', 'cleared', 'in_queue', 'completed', 'cancelled', 'no_show'].map((s) => (
                                            <option key={s} value={s}>
                                                {s.replace('_', ' ')}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-zinc-700 mb-1">Date</label>
                                    <input
                                        type="date"
                                        value={searchForm.date}
                                        onChange={(e) => updateFilter('date', e.target.value)}
                                        className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                    />
                                </div>
                            </div>

                            <div className="pt-1 flex justify-end">
                                <AsyncButton
                                    type="submit"
                                    loading={searching}
                                    loadingText="Searching records..."
                                    variant="primary"
                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 px-5 rounded-md text-xs shadow-sm"
                                >
                                    Filter & Search
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {/* Patients Section */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold tracking-tight text-zinc-900">Matching Patients ({patients.length})</h2>
                        </div>
                        {patients.length === 0 ? (
                            <div className="p-4 rounded-md border border-zinc-200 bg-white text-xs text-zinc-500 italic">No matching patient records.</div>
                        ) : (
                            <div className="divide-y divide-zinc-100 bg-white rounded-md border border-zinc-200 overflow-hidden shadow-sm">
                                {patients.map((p) => (
                                    <div key={p.id} className="p-3.5 hover:bg-zinc-50/50 flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <Badge variant="outline" className="font-mono text-xs">
                                                {p.patient_number || `PAT-#${p.id}`}
                                            </Badge>
                                            <div>
                                                <span className="font-semibold text-xs text-zinc-900 block">{p.full_name}</span>
                                                <span className="text-xs text-zinc-500">{p.phone} · {p.email || 'No email'}</span>
                                            </div>
                                        </div>
                                        <Link href={`/staff/patients/${p.id}`} className="text-xs font-semibold text-zinc-900 underline">
                                            View Patient File →
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Appointments Section */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold tracking-tight text-zinc-900">Matching Appointments ({appointments.length})</h2>
                        </div>
                        {appointments.length === 0 ? (
                            <div className="p-4 rounded-md border border-zinc-200 bg-white text-xs text-zinc-500 italic">No matching appointments found.</div>
                        ) : (
                            <div className="divide-y divide-zinc-100 bg-white rounded-md border border-zinc-200 overflow-hidden shadow-sm">
                                {appointments.map((a) => (
                                    <div key={a.id} className="p-3.5 hover:bg-zinc-50/50 flex items-center justify-between">
                                        <div>
                                            <div className="flex items-center space-x-2">
                                                <Link href={`/staff/appointments/${a.id}`} className="font-semibold text-xs text-zinc-900 hover:underline">
                                                    Appointment #{a.id} · {a.patient?.full_name}
                                                </Link>
                                                {a.patient?.patient_number && (
                                                    <span className="text-xs font-mono text-zinc-400">({a.patient.patient_number})</span>
                                                )}
                                            </div>
                                            <span className="text-xs text-zinc-500 block">{a.department?.name}</span>
                                        </div>
                                        <Badge variant="secondary" className="uppercase text-[10px]">
                                            {a.status}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Queue Entries Section */}
                    <section className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold tracking-tight text-zinc-900">Matching Queue Entries ({queue_entries.length})</h2>
                        </div>
                        {queue_entries.length === 0 ? (
                            <div className="p-4 rounded-md border border-zinc-200 bg-white text-xs text-zinc-500 italic">No matching live queue tokens found.</div>
                        ) : (
                            <div className="divide-y divide-zinc-100 bg-white rounded-md border border-zinc-200 overflow-hidden shadow-sm">
                                {queue_entries.map((e) => (
                                    <div key={e.id} className="p-3.5 hover:bg-zinc-50/50 flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <span className="font-mono font-bold text-sm text-zinc-900">{e.queue_number}</span>
                                            <div>
                                                <div className="flex items-center space-x-2">
                                                    <span className="font-medium text-xs text-zinc-900">{e.patient?.full_name}</span>
                                                    {e.patient?.patient_number && (
                                                        <span className="text-xs font-mono text-zinc-400">({e.patient.patient_number})</span>
                                                    )}
                                                </div>
                                                <span className="text-xs text-zinc-500 block">{e.department?.name}</span>
                                            </div>
                                        </div>
                                        <Badge variant="outline" className="uppercase text-[10px]">
                                            {e.status}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
