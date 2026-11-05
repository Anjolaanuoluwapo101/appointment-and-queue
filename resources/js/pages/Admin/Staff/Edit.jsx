import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import StaffForm from './Form';

export default function Edit() {
    const { staff } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        name: staff.name ?? '',
        email: staff.email ?? '',
        role: staff.role ?? 'receptionist',
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/staff/${staff.id}`);
    };

    return (
        <AppLayout>
            <div className="max-w-2xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Edit Staff Account</h1>
                <div className="bg-white p-6 rounded-xl border border-zinc-200 shadow-sm">
                    <StaffForm
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        isCreate={false}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
