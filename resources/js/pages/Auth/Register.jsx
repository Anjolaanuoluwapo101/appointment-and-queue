import { useForm, Link } from '@inertiajs/react';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        phone: '',
        email: '',
        password: '',
        password_confirmation: '',
        date_of_birth: '',
        gender: '',
        address: '',
        secondary_contact_name: '',
        secondary_contact_phone: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    const field = (name, label, type = 'text', required = true) => (
        <div>
            <label htmlFor={name}>{label}</label>
            <input id={name} type={type} value={data[name]} onChange={(e) => setData(name, e.target.value)} required={required} />
            {errors[name] && <p>{errors[name]}</p>}
        </div>
    );

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '28rem' }}>
            <h1>Patient Registration</h1>
            <form onSubmit={submit}>
                {field('full_name', 'Full name')}
                {field('phone', 'Phone number', 'tel')}
                {field('email', 'Email', 'email')}
                {field('password', 'Password', 'password')}
                {field('password_confirmation', 'Confirm password', 'password')}
                {field('date_of_birth', 'Date of birth', 'date')}
                {field('gender', 'Gender')}
                {field('address', 'Address')}
                {field('secondary_contact_name', 'Secondary contact name (optional)', 'text', false)}
                {field('secondary_contact_phone', 'Secondary contact phone (optional)', 'tel', false)}
                <div>
                    <button type="submit" disabled={processing}>Create account</button>
                </div>
            </form>
            <p>
                Have an account? <Link href="/login/patient">Log in</Link>
            </p>
        </main>
    );
}
