# SA Map — Laravel

Скелет **Laravel 11** развёрнут в этом каталоге. Зависимостей (`vendor/`) в репозитории нет — их нужно установить на машине с **PHP 8.2+** и **Composer**.

## Быстрый старт (локально)

```bash
cd src
composer install
copy .env.example .env   # Windows: copy; Linux/mac: cp
php artisan key:generate
```

Настройте `.env`: для локальной MariaDB/MySQL укажите `DB_*` (можно взять значения из `../docs/hosting/sa-map-database.md`). Либо временно используйте SQLite: раскомментируйте в `.env` блок sqlite из комментария в `.env.example` и создайте файл `database/database.sqlite`.

```bash
php artisan migrate
php artisan serve
```

Откройте http://127.0.0.1:8000 — должна открыться заглушка «Карта системного аналитика».

Фронт по умолчанию использует Vite: для стилей в dev выполните `npm install` и `npm run dev` (в отдельном терминале).

## Что уже сделано в коде

- Таблица пользователей переименована в **`sa_users`** (+ поле `locale`), модель `User` настроена.
- Миграция **`2026_04_11_120000_create_sa_map_domain_tables`**: `sa_projects`, `sa_elements`, `sa_element_upstream`, `sa_attachments`, `sa_attachment_element`, `sa_njk_templates` (по ТЗ, п. 6).
- Стартовая страница Blade: `resources/views/sa-map/home.blade.php`.
- В `.env.example` заданы ориентиры под продакшн: `APP_URL=https://it-senior.kz/sa-map`, БД `itsenior_sa_map`.

## Документация проекта

- Стек и деплой: [../docs/STACK.md](../docs/STACK.md)
- ТЗ: [../docs/TZ_платформа_карты_системного_аналитика.md](../docs/TZ_платформа_карты_системного_аналитика.md)
