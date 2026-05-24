# LiteTracker Engine

## Production Install

1. Clone the repository.

2. Copy the environment file:

```bash
cp .env.example .env
```

3. Start Docker:

```bash
docker compose up -d --build
```

4. Import the production database:

```bash
docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" lite' < database/litetracker.sql
```

5. Login:

```text
URL: http://localhost:8094/
Username: admin
Password: change-me
```

The first admin login is forced to change the temporary password.
