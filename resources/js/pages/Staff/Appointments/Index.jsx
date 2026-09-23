import { Link, router, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { appointments, filters, departments } = usePage().props;

    const handleFilterChange = (field, value) => {
        router.get(
            '/staff/appointments',
            { ...filters, [field]: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-7xl mx-auto bg-white py-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Hospital Appointments Index</h1>
                        <p className="text-xs text-zinc-500 mt-1">Manage scheduled, cleared, and completed consultations</p>
                    </div>
                    <Link
                        href="/staff/appointments/create"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                    >
                        + Book on Behalf of Patient
                    </Link>
                </div>

                {/* Filters Strip */}
                <div className="bg-white p-4 border border-zinc-200 rounded-lg grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label className="block text-xs font-semibold text-zinc-700 mb-1">Date</label>
                        <input
                            type="date"
                            defaultValue={filters.date ?? new Date().toISOString().slice(0, 10)}
                            onChange={(e) => handleFilterChange('date', e.target.value)}
                            className="w-full bg-white border border-zinc-300 rounded px-3 py-1.5 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-zinc-700 mb-1">Department</label>
                        <select
                            defaultValue={filters.department_id ?? ''}
                            onChange={(e) => handleFilterChange('department_id', e.target.value)}
                            className="w-full bg-white border border-zinc-300 rounded px-3 py-1.5 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                        >
                            <option value="">All Departments</option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>{d.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-zinc-700 mb-1">Status</label>
                        <select
                            defaultValue={filters.status ?? ''}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                            className="w-full bg-white border border-zinc-300 rounded px-3 py-1.5 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                        >
                            <option value="">All Statuses</option>
                            {['scheduled', 'pending_clearance', 'cleared', 'in_queue', 'completed', 'cancelled', 'no_show'].map((s) => (
                                <option key={s} value={s}>{s.replace('_', ' ')}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Time</TableHead>
                            <TableHead>Hospital No.</TableHead>
                            <TableHead>Patient Name</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Practitioner</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Payment</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {appointments.map((a) => (
                            <TableRow key={a.id}>
                                <TableCell className="font-mono font-bold text-zinc-900">{a.scheduled_at.slice(11, 16)}</TableCell>
                                <TableCell className="font-mono text-xs font-semibold text-zinc-900">{a.patient.patient_number || `PAT-#${a.patient.id}`}</TableCell>
                                <TableCell className="font-semibold text-zinc-900">{a.patient.full_name}</TableCell>
                                <TableCell className="font-mono text-zinc-600">{a.patient.phone}</TableCell>
                                <TableCell className="text-zinc-700">{a.department.name}</TableCell>
                                <TableCell className="text-zinc-700">{a.practitioner?.full_name ?? 'Unassigned'}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" className="uppercase font-mono text-[10px]">{a.status}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={a.payment_status === 'paid' ? 'success' : 'amber'}>{a.payment_status}</Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <Link href={`/staff/appointments/${a.id}`} className="text-xs font-semibold text-zinc-900 underline">
                                        View →
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
