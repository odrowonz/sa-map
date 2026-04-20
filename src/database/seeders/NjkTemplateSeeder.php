<?php

namespace Database\Seeders;

use App\Models\NjkTemplate;
use Illuminate\Database\Seeder;

class NjkTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templatePath = dirname(base_path()).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'template.njk';
        $mainBody = is_readable($templatePath)
            ? (string) file_get_contents($templatePath)
            : "# {{ name | default(\"Project\") }}\n\n{# Minimal NJK placeholder #}\n";

        NjkTemplate::query()->firstOrCreate(
            [
                'filename' => 'template.njk',
                'is_system' => true,
            ],
            [
                'user_id' => null,
                'title' => 'Стандартное ТЗ (details)',
                'body' => $mainBody,
                'is_system' => true,
            ]
        );

        $brdBody = <<<'NJK'
# Бизнес-требования (BRD) — {{ name | default("Проект") }}

*Предустановленный короткий шаблон. Редактирование на сервере; для своих вариантов создайте копию в «Мои шаблоны».*

{% for lvl_conf in metadata.levels_config %}
{% if lvl_conf.n <= 3 %}
## L{{ lvl_conf.n }} — {{ lvl_conf.title }}

{{ lvl_conf.q }}

{% endif %}
{% endfor %}
NJK;

        NjkTemplate::query()->firstOrCreate(
            [
                'filename' => 'brd-minimal.njk',
                'is_system' => true,
            ],
            [
                'user_id' => null,
                'title' => 'Краткое BRD (L1–L3)',
                'body' => $brdBody,
                'is_system' => true,
            ]
        );
    }
}
