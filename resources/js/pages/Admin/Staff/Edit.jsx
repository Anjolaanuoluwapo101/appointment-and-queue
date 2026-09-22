import { useForm, usePage } from '@inertiajs/react';
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
        <main className="p-12 font-sans max-w-lg">
            <h1 className="text-3xl font-bold mb-6">Edit Staff Account</h1>
            <StaffForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                isCreate={false}
            />
        </main>
    );
}
