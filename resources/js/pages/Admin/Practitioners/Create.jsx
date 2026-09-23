import React from 'react';
import { useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Card, { CardHeader, CardTitle, CardContent } from '../../../Components/Card';
import PractitionerForm from './Form';

export default function Create() {
    const { departments } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        specialisation: '',
        qualifications: '',
        bio: '',
        internal_contact: '',
        availability: 'active',
        department_ids: [],
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/practitioners');
    };

    return (
        <AppLayout activeRoute="admin.practitioners">
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight text-zinc-900">New Practitioner</h1>
                        <p className="text-xs text-zinc-500 mt-1">Register a new medical practitioner and assign their departments.</p>
                    </div>
                    <Link
                        href="/admin/practitioners"
                        className="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 focus:outline-none"
                    >
                        Back to Practitioners
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-semibold">Practitioner Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <PractitionerForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            departments={departments}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
