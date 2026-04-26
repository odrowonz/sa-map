'use strict';
/**
 * Локальный повтор рендера как в resources/js/project-md-export.js.
 * Первый аргумент — JSON из MD-архива (объединённый манифест + name, metadata, data).
 * Синхронизируйте PATTERNS/EXTENSIONS/VSCODE_ICONS_TAG с saMapFileIcons.js при изменениях.
 */
const fs = require('fs');
const nunjucks = require('nunjucks');

const VSCODE_ICONS_TAG = 'v12.17.0';

const PATTERNS = [
    { contains: 'postman', icon: 'postman' },
    { contains: 'soapui', icon: 'xml' },
    { contains: 'insomnia', icon: 'insomnia' },
    { contains: 'swagger', icon: 'swagger' },
    { contains: 'openapi', icon: 'swagger' },
    { contains: 'asyncapi', icon: 'swagger' },
    { contains: 'graphql', icon: 'graphql' },
    { contains: 'camunda', icon: 'xml' },
    { contains: 'kubernetes', icon: 'helm' },
    { contains: 'docker', icon: 'docker' },
    { contains: 'jenkins', icon: 'jenkins' },
    { contains: 'gitlab', icon: 'gitlab' },
    { contains: 'github', icon: 'github' },
    { contains: 'terraform', icon: 'terraform' },
    { contains: 'ansible', icon: 'ansible' },
    { contains: 'prometheus', icon: 'prometheus' },
    { contains: 'grafana', icon: 'grafana' },
    { contains: 'nginx', icon: 'nginx' },
    { contains: 'kafka', icon: 'apachekafka' },
    { contains: 'elastic', icon: 'elastic' },
    { contains: 'mongodb', icon: 'mongo' },
    { contains: 'postgresql', icon: 'pgsql' },
    { contains: 'mysql', icon: 'mysql' },
    { contains: 'redis', icon: 'sql' },
    { contains: 'package.json', icon: 'npm' },
    { contains: 'pom.xml', icon: 'maven' },
    { contains: 'build.gradle', icon: 'gradle' },
];

const EXTENSIONS = {
    bpmn: 'xml',
    cmmn: 'xml',
    dmn: 'xml',
    drawio: 'drawio',
    dio: 'drawio',
    uml: 'mermaid',
    puml: 'mermaid',
    plantuml: 'mermaid',
    xmi: 'eclipse',
    mdj: 'json',
    pdf: 'pdf',
    doc: 'word',
    docx: 'word',
    xls: 'excel',
    xlsx: 'excel',
    ppt: 'powerpoint',
    pptx: 'powerpoint',
    md: 'markdown',
    markdown: 'markdown',
    zip: 'zip',
    '7z': 'zip',
    rar: 'zip',
    gz: 'gnu',
    tar: 'gnu',
    sql: 'sql',
    wsdl: 'xml',
    yaml: 'yaml',
    yml: 'yaml',
    xml: 'xml',
    json: 'json',
    proto: 'protobuf',
    avro: 'avro',
    toml: 'toml',
};

function extFromName(name) {
    const n = String(name || '');
    const i = n.lastIndexOf('.');
    if (i < 0 || i === n.length - 1) {
        return '';
    }
    return n.slice(i + 1).toLowerCase();
}

function resolveIconSlug(originalName) {
    const lower = String(originalName || '').toLowerCase();
    for (const row of PATTERNS) {
        if (lower.includes(row.contains.toLowerCase())) {
            return row.icon;
        }
    }
    const ext = extFromName(lower);
    if (ext && EXTENSIONS[ext]) {
        return EXTENSIONS[ext];
    }
    return 'file';
}

function cdnUrl(icon) {
    const safe = String(icon || 'file').replace(/[^a-z0-9_]/gi, '') || 'file';
    return `https://cdn.jsdelivr.net/gh/vscode-icons/vscode-icons@${VSCODE_ICONS_TAG}/icons/file_type_${safe}.svg`;
}

function escAttr(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

function fileIconImgHtml(att) {
    const originalName = typeof att.original_name === 'string' ? att.original_name : '';
    const slug = resolveIconSlug(originalName);
    const ext = extFromName(originalName);
    const alt = ext ? `.${ext.toUpperCase()}` : 'file';
    const zipIcon = typeof att.icon_zip_path === 'string' ? att.icon_zip_path.trim() : '';
    const url = zipIcon ? zipIcon : cdnUrl(slug);
    return `<img src="${escAttr(url)}" width="16" height="16" alt="${escAttr(alt)}" style="vertical-align:middle;margin-right:0.35em;" />`;
}

function main() {
    const ctxPath = process.argv[2];
    const tplPath = process.argv[3];
    const outPath = process.argv[4];
    if (!ctxPath || !tplPath || !outPath) {
        console.error('Usage: node render-export-md.cjs <context.json> <template.njk> <out.md>');
        process.exit(1);
    }
    const ctx = JSON.parse(fs.readFileSync(ctxPath, 'utf8'));
    const tpl = fs.readFileSync(tplPath, 'utf8');
    const env = new nunjucks.Environment(null, {
        autoescape: false,
        throwOnUndefined: false,
    });
    env.addFilter('nl2br', (str) => {
        if (str == null || str === '') {
            return '';
        }
        return String(str).replace(/\n/g, '<br />\n');
    });
    env.addFilter('md_file_icon', (att) => {
        if (!att || typeof att !== 'object') {
            return '';
        }
        return fileIconImgHtml(att);
    });
    env.addFilter('trim', (s) => (s == null || s === '' ? '' : String(s).trim()));
    env.addFilter('is_string', (x) => typeof x === 'string');
    env.addFilter('is_image_att', (a) =>
        Boolean(a && typeof a === 'object' && typeof a.mime_type === 'string' && a.mime_type.startsWith('image/'))
    );
    const md = env.renderString(tpl, ctx);
    fs.writeFileSync(outPath, md, 'utf8');
    console.error('Wrote', outPath);
}

main();
