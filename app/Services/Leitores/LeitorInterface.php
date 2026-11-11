<?php

namespace App\Services\Leitores;

interface LeitorInterface
{
    public function ler($caminhoArquivo, $tipoArquivo, $instituicao, $mes, $ano);
}
