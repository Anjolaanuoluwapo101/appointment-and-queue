import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';
import DepartmentForm from './Form';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        queue_prefix: '',
        room_label: '',
        payment_mode: 'allow_both',
        base_fee_kobo: 0,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/admin/departments');
    };

    return (
        <AppLayout>
            <div className="space-y-6 max-w-xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Create Hospital Department</h1>
                    <p className="text-xs text-zinc-500 mt-1">Configure new clinical department parameters</p>
                </div>

                <Card className="bg-white border border-zinc-200">
                    <CardHeader>
                        <CardTitle className="text-sm">Department Parameters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DepartmentForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
