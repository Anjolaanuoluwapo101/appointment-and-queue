import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function AuthLayout({ children, title }) {
    const { t } = useTranslation();

    return (
        <div className="min-h-screen bg-white text-zinc-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans antialiased">
            <div className="sm:mx-auto sm:w-full sm:max-w-md text-center space-y-3">
                <Link href="/" className="inline-flex items-center space-x-2 text-sm font-bold text-zinc-900">
                    <span className="h-8 w-8 rounded bg-zinc-900 text-white flex items-center justify-center font-bold text-sm">
                        C
                    </span>
                    <span className="text-lg tracking-tight font-extrabold">{t('app.name')}</span>
                </Link>
                {title && <h2 className="text-xl font-bold tracking-tight text-zinc-900">{title}</h2>}
            </div>

            <div className="mt-6 sm:mx-auto sm:w-full sm:max-w-md">
                <div className="bg-white py-8 px-6 border border-zinc-200 rounded-lg shadow-sm sm:px-10">
                    {children}
                </div>
            </div>
        </div>
    );
}
