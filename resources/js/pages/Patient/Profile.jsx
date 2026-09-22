import { useForm, usePage } from '@inertiajs/react';

export default function Profile() {
    const { patient, status } = usePage().props;

    const { data, setData, patch, processing, errors } = useForm({
        full_name: patient.full_name ?? '',
        phone: patient.phone ?? '',
        date_of_birth: patient.date_of_birth?.slice(0, 10) ?? '',
        gender: patient.gender ?? '',
        address: patient.address ?? '',
        secondary_contact_name: patient.secondary_contact_name ?? '',
        secondary_contact_phone: patient.secondary_contact_phone ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch('/patient/profile');
    };

    const field = (name, label, type = 'text') => (
        <div>
            <label htmlFor={name}>{label}</label>
            <input id={name} type={type} value={data[name]} onChange={(e) => setData(name, e.target.value)} required />
            {errors[name] && <p>{errors[name]}</p>}
        </div>
    );

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>My Profile</h1>
            {status && <p>{status}</p>}
            <p>Email: {patient.email} (contact support to change)</p>
            <form onSubmit={submit}>
                {field('full_name', 'Full name')}
                {field('phone', 'Phone number', 'tel')}
                {field('date_of_birth', 'Date of birth', 'date')}
                {field('gender', 'Gender')}
                {field('address', 'Address')}
                <div>
                    <label htmlFor="secondary_contact_name">Secondary contact name</label>
                    <input
                        id="secondary_contact_name"
                        value={data.secondary_contact_name}
                        onChange={(e) => setData('secondary_contact_name', e.target.value)}
                    />
                </div>
                <div>
                    <label htmlFor="secondary_contact_phone">Secondary contact phone</label>
                    <input
                        id="secondary_contact_phone"
                        type="tel"
                        value={data.secondary_contact_phone}
                        onChange={(e) => setData('secondary_contact_phone', e.target.value)}
                    />
                </div>
                <button type="submit" disabled={processing}>Save</button>
            </form>
        </main>
    );
}
