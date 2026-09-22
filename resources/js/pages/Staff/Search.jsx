import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AsyncButton from '../../Components/AsyncButton';

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
        <main className="p-8 font-sans max-w-5xl mx-auto">
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Staff Search</h1>

            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm mb-8 space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div className="col-span-1 md:col-span-2 lg:col-span-3">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Search Query (Name, Phone, ID, Queue Number)
                        </label>
                        <input
                            type="text"
                            value={searchForm.q}
                            onChange={(e) => setSearchForm({ ...searchForm, q: e.target.value })}
                            placeholder="Type name, phone, or queue number..."
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select
                            value={searchForm.department_id}
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
                            value={searchForm.practitioner_id}
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

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Appointment Status</label>
                        <select
                            value={searchForm.appointment_status}
                            onChange={(e) => updateFilter('appointment_status', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
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
                        <label className="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input
                            type="date"
                            value={searchForm.date}
                            onChange={(e) => updateFilter('date', e.target.value)}
                            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-sm"
                        />
                    </div>
                </div>

                <div className="pt-2 flex justify-end">
                    <AsyncButton
                        type="submit"
                        loading={searching}
                        loadingText="Searching..."
                        variant="primary"
                    >
                        Search Results
                    </AsyncButton>
                </div>
            </form>

            <div className="space-y-8">
                <section>
                    <h2 className="text-xl font-bold text-gray-900 mb-3">Patients ({patients.length})</h2>
                    {patients.length === 0 ? (
                        <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-gray-200">No matching patients found.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                            {patients.map((p) => (
                                <li key={p.id} className="p-4 hover:bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <span className="font-semibold text-gray-900 block">{p.full_name}</span>
                                        <span className="text-sm text-gray-500">{p.phone} · {p.email}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section>
                    <h2 className="text-xl font-bold text-gray-900 mb-3">Appointments ({appointments.length})</h2>
                    {appointments.length === 0 ? (
                        <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-gray-200">No matching appointments found.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                            {appointments.map((a) => (
                                <li key={a.id} className="p-4 hover:bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <Link href={`/staff/appointments/${a.id}`} className="font-semibold text-blue-600 hover:underline">
                                            #{a.id} · {a.patient?.full_name}
                                        </Link>
                                        <span className="text-sm text-gray-500 block">{a.department?.name}</span>
                                    </div>
                                    <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 uppercase">
                                        {a.status}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section>
                    <h2 className="text-xl font-bold text-gray-900 mb-3">Queue Entries ({queue_entries.length})</h2>
                    {queue_entries.length === 0 ? (
                        <p className="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-md border border-gray-200">No matching queue entries found.</p>
                    ) : (
                        <ul className="divide-y divide-gray-100 bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                            {queue_entries.map((e) => (
                                <li key={e.id} className="p-4 hover:bg-gray-50 flex items-center justify-between">
                                    <div>
                                        <span className="font-bold text-gray-900 text-lg mr-2">#{e.queue_number}</span>
                                        <span className="font-medium text-gray-800">{e.patient?.full_name}</span>
                                        <span className="text-sm text-gray-500 block">{e.department?.name}</span>
                                    </div>
                                    <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 uppercase">
                                        {e.status}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </main>
    );
}
