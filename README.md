# Sistema de Gestão Financeira e Estratégica (Metodologia Profit First)

Sistema web monolítico de gestão financeira empresarial projetado para automação e precisão matemática absoluta. Desenvolvido com Laravel 12, o software executa auditoria de extratos bancários, cálculo contínuo de DRE e aplica a metodologia de gestão financeira por cotas (Profit First).

> **Demonstração em Vídeo:** 

https://github.com/user-attachments/assets/35e6d786-732f-4402-a020-bd33f0434d67



## Arquitetura e Tecnologias

* **Back-end:** PHP 8.2+ / Laravel 12
* **Front-end:** Blade Templates / Tailwind CSS / Alpine.js
* **Banco de Dados:** SQLite (Arquitetura local otimizada e blindada contra corrupções físicas)
* **Build Tool:** Vite

## Módulos Principais e Regras de Negócio

### 1. Gestão Estratégica (Profit First)
* **Split Automático de Faturamento:** Subtração da reserva de investimento e distribuição automática do montante restante entre os centros de custo configurados (Operação, Reinvestimento, Lucro e Investimento).
* **Validação de Integridade (Regra de 100%):** Travas de validação no backend e frontend garantindo que a soma percentual das cotas societárias e operacionais seja estritamente igual a 100,00%.
* **Cofres Virtuais e Simulação Matemática:** 
  * Reinvestimento: Gestão de ciclo trimestral para acervo e marketing.
  * Lucro dos Sócios: Simulação e distribuição semestral proporcional ao quadro societário.
  * Investimento (CDI): Cálculo contínuo de juros compostos baseado no CDI anual e percentual negociado.
* **Termômetro de Despesas:** Monitoramento e comparação de custos fixos entre períodos para detecção de aumentos operacionais marginais.

### 2. Gestão de Prioridades de Investimento
Módulo integrado ao cofre de Reinvestimento para planejamento de longo prazo.
* **Cadastro Estratégico:** Registro de demandas com categorização, matriz de prioridade (Urgente, Importante, Baixa Prioridade), previsão orçamentária e prazos.
* **Cobertura Financeira Dinâmica:** Algoritmo que cruza o saldo real do cofre de Reinvestimento com o montante exigido pelas demandas cadastradas, gerando projeções em tempo real de superávit ou déficit para a execução dos projetos.
* **Ciclo de Vida de Projetos:** Controle de status, ordenação inteligente por urgência e geração automática de comparativo entre o valor orçado e o gasto real efetuado.

### 3. DRE Contínuo e Fluxo de Caixa
* **Separação de Receita Real:** Algoritmo de isolamento de entradas operacionais puras (PIX, cartões), separando o "dinheiro novo" de transferências internas ou saldos flutuantes.
* **Previsibilidade de Recebíveis:** Cálculo de recebíveis líquidos descontando a taxa média de antecipação e administração do período.
* **Conciliação Multi-Contas:** Monitoramento individual e consolidado de instituições financeiras distintas.

### 4. Auditoria e Conciliação Automática
* **Multiparser de Extratos:** Algoritmo de leitura inteligente compatível com múltiplos formatos de extratos bancários e relatórios de operadoras de crédito.
* **Trava Anti-Duplicidade:** Validação no nível do banco de dados para impedir a importação repetida de um mesmo lote de arquivos, garantindo a integridade absoluta da auditoria financeira.

---
**Licença:** Este projeto é de uso restrito e privado. Desenvolvido com foco em eficiência financeira e engenharia de software de alto nível por André Berkembrock.
