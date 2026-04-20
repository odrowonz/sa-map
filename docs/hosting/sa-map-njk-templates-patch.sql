-- SA Map: предустановленные шаблоны NJK (`user_id` IS NULL, `is_system` = 1).
-- Назначение: внести/обновить корпоративные шаблоны без `php artisan db:seed`.
-- Выполните в нужной БД (phpMyAdmin → вкладка SQL). Кодировка: utf8mb4.
--
-- Идемпотентность:
--   • UPDATE обновляет title/body у существующих системных строк (повторный выпуск продукта).
--   • INSERT … WHERE NOT EXISTS добавляет строку, если её ещё нет.
-- Логика совпадает с `Database\Seeders\NjkTemplateSeeder`.
--
-- Таблица `sa_njk_templates` должна существовать (см. `sa-map-mysql-install.sql` или миграции).

SET NAMES utf8mb4;

SET @body_template_njk := '# Техническое задание: {{ name | default("Системный анализ") }}
*Дата генерации: {{ metadata.export_date }}*

{# Функция-хелпер для проверки на массив (в Nunjucks нет встроенной) #}
{# Предполагаем, что шаблонизатор запущен с расширением is_array #}

{% for lvl_conf in metadata.levels_config %}
{% set lvl_id = "L" + lvl_conf.n %}
<details>
  <summary><b>LEVEL {{ lvl_conf.n }} — {{ lvl_conf.title }}</b></summary>
  
  <p><i>{{ lvl_conf.q }}</i></p>

  {# Пробегаемся по полям уровня, заданным в конфигурации #}
  {% for field in lvl_conf.fields %}
    {% set field_key = lvl_id + "_" + field.name %}
    {% set field_value = data[field_key] %}

    {% if field_value %}
      <h3>{{ field.name }}</h3>
      
      {# ЛОГИКА ОТОБРАЖЕНИЯ СХЕМ (если поле помечено isArray и содержит Path) #}
      {% if field.isArray and "Path" in field.name %}
        {% for path in field_value %}
          ![Схема ({{ field.name }})]({{ path }})
        {% endfor %}
      
      {# ЛОГИКА ОТОБРАЖЕНИЯ СПИСКОВ #}
      {% elif field.isArray %}
        <ul>
          {% for item in field_value %}
            <li>{{ item }}</li>
          {% endfor %}
        </ul>
      
      {# ОБЫЧНЫЙ ТЕКСТ #}
      {% else %}
        <p>{{ field_value | nl2br }}</p>
      {% endif %}
    {% endif %}

  {% endfor %}
</details>
{% endfor %}

---
*Сгенерировано автоматически на основе SA Operating Map v3.*';

SET @body_brd_minimal := '# Бизнес-требования (BRD) — {{ name | default("Проект") }}

*Предустановленный короткий шаблон. Редактирование на сервере; для своих вариантов создайте копию в «Мои шаблоны».*

{% for lvl_conf in metadata.levels_config %}
{% if lvl_conf.n <= 3 %}
## L{{ lvl_conf.n }} — {{ lvl_conf.title }}

{{ lvl_conf.q }}

{% endif %}
{% endfor %}';

-- ---------------------------------------------------------------------------
-- template.njk — полное ТЗ (details)
-- ---------------------------------------------------------------------------

UPDATE `sa_njk_templates`
SET
  `title` = 'Стандартное ТЗ (details)',
  `body` = @body_template_njk,
  `user_id` = NULL,
  `is_system` = 1,
  `updated_at` = NOW()
WHERE `filename` = 'template.njk' AND `is_system` = 1;

INSERT INTO `sa_njk_templates` (`user_id`, `title`, `filename`, `body`, `is_system`, `created_at`, `updated_at`)
SELECT NULL, 'Стандартное ТЗ (details)', 'template.njk', @body_template_njk, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `sa_njk_templates` t WHERE t.`filename` = 'template.njk' AND t.`is_system` = 1 LIMIT 1
);

-- ---------------------------------------------------------------------------
-- brd-minimal.njk — краткое BRD (L1–L3)
-- ---------------------------------------------------------------------------

UPDATE `sa_njk_templates`
SET
  `title` = 'Краткое BRD (L1–L3)',
  `body` = @body_brd_minimal,
  `user_id` = NULL,
  `is_system` = 1,
  `updated_at` = NOW()
WHERE `filename` = 'brd-minimal.njk' AND `is_system` = 1;

INSERT INTO `sa_njk_templates` (`user_id`, `title`, `filename`, `body`, `is_system`, `created_at`, `updated_at`)
SELECT NULL, 'Краткое BRD (L1–L3)', 'brd-minimal.njk', @body_brd_minimal, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `sa_njk_templates` t WHERE t.`filename` = 'brd-minimal.njk' AND t.`is_system` = 1 LIMIT 1
);
