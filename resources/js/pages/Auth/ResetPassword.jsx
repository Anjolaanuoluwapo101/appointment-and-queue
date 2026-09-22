import { useForm } from '@inertiajs/react';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem', maxWidth: '24rem' }}>
            <h1>Set new password</h1>
            <form onSubmit={submit}>
                <div>
                    <label htmlFor="email">Email</label>
                    <input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                    {errors.email && <p>{errors.email}</p>}
                </div>
                <div>
                    <label htmlFor="password">New password</label>
                    <input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                    {errors.password && <p>{errors.password}</p>}
                </div>
                <div>
                    <label htmlFor="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />
                </div>
                <button type="submit" disabled={processing}>Reset password</button>
            </form>
        </main>
    );
}
