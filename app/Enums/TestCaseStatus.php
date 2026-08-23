<?php

namespace App\Enums;

enum TestCaseStatus: string
{
    case Aprovado = 'APROVADO';
    case Reprovado = 'REPROVADO';
    case Pendente = 'PENDENTE';
    case Cancelado = 'CANCELADO';
    case Regressao = 'REGRESSÃO';
}
