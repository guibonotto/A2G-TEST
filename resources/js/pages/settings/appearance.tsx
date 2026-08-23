import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Configurações de aparência" />

            <h1 className="sr-only">Configurações de aparência</h1>

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="Aparência"
                    description="Personalize a aparência da sua conta."
                />

                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Configurações de aparência',
            href: editAppearance(),
        },
    ],
};