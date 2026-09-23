import React from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AsyncButton from '../../Components/AsyncButton';
import { Card, CardContent, CardHeader, CardTitle } from '../../Components/Card';
import AppLayout from '../../Layouts/AppLayout';
import { Badge } from '../../Components/Badge';

export default function Profile() {
    const { patient } = usePage().props;

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

    const renderField = (name, label, type = 'text') => (
        <div>
            <label htmlFor={name} className="block text-xs font-semibold text-zinc-700 mb-1">
                {label}
            </label>
            <input
                id={name}
                type={type}
                value={data[name]}
                onChange={(e) => setData(name, e.target.value)}
                required
                className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
            />
            {errors[name] && <p className="text-xs text-rose-600 mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AppLayout>
            <div className="space-y-6 max-w-2xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">My Patient Profile</h1>
                        <p className="text-xs text-zinc-500 mt-1">
                            Registered Email: <strong className="text-zinc-800">{patient.email}</strong>
                        </p>
                    </div>
                    {patient.patient_number && (
                        <Badge variant="outline" className="font-mono text-xs">
                            Hospital No: {patient.patient_number}
                        </Badge>
                    )}
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Personal Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-zinc-700 mb-1">Hospital / Folder Number</label>
                                <input
                                    type="text"
                                    disabled
                                    value={patient.patient_number || `PAT-#${patient.id}`}
                                    className="w-full bg-zinc-50 border border-zinc-200 rounded px-3 py-2 text-xs text-zinc-600 font-mono"
                                />
                            </div>

                            {renderField('full_name', 'Full Name')}
                            {renderField('phone', 'Phone Number', 'tel')}
                            {renderField('date_of_birth', 'Date of Birth', 'date')}
                            {renderField('gender', 'Gender')}
                            {renderField('address', 'Residential Address')}

                            <div className="pt-2 border-t border-zinc-100">
                                <span className="text-xs font-bold text-zinc-800 block mb-3">Secondary Emergency Contact</span>
                                <div className="space-y-3">
                                    <div>
                                        <label htmlFor="secondary_contact_name" className="block text-xs font-semibold text-zinc-700 mb-1">
                                            Contact Name
                                        </label>
                                        <input
                                            id="secondary_contact_name"
                                            value={data.secondary_contact_name}
                                            onChange={(e) => setData('secondary_contact_name', e.target.value)}
                                            className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label htmlFor="secondary_contact_phone" className="block text-xs font-semibold text-zinc-700 mb-1">
                                            Contact Phone
                                        </label>
                                        <input
                                            id="secondary_contact_phone"
                                            type="tel"
                                            value={data.secondary_contact_phone}
                                            onChange={(e) => setData('secondary_contact_phone', e.target.value)}
                                            className="w-full bg-white border border-zinc-300 rounded px-3 py-2 text-xs text-zinc-900 focus:outline-none focus:border-zinc-900 shadow-sm"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="pt-3">
                                <AsyncButton
                                    type="submit"
                                    loading={processing}
                                    loadingText="Saving profile..."
                                    variant="primary"
                                    className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-5 py-2 rounded text-xs shadow-sm"
                                >
                                    Save Profile Changes
                                </AsyncButton>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
