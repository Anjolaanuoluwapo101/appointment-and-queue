import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AsyncButton from '../../../Components/AsyncButton';

export default function Index() {
    const { staff: initialStaff, status } = usePage().props;
    const [staffList, setStaffList] = useState(initialStaff ?? []);
    const [togglingId, setTogglingId] = useState(null);

    useEffect(() => {
        setStaffList(initialStaff ?? []);
    }, [initialStaff]);

    const toggleStatus = (staffId) => {
        setTogglingId(staffId);
        const previousList = [...staffList];

        // Optimistically mutate local state instantly (0ms delay)
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
                    // Revert state if backend call fails
                    setStaffList(previousList);
                },
            }
        );
    };

    return (
        <main className="p-8 font-sans max-w-5xl mx-auto">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-3xl font-bold text-gray-900">Staff Accounts</h1>
                <Link
                    href="/admin/staff/create"
                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-all shadow-sm active:scale-[0.98]"
                >
                    + New staff account
                </Link>
            </div>

            {status && (
                <p className="mb-6 p-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md text-sm font-medium">
                    {status}
                </p>
            )}

            <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <ul className="divide-y divide-gray-100">
                    {staffList.map((s) => (
                        <li key={s.id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div className="flex items-center space-x-4">
                                <div>
                                    <span className="font-semibold text-gray-900 block">{s.name}</span>
                                    <span className="text-sm text-gray-500">{s.email}</span>
                                </div>
                                <span className="px-2.5 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold uppercase tracking-wider">
                                    {s.role}
                                </span>
                                <span
                                    className={`px-2.5 py-0.5 rounded-full text-xs font-semibold transition-colors ${
                                        s.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                    }`}
                                >
                                    {s.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>

                            <div className="flex items-center space-x-3">
                                <Link
                                    href={`/admin/staff/${s.id}/edit`}
                                    className="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors"
                                >
                                    Edit
                                </Link>
                                <AsyncButton
                                    onClick={() => toggleStatus(s.id)}
                                    loading={togglingId === s.id}
                                    loadingText="Updating..."
                                    variant={s.is_active ? 'outline' : 'success'}
                                    className="text-xs py-1 px-3"
                                >
                                    {s.is_active ? 'Deactivate' : 'Reactivate'}
                                </AsyncButton>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </main>
    );
}
