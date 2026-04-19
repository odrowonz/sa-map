# SA Map — карта системного аналитика (проект IT Senior)

Репозиторий материалов и прототипов для веб-платформы по ТЗ в `docs/TZ_платформа_карты_системного_аналитика.md`.

**Продакшн-URL (зафиксировано в ТЗ):** https://it-senior.kz/sa-map/

## Структура каталогов

| Каталог / файл | Назначение |
|----------------|------------|
| **`docs/`** | Техническое задание, переписка, инструкции |
| **`docs/reference/`** | Эталонная карта уровней L1–L10 (`system_analyst_map.md`) |
| **`docs/hosting/`** | Заметки по хостингу; учётные данные БД — `sa-map-database.md` (в `.gitignore`) |
| **`docs/HOSTING_INSTALL.md`** | Пошаговая установка приложения на хостинг (для начинающих) |
| **`prototype/`** | Статические HTML-прототипы (`index.html`, `index_stage1.html`) |
| **`contracts/`** | JSON Schema / контракт экспорта (`sa_map_contract.json`) |
| **`templates/`** | Примеры шаблонов Nunjucks (`template.njk`) |
| **`src/`** | **Laravel 11** — исходный код приложения (`composer install` внутри `src/`) |
| **`build/dist/`** | **Выход сборки** — дистрибутив для хостинга, **не** ручное редактирование (см. `build/README.md`) |

## Быстрые ссылки

- ТЗ: [docs/TZ_платформа_карты_системного_аналитика.md](docs/TZ_платформа_карты_системного_аналитика.md)
- **Стек (Laravel + Blade):** [docs/STACK.md](docs/STACK.md) · [src/README.md](src/README.md) (запуск)
- Установка на хостинг: [docs/HOSTING_INSTALL.md](docs/HOSTING_INSTALL.md)
