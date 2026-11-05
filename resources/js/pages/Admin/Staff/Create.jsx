import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import StaffForm from './Form';

export default function Create() {
    const { practitioners } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        role: 'receptionist',
        practitioner_id: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/staff');
    };

    return (
        <AppLayout>
            <div className="max-w-2xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold tracking-tight text-zinc-900">New Staff Account</h1>
                <div className="bg-white p-6 rounded-xl border border-zinc-200 shadow-sm">
                    <StaffForm
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        onSubmit={submit}
                        practitioners={practitioners}
                        isCreate={true}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
