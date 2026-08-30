# Contexto do projeto: Savings Dashboard (módulo Finanças de um futuro dashboard pessoal maior)

## Objetivo
Projeto pessoal de aprendizado, não é produção. Dois objetivos principais:
1. Praticar arquitetura de sistemas: Clean/Hexagonal Architecture, Domain Events, CQRS.
2. Construir algo genuinamente útil pra minha vida (acompanhar minhas metas de poupança reais, incluindo fundo de imigração).

Esse módulo (Finanças) é o primeiro de um dashboard pessoal maior planejado, que futuramente vai incluir outros módulos (imigração, idiomas, hábitos) como bounded contexts separados, integrados via eventos. Por enquanto, o foco é só esse módulo, funcionando ponta a ponta, antes de expandir.

## Meu nível
- Dev com ~3.5 anos de experiência, nível júnior no mercado europeu.
- Já conheço Laravel (por isso essa stack, não Symfony — Symfony é o que uso no trabalho, aprofundo ele lá mesmo, no dia a dia).
- Quero entender e questionar cada decisão, não só ter código funcionando. Prefira me explicar o "porquê" antes de me dar a solução pronta, ou me guiar a chegar nela, em vez de só resolver direto.

## Escopo da v1: Acompanhamento de Metas de Poupança
Fora de escopo por enquanto (fase futura): categorização de despesas, múltiplas contas, gastos/transações negativas, orçamento mensal.

### Entidades
- **SavingsGoal**: id, title, targetAmount, targetDate (opcional), currentAmount, status (ACTIVE, COMPLETED)
- **Contribution**: id, savingsGoalId, amount, date, note (opcional)

### Regras de domínio
- Ao registrar uma Contribution, SavingsGoal.currentAmount incrementa.
- Se currentAmount >= targetAmount → dispara evento GoalCompleted.
- Se há targetDate, o domínio calcula ritmo necessário: (targetAmount - currentAmount) / dias restantes.
- Cruzar marcos de progresso (25%, 50%, 75%) dispara evento MilestoneReached.

### Fluxo principal
AddContributionCommand (savingsGoalId, amount, date)
→ carrega SavingsGoal
→ goal.addContribution(amount) [método de domínio]
→ currentAmount += amount
→ verifica milestone → MilestoneReached
→ verifica meta batida → GoalCompleted
→ salva
→ despacha eventos coletados (síncrono por enquanto, nessa v1 simples)


## Arquitetura em camadas (Clean/Hexagonal), adaptada pro Laravel
- `app/Domain/` — regras de negócio puras, SEM dependência do Laravel/Eloquent. Testável isoladamente. Contém: SavingsGoal, Contribution, os Domain Events.
- `app/Application/` — Command/Query handlers (CQRS leve), orquestram o domínio.
- `app/Infrastructure/` — Eloquent Models + Repositories que implementam interfaces do Domain, Queue listeners, Cache.
- `app/Http/Controllers/` — controllers finos, só chamam handlers.
- Regra de ouro: dependência aponta só pra dentro. Domain nunca importa nada de Eloquent/Laravel.
- Cuidado especial: Eloquent por padrão é Active Record (entidade = model que se salva sozinha). Isso vai contra a separação que queremos — manter classes de Domain como PHP puro, separadas dos Models Eloquent, com um Repository na Infrastructure fazendo a conversão Model ↔ entidade de Domain.

## Tipo de projeto
Backend primeiro e prioritário (API REST). Frontend é opcional e só entra como "recompensa" depois que o backend estiver funcionando ponta a ponta — não é trabalho paralelo constante, e não é o foco de aprendizado aqui (frontend/React já é meu ponto forte).

## Stack e infra (sem Docker, tudo local no Windows)
- Banco: MySQL via XAMPP/phpMyAdmin
- Fila: RabbitMQ (instalado nativo no Windows) — via Laravel Queue, driver customizado ou pacote de suporte AMQP
- Cache: Redis via Memurai (fork Windows-compatible), client `predis/predis`
- Testes: PHPUnit

## Como eu quero trabalhar
- Domínio primeiro, testado, antes de qualquer infra (Eloquent/Laravel entram por último em cada feature nova).
- Sempre teste antes da implementação quando fizer sentido (TDD leve no domínio).
- Quando eu pedir pra implementar algo, prefiro entender a decisão de arquitetura por trás antes de aceitar — pode me perguntar de volta ou me dar opções com trade-offs em vez de só resolver direto.
- Ritmo do projeto é livre/sem pressão — não precisa ser todo dia. Ao pausar, atualizar a seção "Status atual" com onde exatamente parei.

## Contexto de projetos anteriores (descartados/substituídos)
- Havia um projeto anterior de progressão de RPG (Player/Quest/Skill fictício) em Symfony que foi descartado em favor deste, mais útil na prática.
- Há também um projeto de budget API em Symfony, pausado, anterior a este.

## Status atual
**Camada de domínio da v1 completa e testada** (31 testes verdes, `./vendor/bin/phpunit`). Git limpo, remote `github.com/JoaoVictorLiz/personal-dashboard`.

Implementado em `app/Domain/`:
- `Shared/Money.php` — Value Object (centavos int + moeda), imutável. `fromCents`, `add`, `subtract`, `equals`, `isGreaterThanOrEqualTo`. Recusa moedas diferentes; proíbe negativo.
- `Shared/DomainEvent.php` — interface-marcador.
- `SavingsGoal/SavingsGoal.php` — raiz do agregado. Construtor privado + `create()`. Guarda alvo positivo. `addContribution(id, Money, date, ?note)` → cria `Contribution`, incrementa `currentAmount`, coleta eventos. Marcos 25/50/75 (`MilestoneReached`, 1 por marco cruzado) e conclusão (`GoalCompleted`, uma vez só via guarda `=== ACTIVE`). `requiredDailyPace(today)`: `?Money`, arredonda pra cima, `max(1, dias)` pra prazo vencido. `releaseEvents()` esvazia a lista.
- `SavingsGoal/Contribution.php` — entidade filha, imutável.
- `SavingsGoal/SavingsGoalStatus.php` — enum backed (active/completed).
- `SavingsGoal/Event/GoalCompleted.php`, `MilestoneReached.php`.

**Camada Application: caso de uso `AddContribution` completo e testado.**
- `app/Domain/SavingsGoal/SavingsGoalRepository.php` — interface (porta), no Domain. `get(id): SavingsGoal` (lança `SavingsGoalNotFound`), `save(SavingsGoal)`.
- `app/Domain/SavingsGoal/SavingsGoalNotFound.php` — exceção.
- `app/Domain/Shared/EventDispatcher.php` — interface (porta). `dispatch(DomainEvent ...$events)`.
- `app/Application/SavingsGoal/AddContributionCommand.php` — DTO (savingsGoalId, contributionId, Money, date, ?note). Controller monta a partir do request, incl. gerar o UUID.
- `app/Application/SavingsGoal/AddContributionHandler.php` — orquestra: `get` → `addContribution` → `save` → `dispatch(...releaseEvents())`. Zero regra de negócio.
- `SavingsGoal::id()` adicionado.
- Testado com `tests/Fakes/InMemorySavingsGoalRepository.php` + `tests/Fakes/RecordingEventDispatcher.php`.

Falta ainda: `SavingsGoal::reconstitute()` (recriar do banco sem rodar regras de criação) — entra junto com o repositório Eloquent.

**Próximo passo (pausado aqui em 2026-08-30):** camada Infrastructure. Nessa ordem:
1. Migrations: tabelas `savings_goals` e `contributions`.
2. Models Eloquent (`app/Infrastructure/Persistence/Eloquent/`) — só mapeamento, sem regra.
3. `EloquentSavingsGoalRepository implements SavingsGoalRepository` — converte Model ↔ entidade de domínio (precisa do `reconstitute()`).
4. `LaravelEventDispatcher implements EventDispatcher` — joga no event bus do Laravel.
5. Service provider com os `bind()` das interfaces → implementações.
Depois disso: Http (controller fino + rota `POST /savings-goals/{id}/contributions`), e uma query de leitura pra listar metas. Só então, frontend (opcional).