import { useForm, usePage } from '@inertiajs/react';
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
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Edit Department</h1>
            <DepartmentForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                department={department}
            />
        </main>
    );
}
