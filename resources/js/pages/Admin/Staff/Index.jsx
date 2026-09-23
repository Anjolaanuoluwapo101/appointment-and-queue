import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AsyncButton from '../../../Components/AsyncButton';
import { Badge } from '../../../Components/Badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../Components/Table';
import AppLayout from '../../../Layouts/AppLayout';

export default function Index() {
    const { staff: initialStaff } = usePage().props;
    const [staffList, setStaffList] = useState(initialStaff ?? []);
    const [togglingId, setTogglingId] = useState(null);

    useEffect(() => {
        setStaffList(initialStaff ?? []);
    }, [initialStaff]);

    const toggleStatus = (staffId) => {
        setTogglingId(staffId);
        const previousList = [...staffList];

        setStaffList((prev) =>
            prev.map((s) => (s.id === staffId ? { ...s, is_active: !s.is_active } : s))
        );

        router.patch(
            `/admin/staff/${staffId}/toggle`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setTogglingId(null),
                onError: () => {
                    setStaffList(previousList);
                },
            }
        );
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-7xl mx-auto bg-white">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Staff Account Directory</h1>
                        <p className="text-xs text-zinc-500 mt-1">Manage hospital practitioners, receptionists, and system admins</p>
                    </div>
                    <Link
                        href="/admin/staff/create"
                        className="bg-zinc-900 hover:bg-zinc-800 text-white px-4 py-2 rounded font-semibold text-xs transition-colors shadow-sm"
                    >
                        + New Staff Account
                    </Link>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Staff Name</TableHead>
                            <TableHead>Email Address</TableHead>
                            <TableHead>System Role</TableHead>
                            <TableHead>Account Status</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {staffList.map((s) => (
                            <TableRow key={s.id}>
                                <TableCell className="font-semibold text-zinc-900">{s.name}</TableCell>
                                <TableCell className="text-zinc-500 font-mono text-xs">{s.email}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" className="uppercase font-mono text-[10px]">
                                        {s.role}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge variant={s.is_active ? 'success' : 'destructive'}>
                                        {s.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-right space-x-2">
                                    <Link
                                        href={`/admin/staff/${s.id}/edit`}
                                        className="text-xs font-semibold text-zinc-700 hover:text-zinc-900 transition-colors"
                                    >
                                        Edit
                                    </Link>
                                    <AsyncButton
                                        onClick={() => toggleStatus(s.id)}
                                        loading={togglingId === s.id}
                                        loadingText="Updating..."
                                        variant={s.is_active ? 'outline' : 'primary'}
                                        className="text-[11px] px-2.5 py-1 rounded"
                                    >
                                        {s.is_active ? 'Deactivate' : 'Reactivate'}
                                    </AsyncButton>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
