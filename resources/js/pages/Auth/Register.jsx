import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import AuthLayout from '../../Layouts/AuthLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        patient_number: '',
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

    const renderInput = (name, label, type = 'text', required = true, placeholder = '') => (
        <div>
            <label htmlFor={name} className="block text-xs font-semibold text-zinc-700 mb-1">
                {label}
            </label>
            <input
                id={name}
                type={type}
                value={data[name]}
                onChange={(e) => setData(name, e.target.value)}
                required={required}
                placeholder={placeholder}
                className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
            />
            {errors[name] && <p className="text-xs text-rose-600 mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AuthLayout title="Patient Account Registration">
            <form onSubmit={submit} className="space-y-4">
                {renderInput('patient_number', 'Existing Hospital / Card Number (Optional)', 'text', false, 'Leave blank to auto-generate')}
                {renderInput('full_name', 'Full Name')}
                {renderInput('phone', 'Phone Number', 'tel')}
                {renderInput('email', 'Email Address', 'email')}
                {renderInput('password', 'Password', 'password')}
                {renderInput('password_confirmation', 'Confirm Password', 'password')}
                {renderInput('date_of_birth', 'Date of Birth', 'date')}
                {renderInput('gender', 'Gender')}
                {renderInput('address', 'Residential Address')}
                {renderInput('secondary_contact_name', 'Secondary Contact Name (Optional)', 'text', false)}
                {renderInput('secondary_contact_phone', 'Secondary Contact Phone (Optional)', 'tel', false)}

                <div className="pt-2">
                    <AsyncButton
                        type="submit"
                        loading={processing}
                        loadingText="Creating account..."
                        variant="primary"
                        className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded text-xs shadow-sm"
                    >
                        Create Patient Account
                    </AsyncButton>
                </div>
            </form>

            <div className="mt-6 text-center text-xs text-zinc-500 border-t border-zinc-100 pt-4">
                Already registered?{' '}
                <Link href="/login/patient" className="font-semibold text-zinc-900 hover:underline">
                    Sign in here
                </Link>
            </div>
        </AuthLayout>
    );
}
