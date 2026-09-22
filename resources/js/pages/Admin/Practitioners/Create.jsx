import { useForm, usePage } from '@inertiajs/react';
import PractitionerForm from './Form';

export default function Create() {
    const { departments } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        specialisation: '',
        qualifications: '',
        bio: '',
        internal_contact: '',
        availability: 'active',
        department_ids: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/practitioners');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>New Practitioner</h1>
            <PractitionerForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                departments={departments}
            />
        </main>
    );
}
