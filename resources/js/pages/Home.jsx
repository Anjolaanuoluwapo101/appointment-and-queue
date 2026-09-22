import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Home({ status }) {
    const { t } = useTranslation();

    return (
        <main style={{ fontFamily: 'Instrument Sans, system-ui, sans-serif', padding: '3rem' }}>
            <h1>{t('app.name')}</h1>
            <p>{status ?? t('app.tagline')}</p>
            <p>
                <Link href="/login/patient">{t('nav.patientLogin')}</Link> · <Link href="/login/staff">{t('nav.staffLogin')}</Link> ·{' '}
                <Link href="/register">{t('nav.register')}</Link>
            </p>
        </main>
    );
}
