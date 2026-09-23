import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { schedules, errors } = usePage().props;
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    return (
        <AppLayout>
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Shift Schedules ({schedules.length})</h1>
                        <p className="text-xs text-zinc-500 mt-1">Configure recurring practitioner shift timetables and slot durations</p>
                    </div>
                    <div className="flex space-x-2">
                        <Link
                            href="/admin/exceptions"
                            className="bg-white border border-zinc-200 text-zinc-800 font-semibold px-4 py-2 rounded text-xs hover:bg-zinc-50 transition-colors"
                        >
                            Manage Exceptions
                        </Link>
                        <Link
                            href="/admin/schedules/create"
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Add Schedule
                        </Link>
                    </div>
                </div>

                {errors.schedule && (
                    <div className="p-3 bg-rose-50 border border-rose-200 rounded text-rose-800 text-xs font-medium">
                        {errors.schedule}
                    </div>
                )}

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Practitioner</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Day & Hours</TableHead>
                            <TableHead>Slot Duration</TableHead>
                            <TableHead>Capacity</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {schedules.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell className="font-semibold text-zinc-900">{s.practitioner.full_name}</TableCell>
                                <TableCell className="text-zinc-700">{s.department.name}</TableCell>
                                <TableCell className="font-mono text-xs text-zinc-900">
                                    {days[s.weekday]} {s.start_time.slice(0, 5)}–{s.end_time.slice(0, 5)}
                                </TableCell>
                                <TableCell className="font-mono text-xs">{s.slot_duration_minutes} mins</TableCell>
                                <TableCell className="font-mono text-xs">{s.max_per_slot} max/slot</TableCell>
                                <TableCell>
                                    <Badge variant={s.is_active ? 'success' : 'destructive'}>
                                        {s.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-right space-x-2">
                                    <Link href={`/admin/schedules/${s.id}/edit`} className="text-xs font-semibold text-zinc-900 underline">
                                        Edit
                                    </Link>
                                    <Link href={`/admin/schedules/${s.id}`} method="delete" as="button" className="text-xs font-semibold text-rose-600 hover:underline">
                                        Delete
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
