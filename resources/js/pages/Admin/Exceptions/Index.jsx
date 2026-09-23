import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { exceptions } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Schedule Exceptions ({exceptions.length})</h1>
                        <p className="text-xs text-zinc-500 mt-1">Manage practitioner leave dates and shift override exceptions</p>
                    </div>
                    <div className="flex space-x-2">
                        <Link
                            href="/admin/schedules"
                            className="bg-white border border-zinc-200 text-zinc-800 font-semibold px-4 py-2 rounded text-xs hover:bg-zinc-50 transition-colors"
                        >
                            Regular Schedules
                        </Link>
                        <Link
                            href="/admin/exceptions/create"
                            className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                        >
                            + Add Exception
                        </Link>
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Practitioner</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Exception Type</TableHead>
                            <TableHead>Reason</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {exceptions.map((x) => (
                            <TableRow key={x.id}>
                                <TableCell className="font-mono font-bold text-zinc-900">{x.date.slice(0, 10)}</TableCell>
                                <TableCell className="font-semibold text-zinc-900">{x.practitioner.full_name}</TableCell>
                                <TableCell className="text-zinc-600">{x.department?.name ?? 'All Departments'}</TableCell>
                                <TableCell>
                                    <Badge variant="amber" className="uppercase text-[10px] font-mono">{x.type}</Badge>
                                </TableCell>
                                <TableCell className="text-zinc-500 text-xs">{x.reason ?? '—'}</TableCell>
                                <TableCell className="text-right">
                                    <Link href={`/admin/exceptions/${x.id}`} method="delete" as="button" className="text-xs font-semibold text-rose-600 hover:underline">
                                        Remove
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
