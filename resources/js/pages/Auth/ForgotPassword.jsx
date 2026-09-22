import { useForm, Link } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '24rem' }}>
            <h1>Reset password</h1>
            {status && <p>{status}</p>}
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="email">Email</label>
                    <input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                    {errors.email && <p>{errors.email}</p>}
                </div>
                <button type="submit" disabled={processing}>Send reset link</button>
            </form>
            <p>
                <Link href="/">Back</Link>
            </p>
        </main>
    );
}
