import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="A2G TEST" />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <span className="text-sm font-bold">A2G</span>
                            </div>

                            <div>
                                <span className="text-lg font-semibold">
                                    A2G TEST
                                </span>
                            </div>
                        </div>

                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="rounded-lg border border-border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                                >
                                    Entrar
                                </Link>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="relative overflow-hidden">
                        <div className="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
                            <div className="mx-auto max-w-3xl text-center">
                                <div className="mb-6 inline-flex items-center rounded-full border border-border bg-muted/50 px-4 py-1.5 text-sm font-medium">
                                    Plataforma de Gestão de Testes
                                </div>

                                <h1 className="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                    Testes de software mais
                                    <span className="text-primary">
                                        {' '}organizados e eficientes
                                    </span>
                                </h1>

                                <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                                    O A2G TEST é uma plataforma para criação,
                                    organização, execução e acompanhamento de
                                    casos de teste, ajudando equipes a melhorar
                                    a qualidade e a confiabilidade de seus
                                    sistemas.
                                </p>

                                <div className="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    {auth.user ? (
                                        <Link
                                            href={dashboard()}
                                            className="w-full rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 sm:w-auto"
                                        >
                                            Acessar dashboard
                                        </Link>
                                    ) : (
                                        <Link
                                            href={login()}
                                            className="w-full rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 sm:w-auto"
                                        >
                                            Acessar sistema
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="border-y border-border bg-muted/30">
                        <div className="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight">
                                    Tudo para gerenciar seus testes
                                </h2>

                                <p className="mt-4 text-muted-foreground">
                                    Centralize as informações do processo de
                                    testes em um único ambiente.
                                </p>
                            </div>

                            <div className="mx-auto mt-12 grid max-w-5xl gap-6 md:grid-cols-3">
                                <FeatureCard
                                    title="Casos de teste"
                                    description="Crie e organize casos de teste de forma estruturada, mantendo seus cenários documentados e acessíveis."
                                    icon="✓"
                                />

                                <FeatureCard
                                    title="Execução"
                                    description="Registre a execução dos testes e acompanhe seus resultados para identificar problemas rapidamente."
                                    icon="▶"
                                />

                                <FeatureCard
                                    title="Acompanhamento"
                                    description="Visualize o andamento dos testes e obtenha uma visão clara da qualidade do projeto."
                                    icon="◈"
                                />
                            </div>
                        </div>
                    </section>

                    <section>
                        <div className="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                            <div className="mx-auto max-w-3xl rounded-2xl border border-border bg-card p-8 text-center shadow-sm sm:p-12">
                                <h2 className="text-3xl font-bold">
                                    Pronto para começar?
                                </h2>

                                <p className="mx-auto mt-4 max-w-xl text-muted-foreground">
                                    Acesse o A2G TEST e comece a gerenciar seus
                                    casos de teste de maneira mais simples e
                                    organizada.
                                </p>

                                <div className="mt-8">
                                    {auth.user ? (
                                        <Link
                                            href={dashboard()}
                                            className="inline-flex rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                                        >
                                            Ir para o dashboard
                                        </Link>
                                    ) : (
                                        <Link
                                            href={login()}
                                            className="inline-flex rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                                        >
                                            Entrar no A2G TEST
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex max-w-7xl flex-col gap-2 px-6 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between lg:px-8">
                        <p>
                            © {new Date().getFullYear()} A2G TEST. Todos os
                            direitos reservados.
                        </p>

                        <p>
                            Sistema de gerenciamento de testes de software.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function FeatureCard({
    title,
    description,
    icon,
}: {
    title: string;
    description: string;
    icon: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-card p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
            <div className="mb-5 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-lg font-bold text-primary">
                {icon}
            </div>

            <h3 className="text-lg font-semibold">{title}</h3>

            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}