import { useForm, usePage } from '@inertiajs/react';
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
        <main className="p-12 font-sans max-w-lg">
            <h1 className="text-3xl font-bold mb-6">New Staff Account</h1>
            <StaffForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                practitioners={practitioners}
                isCreate={true}
            />
        </main>
    );
}
