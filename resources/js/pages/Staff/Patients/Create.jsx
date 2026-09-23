import React from 'react';
import { useForm } from '@inertiajs/react';
import AsyncButton from '../../../Components/AsyncButton';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        patient_number: '',
        full_name: '',
        phone: '',
        email: '',
        date_of_birth: '',
        gender: '',
        address: '',
        secondary_contact_name: '',
        secondary_contact_phone: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/staff/patients');
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
        <AppLayout>
            <div className="space-y-6 max-w-2xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Register New Patient File</h1>
                    <p className="text-xs text-zinc-500 mt-1">Add new patient demographics to hospital records</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Patient Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            {renderInput('patient_number', 'Existing Hospital / Paper Folder Number (Optional)', 'text', false, 'Leave blank to auto-generate (e.g. PAT-2026-00001)')}
                            {renderInput('full_name', 'Full Name')}
                            {renderInput('phone', 'Phone Number', 'tel')}
                            {renderInput('email', 'Email Address', 'email')}
                            {renderInput('date_of_birth', 'Date of Birth', 'date')}
                            {renderInput('gender', 'Gender')}
                            {renderInput('address', 'Residential Address')}
                            {renderInput('secondary_contact_name', 'Secondary Emergency Contact Name', 'text', false)}
                            {renderInput('secondary_contact_phone', 'Secondary Emergency Contact Phone', 'tel', false)}

                            <div className="pt-3">
                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Registering patient..."
                                    variant="primary"
                                    className="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-semibold py-2 rounded text-xs shadow-sm"
                                >
                                    Register Patient File
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
