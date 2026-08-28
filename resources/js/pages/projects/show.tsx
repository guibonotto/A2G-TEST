import { Head } from '@inertiajs/react';
import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as projectsIndex,
    show as projectsShow,
} from '@/routes/projects';

type Project = {
    uuid: string;
    name: string;
    isOwner: boolean;
};

export default function ProjectsShow({ project }: { project: Project }) {
    const [copied, setCopied] = useState(false);

    const copyId = async () => {
        await navigator.clipboard.writeText(project.uuid);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <>
            <Head title={project.name} />

            <div className="mx-auto w-full max-w-lg space-y-6">
                <Heading
                    title={project.name}
                    description={
                        project.isOwner
                            ? 'Compartilhe o ID abaixo e a senha do projeto com quem você quer convidar.'
                            : 'Você faz parte deste projeto.'
                    }
                />

                <div className="grid gap-2">
                    <Label htmlFor="project-uuid">ID do projeto</Label>
                    <div className="flex gap-2">
                        <Input
                            id="project-uuid"
                            readOnly
                            value={project.uuid}
                            className="font-mono"
                            onFocus={(e) => e.target.select()}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={copyId}
                            aria-label="Copiar ID do projeto"
                        >
                            {copied ? (
                                <Check className="text-green-600" />
                            ) : (
                                <Copy />
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

ProjectsShow.layout = (props: { project: Project }) => ({
    breadcrumbs: [
        { title: 'Projetos', href: projectsIndex() },
        { title: props.project.name, href: projectsShow(props.project.uuid) },
    ],
});
