import { Link, useForm } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import AuthLayout from '../../Layouts/AuthLayout';

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
        <AuthLayout title={portal === 'patient' ? 'Patient Sign In' : 'Staff Sign In'}>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="block text-xs font-semibold text-zinc-700 mb-1">
                        Email Address
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                    {errors.email && <p className="text-xs text-rose-600 mt-1">{errors.email}</p>}
                </div>

                <div>
                    <div className="flex items-center justify-between mb-1">
                        <label htmlFor="password" className="block text-xs font-semibold text-zinc-700">
                            Password
                        </label>
                        <Link href="/forgot-password" className="text-xs font-medium text-zinc-600 hover:text-zinc-900">
                            Forgot password?
                        </Link>
                    </div>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                        className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                    />
                </div>

                <div className="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                    />
                    <label htmlFor="remember" className="ml-2 text-xs text-zinc-600">
                        Remember me on this browser
                    </label>
                </div>

                <div className="pt-2">
                    <AsyncButton
                        type="submit"
                        loading={processing}
                        loadingText="Signing in..."
                        variant="primary"
                        className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded text-xs shadow-sm"
                    >
                        Sign In
                    </AsyncButton>
                </div>
            </form>

            {portal === 'patient' && (
                <div className="mt-6 text-center text-xs text-zinc-500 border-t border-zinc-100 pt-4">
                    Need a patient account?{' '}
                    <Link href="/register" className="font-semibold text-zinc-900 hover:underline">
                        Register here
                    </Link>
                </div>
            )}
        </AuthLayout>
    );
}
