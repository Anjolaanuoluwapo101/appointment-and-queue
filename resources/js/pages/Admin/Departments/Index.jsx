import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { departments, errors } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Hospital Departments ({departments.length})</h1>
                        <p className="text-xs text-zinc-500 mt-1">Manage clinical units, queue prefixes, base fees, and payment policies</p>
                    </div>
                    <Link
                        href="/admin/departments/create"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                    >
                        + Add Department
                    </Link>
                </div>

                {errors.department && (
                    <div className="p-3 bg-rose-50 border border-rose-200 rounded text-rose-800 text-xs font-medium">
                        {errors.department}
                    </div>
                )}

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Department Name</TableHead>
                            <TableHead>Prefix</TableHead>
                            <TableHead>Payment Policy</TableHead>
                            <TableHead>Base Fee</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {departments.map((d) => (
                            <TableRow key={d.id}>
                                <TableCell className="font-semibold text-zinc-900">{d.name}</TableCell>
                                <TableCell className="font-mono font-bold text-zinc-900">{d.queue_prefix}</TableCell>
                                <TableCell className="capitalize text-zinc-600 font-mono text-xs">{d.payment_mode.replace('_', ' ')}</TableCell>
                                <TableCell className="font-mono font-medium text-zinc-900">₦{(d.base_fee_kobo / 100).toLocaleString()}</TableCell>
                                <TableCell>
                                    <Badge variant={d.is_active ? 'success' : 'destructive'}>
                                        {d.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-right space-x-2">
                                    <Link href={`/admin/departments/${d.id}/edit`} className="text-xs font-semibold text-zinc-900 underline">
                                        Edit
                                    </Link>
                                    <Link href={`/admin/departments/${d.id}`} method="delete" as="button" className="text-xs font-semibold text-rose-600 hover:underline">
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
