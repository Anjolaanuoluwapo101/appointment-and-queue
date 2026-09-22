import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// English-only for MVP (PRD §25). Yoruba/Igbo/Hausa join post-MVP;
// structure stays key-based so adding locales needs no retrofit.
const resources = {
    en: {
        translation: {
            app: {
                name: 'Appointment System',
                tagline: 'Inertia + React is wired. Supabase Postgres connected.',
            },
            nav: {
                patientLogin: 'Patient login',
                staffLogin: 'Staff login',
                register: 'Register',
            },
        },
    },
};

i18n.use(initReactI18next).init({
    resources,
    lng: 'en',
    fallbackLng: 'en',
    interpolation: { escapeValue: false },
});

export default i18n;
