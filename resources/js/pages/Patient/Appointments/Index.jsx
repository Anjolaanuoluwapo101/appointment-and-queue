import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { upcoming, history } = usePage().props;

    const renderTable = (list, title) => (
        <div className="space-y-3">
            <h2 className="text-sm font-bold text-zinc-900 uppercase tracking-tight">{title} ({list.length})</h2>
            {list.length === 0 ? (
                <div className="bg-white border border-zinc-200 rounded-lg p-6 text-center text-xs text-zinc-500">
                    No appointments in this category.
                </div>
            ) : (
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Department</TableHead>
                            <TableHead>Practitioner</TableHead>
                            <TableHead>Scheduled Time</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Payment</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {list.map((a) => (
                            <TableRow key={a.id}>
                                <TableCell className="font-semibold text-zinc-900">{a.department.name}</TableCell>
                                <TableCell className="text-zinc-700">{a.practitioner?.full_name ?? 'Any Practitioner'}</TableCell>
                                <TableCell className="font-mono text-zinc-600">{a.scheduled_at.slice(0, 16).replace('T', ' ')}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" className="uppercase font-mono text-[10px]">{a.status}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={a.payment_status === 'paid' ? 'success' : 'amber'}>{a.payment_status}</Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <Link href={`/patient/appointments/${a.id}`} className="text-xs font-semibold text-zinc-900 underline">
                                        View Details & Ticket →
                                    </Link>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </div>
    );

    return (
        <AppLayout>
            <div className="space-y-8 max-w-6xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">My Appointments History</h1>
                        <p className="text-xs text-zinc-500 mt-1">Track upcoming and past medical consultations</p>
                    </div>
                    <Link
                        href="/patient/book/1/practitioners"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                    >
                        + Book New Appointment
                    </Link>
                </div>

                {renderTable(upcoming, 'Upcoming Appointments')}
                {renderTable(history, 'Appointment History')}
            </div>
        </AppLayout>
    );
}
