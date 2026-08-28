<?php

namespace Database\Seeders;

use App\Models\Requirement;
use Illuminate\Database\Seeder;

class RequirementSeeder extends Seeder
{
    /**
     * Requisitos funcionais definidos na proposta do A2G Test (Tabela 1).
     *
     * @var array<int, array{code: string, title: string, description: string}>
     */
    private array $requirements = [
        ['code' => 'RF001', 'title' => 'Cadastro de Caso de Teste', 'description' => 'O sistema deve permitir que o usuário crie um caso de teste contendo: título, descrição (opcional), passos e resultado esperado.'],
        ['code' => 'RF002', 'title' => 'Edição de Caso de Teste', 'description' => 'O sistema deve permitir que o usuário edite casos de teste já existentes.'],
        ['code' => 'RF003', 'title' => 'Consulta de Caso de Teste', 'description' => 'O sistema deve permitir ao usuário visualizar os detalhes de um caso de teste criado.'],
        ['code' => 'RF004', 'title' => 'Busca por Caso de Teste', 'description' => 'O sistema deve permitir que o usuário busque por casos de teste por nome ou ID.'],
        ['code' => 'RF005', 'title' => 'Registro de Resultado', 'description' => 'O sistema deve permitir que o usuário marque um teste executado como APROVADO ou REPROVADO.'],
        ['code' => 'RF006', 'title' => 'Registro Múltiplo de Resultados', 'description' => 'O sistema deve permitir que o usuário defina o status de dois ou mais testes com uma única ação.'],
        ['code' => 'RF007', 'title' => 'Registro de Informações da Execução', 'description' => 'O sistema deve registrar a data de execução, usuário responsável e comentários (opcional).'],
        ['code' => 'RF008', 'title' => 'Anexo de Evidência', 'description' => 'O sistema deve permitir ao usuário anexar arquivos como prints, logs, etc. a uma execução de teste.'],
        ['code' => 'RF009', 'title' => 'Anexo em Lotes', 'description' => 'O sistema deve permitir que o usuário anexe diversos arquivos de uma única vez a múltiplos testes.'],
        ['code' => 'RF010', 'title' => 'Visualizar Status de Testes', 'description' => 'O sistema deve apresentar a quantidade de testes aprovados, reprovados e pendentes/não executados.'],
        ['code' => 'RF011', 'title' => 'Histórico de Execuções', 'description' => 'O sistema deve manter e exibir o histórico de execuções de cada caso de teste.'],
        ['code' => 'RF012', 'title' => 'Atribuição de Caso de Teste', 'description' => 'O sistema deve permitir que um gerente atribua casos de teste a usuários.'],
        ['code' => 'RF013', 'title' => 'Cadastro de Usuários', 'description' => 'O sistema deve permitir o cadastro de usuários.'],
        ['code' => 'RF014', 'title' => 'Autenticação de Usuários', 'description' => 'O sistema deve permitir login seguro com email e senha.'],
        ['code' => 'RF015', 'title' => 'Gerenciamento de Permissões', 'description' => 'O sistema deve permitir que o super usuário controle as permissões dos demais usuários.'],
        ['code' => 'RF016', 'title' => 'Classificação de Testes', 'description' => 'O sistema deve permitir classificar testes como unitário ou integração.'],
        ['code' => 'RF017', 'title' => 'Associação com Requisitos e Critérios de Aceite', 'description' => 'O sistema deve permitir vincular casos de teste a requisitos do sistema e critérios de aceitação.'],
        ['code' => 'RF018', 'title' => 'Agrupamento por Status', 'description' => 'O sistema deve permitir agrupar testes com base nos seus status (APROVADO, REPROVADO, REGRESSÃO etc.).'],
        ['code' => 'RF019', 'title' => 'Identificar Impacto de Testes', 'description' => 'O sistema deve identificar e exibir ao usuário testes impactados por mudanças em requisitos e critérios de aceitação.'],
        ['code' => 'RF020', 'title' => 'Notificação de Mudanças', 'description' => 'O sistema deve notificar os usuários sobre mudanças nos requisitos, critérios de aceitação, casos de teste, passo a passo e comentários, com interações diferentes dependendo do usuário estar ou não diretamente responsável por um teste.'],
        ['code' => 'RF021', 'title' => 'Visualização de Resultado de Teste Simplificado', 'description' => 'O sistema deve permitir ao usuário trocar entre a visualização completa e a resumida do resultado de um teste.'],
        ['code' => 'RF022', 'title' => 'Template Reutilizável', 'description' => 'O sistema deve permitir ao usuário criar um template de caso de teste que pode ser reutilizado na criação de novos casos de teste.'],
        ['code' => 'RF023', 'title' => 'Sugestão de Requisitos', 'description' => 'O sistema deve fornecer uma sugestão de requisitos que podem se relacionar a um teste com base em palavras em comum entre caso de teste e requisito.'],
        ['code' => 'RF024', 'title' => 'Exportação de Relatório', 'description' => 'O sistema deve permitir que o usuário exporte o relatório de um caso de teste no formato XLSX ou CSV.'],
        ['code' => 'RF025', 'title' => 'Visualização de Casos de Teste Destinados', 'description' => 'O sistema deve permitir que o usuário escolha entre ver apenas os casos de teste destinados a ele ou ver todos os casos de teste.'],
        ['code' => 'RF026', 'title' => 'Dashboard Geral', 'description' => 'O sistema deve exibir um painel com indicadores gerais do projeto (total de casos de teste, percentual de aprovação e testes pendentes).'],
        ['code' => 'RF027', 'title' => 'Gráfico de Progresso de Execução', 'description' => 'O sistema deve exibir um gráfico de progresso indicando a proporção de testes executados vs. não executados dentro de um Test Suite.'],
        ['code' => 'RF028', 'title' => 'Personalização de Status de Teste', 'description' => 'O sistema deve permitir ao usuário criar e gerenciar status de execução de testes customizados, além dos padrões já existentes.'],
        ['code' => 'RF029', 'title' => 'Filtro Avançado de Casos de Teste', 'description' => 'O sistema deve permitir filtrar casos de teste com múltiplos critérios simultaneamente: status, responsável, tipo (unitário ou integração), prioridade e data de execução.'],
        ['code' => 'RF030', 'title' => 'Importação de Casos de Teste via CSV ou Excel', 'description' => 'O sistema deve permitir ao usuário importar casos de teste em lote através de arquivos no formato CSV ou XLSX.'],
        ['code' => 'RF031', 'title' => 'Matriz de Rastreabilidade', 'description' => 'O sistema deve gerar uma matriz de rastreabilidade relacionando os requisitos do sistema com os respectivos casos de teste e os resultados de execução.'],
    ];

    /**
     * Seed the functional requirements from the project proposal.
     */
    public function run(): void
    {
        foreach ($this->requirements as $requirement) {
            Requirement::firstOrCreate(
                ['code' => $requirement['code']],
                [
                    'type' => 'funcional',
                    'title' => $requirement['title'],
                    'description' => $requirement['description'],
                ]
            );
        }
    }
}
