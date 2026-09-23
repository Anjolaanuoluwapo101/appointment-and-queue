import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { practitioners } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-6xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Hospital Practitioners ({practitioners.length})</h1>
                        <p className="text-xs text-zinc-500 mt-1">Manage clinical doctors, specialists, and department assignments</p>
                    </div>
                    <Link
                        href="/admin/practitioners/create"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm"
                    >
                        + Add Practitioner
                    </Link>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Practitioner Name</TableHead>
                            <TableHead>Specialisation</TableHead>
                            <TableHead>Departments</TableHead>
                            <TableHead>Availability</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {practitioners.map((p) => (
                            <TableRow key={p.id}>
                                <TableCell className="font-semibold text-zinc-900">{p.full_name}</TableCell>
                                <TableCell className="text-zinc-700 font-medium">{p.specialisation}</TableCell>
                                <TableCell className="text-zinc-600 text-xs">
                                    {p.departments.map((d) => d.name).join(', ')}
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" className="capitalize text-[11px]">{p.availability}</Badge>
                                </TableCell>
                                <TableCell className="text-right">
                                    <Link href={`/admin/practitioners/${p.id}/edit`} className="text-xs font-semibold text-zinc-900 underline">
                                        Edit
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
