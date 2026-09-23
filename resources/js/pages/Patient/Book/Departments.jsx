import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function Departments() {
    const { departments } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-4xl mx-auto bg-white py-6">
                <div className="border-b border-zinc-100 pb-4">
                    <span className="text-xs font-mono text-zinc-400 block uppercase">Step 1 of 3 · Select Department</span>
                    <h1 className="text-2xl font-bold tracking-tight text-zinc-900">Choose Department for Appointment</h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {departments.map((d) => (
                        <Card key={d.id} className="bg-white border border-zinc-200 hover:border-zinc-400 transition-all">
                            <CardHeader className="pb-2 border-b-0">
                                <CardTitle className="text-base">{d.name}</CardTitle>
                                <Badge variant="outline" className="font-mono text-[10px] uppercase">
                                    {d.payment_mode.replace('_', ' ')}
                                </Badge>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-xs font-semibold text-zinc-900">
                                    Base Consultation Fee: ₦{(d.base_fee_kobo / 100).toLocaleString()}
                                </p>
                                <div className="pt-2">
                                    <Link
                                        href={`/patient/book/${d.id}/practitioners`}
                                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm inline-block"
                                    >
                                        Select Department →
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
