import React from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import DepartmentForm from './Form';

export default function Edit() {
    const { department } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        name: department.name ?? '',
        queue_prefix: department.queue_prefix ?? '',
        room_label: department.room_label ?? '',
        payment_mode: department.payment_mode ?? 'allow_both',
        base_fee_kobo: department.base_fee_kobo ?? 0,
        is_active: department.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/departments/${department.id}`);
    };

    return (
        <AppLayout activeRoute="admin.departments">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">Edit Department</h1>
                        <p className="text-xs text-zinc-500 mt-1">Update consultation fees, room location, or queue prefix for {department.name}.</p>
                    </div>
                    <Link
                        href="/admin/departments"
                        className="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none"
                    >
                        Back to Departments
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Department Configuration</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DepartmentForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            department={department}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
