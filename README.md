# Notely

Local full-stack notes application:

- Backend: PHP 8.2 + Slim 4
- Frontend: Vanilla JS SPA
- Data: PostgreSQL + Redis + MinIO

## Quick Start

1. Copy env file:

   `cp .env.example .env`

2. Start services:

   `docker compose up --build -d`

3. Install backend dependencies:

   `docker compose exec php composer install`

4. Run migrations:

   `docker compose exec php vendor/bin/phinx migrate`

5. Open:

   - App: <http://localhost:8081>
   - API health: <http://localhost:8081/health>
