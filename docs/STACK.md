# Стек разработки (утверждено)

## Решение

- **Фреймворк:** [Laravel](https://laravel.com/) (последняя LTS или текущая стабильная, PHP ≥ 8.2).
- **Представление:** **Blade** — HTML-страницы отдаются с сервера; отдельное SPA (Vue/React-only) **не** является базой первого релиза.
- **БД:** **MariaDB**; продакшн — см. `hosting/sa-map-database.md` (в `.gitignore`), структура таблиц — ТЗ п. 6.
- **URL приложения:** `https://it-senior.kz/sa-map/` — в `.env` задать `APP_URL` (и при подкаталоге — настроить `asset`/`url` или `RouteServiceProvider` / `APP_URL` с путём).

## Зачем так

- Один язык на сервере (**PHP**), типовой стек для вашего хостинга.
- Laravel даёт миграции, авторизацию, валидацию, локализацию из коробки.
- Blade проще сопровождать неспециалисту, чем «фронт отдельно + API».

## Установка зависимостей

В каталоге **`src/`** уже лежит проект Laravel 11 (скелет с GitHub). Нужны **PHP 8.2+**, **Composer**, **Node.js** (для `npm run dev` / сборки Vite).

```bash
cd src
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

Подробнее: [../src/README.md](../src/README.md).

Если нужно **пересобрать** чистый Laravel с нуля: удалите содержимое `src` (кроме резервной копии) и выполните `composer create-project laravel/laravel .` в пустом `src`.

## Деплой на хостинг

- Собрать артефакты в **`build/dist/`** (см. `build/README.md`): код без `node_modules` в исходниках на сервере при необходимости собрать `npm run build` локально; залить `vendor/` через `composer install --no-dev` локально.
- На сервере в `httpdocs/sa-map/public` указывает document root **или** корень `sa-map/` с `public/` как подпапка — по настройке Plesk (часто **корень сайта** = `.../sa-map/public`).

Подробно: [HOSTING_INSTALL.md](HOSTING_INSTALL.md).

## Nunjucks

Шаблоны NJK для «Сохранить MD» хранятся в БД (таблица `sa_njk_templates`); рендер в Markdown — в браузере (подключение `nunjucks` через Vite/npm) либо уточнение в задаче на реализацию.
