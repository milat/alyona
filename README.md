# Alyona

Aplicação web para controle financeiro doméstico com compartilhamento por grupo (household), gestão de categorias e orçamento, compras e entradas mensais.

## Stack
- PHP 8.2+
- Laravel 12
- Livewire 3
- MySQL
- Bootstrap 5 + Bootstrap Icons
- Chart.js

## Funcionalidades
- Autenticação:
  - Cadastro, login, logout e opção de manter conectado.
- Household (grupo):
  - Cada usuário participa de apenas um grupo.
  - Criação de grupo e convite por e-mail.
  - Convite com aceite/recusa na home.
- Período orçamentário por household:
  - Mês calendário (1º ao último dia).
  - 5º dia útil até o dia anterior ao próximo 5º dia útil.
- Categorias:
  - CRUD de categorias com cor e status (ativa/inativa).
  - Orçamento por categoria com histórico por vigência (`effective_at`).
- Compras:
  - Cadastro via modal (UX de web app sem reload de página).
  - Parcelamento com geração automática de compras futuras.
  - Regra especial para household no 5º dia útil: 1 parcela por período orçamentário.
  - Listagem com filtros por mês e categoria, paginação e exclusão.
- Entradas:
  - CRUD de entradas mensais por usuário no household.
- Dashboard:
  - Resumo do período com total gasto, orçamento, saldo e gráfico por categoria.

## Regras de negócio principais
- Orçamento de categoria vale a partir do momento da alteração e mantém histórico.
- Relatórios usam o orçamento vigente no período consultado.
- Apenas categorias ativas aparecem no cadastro de compra.
- Filtro de mês:
  - mês atual,
  - até 12 meses anteriores,
  - até 3 meses seguintes,
  - considerando meses com dados.

## Instalação
1. Configure o ambiente Docker:
```bash
cp docker/.env.example docker/.env
```
2. Edite `docker/.env` e ajuste:
- `PROJECT_PATH`: caminho absoluto do projeto na sua máquina
- portas (`PORT_80`, `PORT_443`, `PORT_3306`) se necessário
- credenciais do MySQL

3. Suba os containers:
```bash
docker compose --env-file docker/.env -f docker/docker-compose.yml up -d --build
```

4. Configure o ambiente Laravel:
```bash
cp .env.example .env
```

5. Instale dependências e inicialize a aplicação dentro do container PHP:
```bash
docker exec -it alyona-php composer install
docker exec -it alyona-php php artisan key:generate
docker exec -it alyona-php php artisan migrate --seed
```

## Execução em desenvolvimento
Com os containers em execução, acesse:
- aplicação: `http://localhost:${PORT_80}`
- banco MySQL: `localhost:${PORT_3306}`

Para parar os containers:
```bash
docker compose --env-file docker/.env -f docker/docker-compose.yml down
```

## Seeders
`PaymentMethodSeeder` cadastra automaticamente:
- Débito
- Crédito
- Pix
- Em espécie

## Testes
```bash
docker exec -it alyona-php php artisan test
```

## Licença
Projeto privado de aplicação web. Adapte esta seção conforme a política do repositório.
