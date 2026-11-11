<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CaixaFisicoController;
use App\Http\Controllers\DespesaController;
use App\Http\Controllers\ImportacaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaxaController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DreController;
use App\Http\Controllers\DashboardController;

// Redireciona a página inicial direto para o login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rotas do seu Sistema Financeiro
Route::middleware(['auth', 'verified'])->group(function () {

    // Rota do Dashboard inteligente
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Caixa Físico
    Route::get('/caixa', [CaixaFisicoController::class, 'index'])->name('caixa.index');
    Route::post('/caixa', [CaixaFisicoController::class, 'store'])->name('caixa.store');

    // ==========================================
    // ROTAS DE DESPESAS (PF e PJ)
    // ==========================================
    Route::get('/despesas', [\App\Http\Controllers\DespesaController::class, 'index'])->name('despesas.index');
    Route::post('/despesas', [\App\Http\Controllers\DespesaController::class, 'store'])->name('despesas.store');
    Route::delete('/despesas/{id}', [\App\Http\Controllers\DespesaController::class, 'destroy'])->name('despesas.destroy');

    // ==========================================
    // MÓDULO DE GESTÃO ESTRATÉGICA (POTES)
    // ==========================================
    Route::prefix('despesas/potes')->group(function () {
        Route::post('config', [\App\Http\Controllers\DespesaController::class, 'salvarConfiguracaoPotes'])->name('potes.configuracao');
        Route::post('faturamento', [\App\Http\Controllers\DespesaController::class, 'salvarFaturamentoPotes'])->name('potes.faturamento');
        Route::post('despesa', [\App\Http\Controllers\DespesaController::class, 'salvarDespesaPotes'])->name('potes.despesa');
        Route::post('despesa/copiar-fixas', [\App\Http\Controllers\DespesaController::class, 'copiarFixas'])->name('potes.fixas.copiar'); // NOVA ROTA
        Route::delete('despesa/{id}', [\App\Http\Controllers\DespesaController::class, 'deletarDespesaPotes'])->name('potes.despesa.destroy');
        Route::post('saque', [\App\Http\Controllers\DespesaController::class, 'registrarSaquePotes'])->name('potes.saque');
        Route::get('relatorio', [\App\Http\Controllers\DespesaController::class, 'gerarRelatorioPdf'])->name('potes.relatorio');
        // Rotas das Prioridades de Investimento
        Route::post('prioridade', [App\Http\Controllers\DespesaController::class, 'salvarPrioridade'])->name('potes.prioridade.store');
        Route::patch('prioridade/{id}', [App\Http\Controllers\DespesaController::class, 'atualizarPrioridade'])->name('potes.prioridade.update');
        Route::delete('prioridade/{id}', [App\Http\Controllers\DespesaController::class, 'deletarPrioridade'])->name('potes.prioridade.destroy');
    });

    // ==========================================
    // MÓDULO DE IMPORTAÇÃO (EXTRATOS)
    // ==========================================
    Route::get('/importacao', [ImportacaoController::class, 'index'])->name('importacao.index');
    Route::post('/importacao/upload', [ImportacaoController::class, 'upload'])->name('importacao.upload');
    Route::delete('/importacao/excluir', [ImportacaoController::class, 'destroy'])->name('importacao.destroy');

    // ==========================================
    // MÓDULO DO DRE
    // ==========================================
    Route::get('/dre', [DreController::class, 'index'])->name('dre.index');
    Route::get('/dre/imprimir', [DreController::class, 'imprimir'])->name('dre.imprimir');

    /// ==========================================
    // CONFIGURAÇÕES, SALDOS E TAXAS
    // ==========================================
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::post('/configuracoes/saldo', [ConfiguracaoController::class, 'salvarSaldoInicial'])->name('configuracoes.saldo.salvar');

    // Rota que a barra lateral espera encontrar:
    Route::get('/taxas', [TaxaController::class, 'index'])->name('taxas.index');

    // Rotas de gestão interna das taxas:
    Route::get('/configuracoes/taxas-maquininhas', [TaxaController::class, 'index'])->name('configuracoes.taxas');
    Route::post('/configuracoes/taxas-maquininhas', [TaxaController::class, 'store'])->name('configuracoes.taxas.store');
    Route::delete('/configuracoes/taxas-maquininhas/{taxa}', [TaxaController::class, 'destroy'])->name('configuracoes.taxas.destroy');
});

// Rotas do Perfil do Utilizador
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
