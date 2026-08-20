import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Spinner } from '@/components/ui/spinner';
import { store as loginStore } from '@/routes/login';
import { store } from '@/routes/register';

const roles = [
    { id: 'role-qa', value: 'qa', name: 'QA / Tester', desc: 'Criar e editar testes' },
    { id: 'role-dev', value: 'developer', name: 'Desenvolvedor', desc: 'Visualizar e executar' },
    { id: 'role-admin', value: 'admin', name: 'Administrador', desc: 'Acesso total' },
    { id: 'role-viewer', value: 'viewer', name: 'Visualizador', desc: 'Somente leitura' },
];

export default function Register() {
    const [selectedRole, setSelectedRole] = useState('qa');

    return (
        <>
            <Head title="Criar conta" />

            <div className="bg-grid" />

            <div className="auth-wrap fade-in">
                <div className="auth-logo">
                    <Link href="/"><span>A2G</span> TEST<span className="dot">.</span></Link>
                </div>

                <div className="auth-card">
                    <div className="step-indicator">
                        <div className="step-dot active" />
                        <div className="step-dot" />
                        <div className="step-dot" />
                    </div>

                    <div className="auth-header">
                        <h1 className="auth-title">Crie sua conta</h1>
                        <p className="auth-sub">Junte-se ao A2G TEST e gerencie seus testes com eficiência.</p>
                    </div>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="flex flex-col gap-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="form-row">
                                    <div className="form-group">
                                        <label className="form-label">Nome</label>
                                        <input className="form-input" type="text" name="first_name" placeholder="João" autoFocus autoComplete="given-name" />
                                        <InputError message={errors.first_name} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Sobrenome</label>
                                        <input className="form-input" type="text" name="last_name" placeholder="Silva" autoComplete="family-name" />
                                        <InputError message={errors.last_name} />
                                    </div>
                                </div>

                                <div className="form-group">
                                    <label className="form-label">E-mail</label>
                                    <input className="form-input" type="email" name="email" placeholder="joao@empresa.com" autoComplete="email" />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Senha</label>
                                    <PasswordInput className="form-input" name="password" placeholder="••••••••••" autoComplete="new-password" />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Confirmar senha</label>
                                    <PasswordInput className="form-input" name="password_confirmation" placeholder="••••••••••" autoComplete="new-password" />
                                    <InputError message={errors.password_confirmation} />
                                </div>

                                <div className="form-group">
                                    <label className="form-label" style={{ marginBottom: 12 }}>Perfil de acesso</label>
                                    <div className="role-options">
                                        {roles.map((role) => (
                                            <div key={role.id} className="role-option">
                                                <input
                                                    type="radio"
                                                    name="role"
                                                    id={role.id}
                                                    value={role.value}
                                                    checked={selectedRole === role.value}
                                                    onChange={() => setSelectedRole(role.value)}
                                                />
                                                <label className="role-label" htmlFor={role.id}>
                                                    <span className="role-name">{role.name}</span>
                                                    <span className="role-desc">{role.desc}</span>
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    <InputError message={errors.role} />
                                </div>

                                <button type="submit" className="btn btn-primary btn-full" disabled={processing}>
                                    {processing && <Spinner />}
                                    Criar conta →
                                </button>

                                <div className="divider-or"><span>ou</span></div>

                                <button type="button" className="btn btn-ghost btn-full">
                                    Continuar com Google
                                </button>
                            </>
                        )}
                    </Form>
                </div>

                <div className="auth-footer">
                    Já tem uma conta? <Link href={loginStore.url()}>Fazer login</Link>
                </div>
            </div>
        </>
    );
}
