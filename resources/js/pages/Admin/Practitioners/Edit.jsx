import { useForm, usePage } from '@inertiajs/react';
import PractitionerForm from './Form';

export default function Edit() {
    const { practitioner, departments } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        full_name: practitioner.full_name ?? '',
        specialisation: practitioner.specialisation ?? '',
        qualifications: practitioner.qualifications ?? '',
        bio: practitioner.bio ?? '',
        internal_contact: practitioner.internal_contact ?? '',
        availability: practitioner.availability ?? 'active',
        department_ids: practitioner.departments.map((d) => d.id),
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/practitioners/${practitioner.id}`);
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Edit Practitioner</h1>
            <PractitionerForm
                data={data}
                setData={setData}
                errors={errors}
                processing={processing}
                onSubmit={submit}
                departments={departments}
                practitioner={practitioner}
            />
        </main>
    );
}
