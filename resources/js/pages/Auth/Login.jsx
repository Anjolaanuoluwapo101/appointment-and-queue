import { useForm, Link } from '@inertiajs/react';

export default function Login({ portal }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: portal === 'patient',
    });

    const submit = (e) => {
        e.preventDefault();
        post(`/login/${portal}`);
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '24rem' }}>
            <h1>{portal === 'patient' ? 'Patient Login' : 'Staff Login'}</h1>
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="email">Email</label>
                    <input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                    {errors.email && <p>{errors.email}</p>}
                </div>
                <div>
                    <label htmlFor="password">Password</label>
                    <input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                </div>
                <label>
                    <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    Remember me
                </label>
                <div>
                    <button type="submit" disabled={processing}>Log in</button>
                </div>
            </form>
            <p>
                <Link href="/forgot-password">Forgot password?</Link>
            </p>
            {portal === 'patient' && (
                <p>
                    No account? <Link href="/register">Register</Link>
                </p>
            )}
        </main>
    );
}
