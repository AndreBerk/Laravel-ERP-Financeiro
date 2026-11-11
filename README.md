Enterprise Financial Management ERP (Profit First)

Sistema web monolítico de gestão financeira e auditoria, desenvolvido para garantir precisão matemática absoluta e previsibilidade de caixa. A arquitetura foi projetada com base na metodologia de gestão estratégica "Profit First", integrando cálculo contínuo de DRE e auditoria de extratos bancários.

Arquitetura e Tecnologias

Backend: PHP 8.2+ / Laravel 12

Frontend: Blade Templates / Tailwind CSS / Alpine.js

Banco de Dados: SQLite (Arquitetura local otimizada e livre de corrupções físicas)

Gestão de Ativos: Vite

Módulos Principais

1. Gestão Estratégica por Potes (Profit First)

Motor financeiro responsável pela distribuição inteligente de capital.

Split Automático de Faturamento: Subtração da reserva de investimento e distribuição do saldo remanescente entre os centros de custo (Operação, Reinvestimento, Lucro e Investimento).

Validação de Integridade: Regras de negócio rígidas no backend e frontend que exigem que as porcentagens dos potes e das cotas societárias totalizem exatamente 100,00%.

Gestão de Cofres Virtuais:

Reinvestimento: Ciclo trimestral para acervo, suprimentos e marketing.

Lucro dos Sócios: Simulação e distribuição semestral baseada no quadro societário.

Investimentos (CDI): Cálculo de juros compostos baseado no CDI anual e percentual negociado.

Termômetro de Custos: Algoritmo comparativo de despesas fixas mensais para identificar aumentos operacionais silenciosos.

2. Gestão de Prioridades e Investimentos

Módulo de planejamento integrado ao fluxo de capital da empresa.

Cadastro Estratégico: Registro de demandas categorizadas por prioridade visual, previsão de custo e prazos.

Dashboard Analítico: Exibição dinâmica de valores previstos em aberto, demandas urgentes e capital executado em projetos concluídos.

Cobertura Financeira: Cruzamento em tempo real do saldo real do cofre de Reinvestimento com o total previsto das demandas, indicando superávit ou déficit instantâneo.

Ciclo de Vida de Projetos: Ordenação por urgência, alertas de vencimento e input de "Gasto Real" para comparativo orçado vs. realizado.

3. DRE Contínuo e Previsibilidade

Separação de Receita Real: Isolamento de entradas operacionais reais contra transferências internas ou saldos anteriores.

Previsibilidade de Recebíveis: Cálculo de valores a receber líquidos (maquininhas) descontando a taxa média histórica do período.

Multi-Contas: Acompanhamento consolidado e isolado de contas bancárias.

4. Auditoria e Importação

Multiparser de Extratos: Leitor e interpretador de extratos bancários e relatórios de cartões de crédito (Itaú PJ, Cielo e Rede Itaú).

Trava Anti-Duplicidade: Prevenção no nível da aplicação e do banco de dados contra upload repetido de lotes de arquivos.

Configuração e Instalação Local

Clone o repositório:

git clone https://github.com/seu-utilizador/sistema-financeiro.git
cd sistema-financeiro


Instale as dependências:

composer install
npm install


Configure as variáveis de ambiente:

cp .env.example .env


Certifique-se de configurar a conexão para SQLite no arquivo .env:

DB_CONNECTION=sqlite


(Caso necessário, crie o arquivo do banco rodando: touch database/database.sqlite no Linux/Mac ou criando um arquivo vazio no Windows).

Gere a chave da aplicação e rode as migrações:

php artisan key:generate
php artisan migrate:fresh


Compile os assets e inicie o servidor:

npm run build
php artisan serve


Execução Desktop (Ambiente Windows)

Para inicializar o sistema como uma aplicação nativa no Windows, crie um arquivo Iniciar_Sistema.bat na Área de Trabalho com o script abaixo (ajuste o caminho do projeto):

@echo off
cd C:\caminho\para\o\seu\projeto\financeiro
start /b php artisan serve
timeout /t 2 >nul
start chrome --app=http://127.0.0.1:8000
exit


Este projeto possui fins de portfólio e gestão privada. Desenvolvido com foco em alta engenharia de software e eficiência financeira.
