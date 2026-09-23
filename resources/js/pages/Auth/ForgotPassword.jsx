import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import AuthLayout from '../../Layouts/AuthLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../Components/Card';
import AsyncButton from '../../Components/AsyncButton';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <AuthLayout title="Forgot Password" subtitle="Enter your account email to receive a password reset link">
            <Card className="w-full">
                <CardHeader>
                    <CardTitle className="text-base font-semibold text-zinc-900">Reset Password</CardTitle>
                </CardHeader>
                <CardContent>
                    {status && (
                        <div className="mb-4 p-3 rounded bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
                            {status}
                        </div>
                    )}

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
                                placeholder="name@hospital.com"
                                className="w-full bg-white border border-zinc-200 rounded-md px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                            />
                            {errors.email && <p className="text-xs text-rose-600 mt-1">{errors.email}</p>}
                        </div>

                        <AsyncButton
                            type="submit"
                            loading={processing}
                            loadingText="Sending link..."
                            variant="primary"
                            className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded-md text-xs shadow-sm"
                        >
                            Send Reset Link
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
