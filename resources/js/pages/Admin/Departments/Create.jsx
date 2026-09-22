import { useForm } from '@inertiajs/react';
import DepartmentForm from './Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        queue_prefix: '',
        room_label: '',
        payment_mode: 'allow_both',
        base_fee_kobo: 0,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/departments');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>New Department</h1>
            <DepartmentForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} />
        </main>
    );
}
