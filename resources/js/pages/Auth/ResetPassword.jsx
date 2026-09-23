import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../Components/Card';
import AsyncButton from '../../Components/AsyncButton';

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
        <AuthLayout title="Set New Password" subtitle="Choose a strong password to secure your account">
            <Card className="w-full">
                <CardHeader>
                    <CardTitle className="text-base font-semibold text-zinc-900">New Password</CardTitle>
                </CardHeader>
                <CardContent>
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
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                            {errors.email && <p className="text-xs text-rose-600 mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-xs font-semibold text-zinc-700 mb-1">
                                New Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                            {errors.password && <p className="text-xs text-rose-600 mt-1">{errors.password}</p>}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-xs font-semibold text-zinc-700 mb-1">
                                Confirm New Password
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                        </div>

                        <AsyncButton
                            type="submit"
                            loading={processing}
                            loadingText="Resetting password..."
                            variant="primary"
                            className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                        >
                            Reset Password
                        </AsyncButton>

                        <div className="text-center pt-2">
                            <Link href="/login/patient" className="text-xs font-medium text-zinc-600 hover:text-zinc-900 underline">
                                Back to Sign In
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
