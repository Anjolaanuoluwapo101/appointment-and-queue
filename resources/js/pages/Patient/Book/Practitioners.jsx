import { Link, usePage } from '@inertiajs/react';
import { Badge } from '../../../Components/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '../../../Components/Card';
import AppLayout from '../../../Layouts/AppLayout';

export default function Practitioners() {
    const { department, practitioners } = usePage().props;

    return (
        <AppLayout>
            <div className="space-y-6 max-w-4xl mx-auto bg-white py-6">
                <div className="flex items-center justify-between border-b border-zinc-100 pb-4">
                    <div>
                        <span className="text-xs font-mono text-zinc-400 block uppercase">Step 2 of 3 · Select Practitioner</span>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900">{department.name} Specialists</h1>
                    </div>
                    <Link
                        href={`/patient/book/${department.id}/slots`}
                        className="bg-zinc-100 hover:bg-zinc-200 text-zinc-900 font-semibold px-4 py-2 rounded text-xs border border-zinc-300 transition-colors"
                    >
                        Skip Practitioner Selection →
                    </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {practitioners.map((p) => (
                        <Card key={p.id} className="bg-white border border-zinc-200 hover:border-zinc-400 transition-all">
                            <CardHeader className="pb-2 border-b-0">
                                <CardTitle className="text-base">{p.full_name}</CardTitle>
                                <Badge variant="outline">{p.specialisation}</Badge>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p className="text-xs text-zinc-600 font-semibold">
                                    Consultation Fee: ₦{(p.fee_kobo / 100).toLocaleString()}
                                </p>
                                {p.bio && <p className="text-xs text-zinc-500 italic">{p.bio}</p>}
                                <div className="pt-2">
                                    <Link
                                        href={`/patient/book/${department.id}/slots?practitioner_id=${p.id}`}
                                        className="bg-zinc-900 hover:bg-zinc-800 text-white font-semibold px-4 py-2 rounded text-xs transition-colors shadow-sm inline-block"
                                    >
                                        Select Practitioner →
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
