import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { store as registerStore } from '@/routes/register';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Login" />

            <div className="bg-grid" />

            <div className="auth-wrap fade-in">
                <div className="auth-logo">
                    <Link href="/"><span>A2G</span> TEST<span className="dot">.</span></Link>
                </div>

                <div className="auth-card">
                    <div className="auth-header">
                        <h1 className="auth-title">Bem-vindo de volta</h1>
                        <p className="auth-sub">Acesse sua conta para gerenciar seus casos de teste.</p>
                    </div>

                    {status && (
                        <div className="alert alert-success" style={{ marginBottom: 20 }}>
                            {status}
                        </div>
                    )}

                    <div className="demo-accounts">
                        <div className="demo-title">// Contas de demonstração</div>
                        <div className="demo-item"><span className="demo-badge">admin</span> admin@a2gtest.com</div>
                        <div className="demo-item"><span className="demo-badge">qa</span> qa@a2gtest.com</div>
                        <div className="demo-item"><span className="demo-badge">viewer</span> viewer@a2gtest.com</div>
                    </div>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="flex flex-col"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="form-group">
                                    <label className="form-label">E-mail</label>
                                    <input
                                        className="form-input"
                                        type="email"
                                        name="email"
                                        placeholder="seu@email.com"
                                        autoFocus
                                        autoComplete="email"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Senha</label>
                                    <PasswordInput
                                        className="form-input"
                                        name="password"
                                        placeholder="••••••••••"
                                        autoComplete="current-password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                {canResetPassword && (
                                    <Link href={request()} className="forgot">
                                        Esqueceu a senha?
                                    </Link>
                                )}

                                <button type="submit" className="btn btn-primary btn-full" disabled={processing}>
                                    {processing && <Spinner />}
                                    Entrar →
                                </button>
                            </>
                        )}
                    </Form>
                </div>

                <div className="auth-footer">
                    Não tem uma conta? <Link href={registerStore.url()}>Criar conta grátis</Link>
                </div>
            </div>
        </>
    );
}
