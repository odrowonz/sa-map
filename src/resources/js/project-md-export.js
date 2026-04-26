import JSZip from 'jszip';
import nunjucks from 'nunjucks';
import renderExportMdCjs from './scripts/render-export-md.cjs?raw';
import replayMdHtml from './scripts/replay-md.html?raw';
import { fileIconImgHtml } from './saMapFileIcons';

function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') || '' : '';
}

function mount() {
    const root = document.getElementById('sa-md-export-config');
    if (!root) {
        return;
    }

    const exportPost = root.dataset.exportPost || '';
    const exportPostMd =
        exportPost && !/[?&]md=1(?:&|$)/.test(exportPost)
            ? exportPost + (exportPost.includes('?') ? '&' : '?') + 'md=1'
            : exportPost;
    const jsonName = root.dataset.jsonName || 'sa-map-project-export.json';
    const mdName = root.dataset.mdName || 'sa-map-project-export.md';
    const templateUrlTpl = root.dataset.templateUrlTpl || '';
    function readJsonScript(id, fallback) {
        const el = document.getElementById(id);
        if (!el) {
            return fallback;
        }
        try {
            return JSON.parse(el.textContent.trim());
        } catch {
            return fallback;
        }
    }

    let i18n = readJsonScript('sa-md-export-i18n', {});
    if (!i18n || typeof i18n !== 'object') {
        i18n = {};
    }
    const t = (k, fb) => (i18n[k] != null ? i18n[k] : fb);

    function formatLegacyExportDate(iso) {
        if (iso == null || typeof iso !== 'string') {
            return '';
        }
        return iso.replace('T', ' ').slice(0, 25);
    }

    function elementToDataRow(el) {
        if (!el || typeof el !== 'object') {
            return { title: '', body: '', attachments: [] };
        }
        const c = el.content && typeof el.content === 'object' ? el.content : {};
        const atts = Array.isArray(el.attachments) ? el.attachments : [];
        return {
            title: typeof c.title === 'string' ? c.title : '',
            body: typeof c.body === 'string' ? c.body : '',
            attachments: atts
                .map((a) => (a && typeof a === 'object' ? a : null))
                .filter(Boolean)
                .map((a) => ({
                    id: a.id,
                    original_name: a.original_name,
                    zip_path: a.zip_path,
                    mime_type: a.mime_type,
                    kind: a.kind,
                    icon_zip_path: a.icon_zip_path,
                })),
        };
    }

    function rowIsEmpty(row) {
        const t = typeof row.title === 'string' ? row.title.trim() : '';
        const b = typeof row.body === 'string' ? row.body.trim() : '';
        const at = Array.isArray(row.attachments) && row.attachments.length > 0;
        return !t && !b && !at;
    }

    function pathLikeAttachmentUrls(el) {
        const out = [];
        const atts = Array.isArray(el.attachments) ? el.attachments : [];
        for (const att of atts) {
            const zp = typeof att.zip_path === 'string' ? att.zip_path : '';
            if (!zp) {
                continue;
            }
            const mt = typeof att.mime_type === 'string' ? att.mime_type : '';
            if (mt.startsWith('image/')) {
                out.push(zp);
            }
        }
        return out;
    }

    function buildLegacyFlatData(manifest, bridge) {
        const byKey = {};
        for (const el of manifest.elements || []) {
            const k = el.artifact_key;
            if (typeof k !== 'string') {
                continue;
            }
            if (!byKey[k]) {
                byKey[k] = [];
            }
            byKey[k].push(el);
        }
        const data = {};
        for (const lvl of bridge.levels_config || []) {
            const lid = `L${lvl.n}`;
            for (const field of lvl.fields || []) {
                const fieldKey = `${lid}_${field.name}`;
                const list = byKey[fieldKey] || [];
                if (!list.length) {
                    continue;
                }
                const isPathField = typeof field.name === 'string' && field.name.indexOf('Path') !== -1;
                if (field.isArray) {
                    if (isPathField) {
                        const paths = [];
                        for (const el of list) {
                            paths.push.apply(paths, pathLikeAttachmentUrls(el));
                        }
                        if (paths.length) {
                            data[fieldKey] = paths;
                        } else {
                            const rows = list.map(elementToDataRow).filter((r) => !rowIsEmpty(r));
                            if (rows.length) {
                                data[fieldKey] = rows;
                            }
                        }
                    } else {
                        const rows = list.map(elementToDataRow).filter((r) => !rowIsEmpty(r));
                        if (rows.length) {
                            data[fieldKey] = rows;
                        }
                    }
                } else {
                    const one = elementToDataRow(list[0]);
                    if (!rowIsEmpty(one)) {
                        data[fieldKey] = one;
                    }
                }
            }
        }
        return data;
    }

    function nunjucksRenderContext(manifest, bridgeJson) {
        let bridge = null;
        try {
            bridge = typeof bridgeJson === 'string' && bridgeJson.length ? JSON.parse(bridgeJson) : null;
        } catch {
            bridge = null;
        }
        if (!bridge || !Array.isArray(bridge.levels_config)) {
            if (Array.isArray(manifest.levels_config)) {
                bridge = { levels_config: manifest.levels_config };
            } else {
                return manifest;
            }
        }
        const levelsCfg = Array.isArray(manifest.levels_config) ? manifest.levels_config : bridge.levels_config;
        const bridgeForData = { levels_config: levelsCfg };
        return {
            ...manifest,
            metadata: {
                export_date: formatLegacyExportDate(manifest.exported_at),
                levels_config: levelsCfg,
            },
            data: buildLegacyFlatData(manifest, bridgeForData),
        };
    }

    const openBtn = document.getElementById('sa-md-export-open');
    const modal = document.getElementById('sa-md-export-modal');
    const closeBtn = document.getElementById('sa-md-export-close');
    const cancelBtn = document.getElementById('sa-md-export-cancel');
    const runBtn = document.getElementById('sa-md-export-run');
    const select = document.getElementById('sa-md-export-template');
    const errEl = document.getElementById('sa-md-export-error');

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
    env.addFilter('is_image_att', (a) => Boolean(a && typeof a === 'object' && typeof a.mime_type === 'string' && a.mime_type.startsWith('image/')));

    function setModal(open) {
        if (!modal) {
            return;
        }
        if (open) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (!open && errEl) {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }
    }

    function showErr(msg) {
        if (!errEl) {
            return;
        }
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    }

    openBtn?.addEventListener('click', async () => {
        if (typeof window.saRunExportWizard === 'function') {
            try {
                window.__saMdExportElementIds = await window.saRunExportWizard({ context: 'export_md' });
            } catch {
                return;
            }
        }
        setModal(true);
    });
    closeBtn?.addEventListener('click', () => setModal(false));
    cancelBtn?.addEventListener('click', () => setModal(false));
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) {
            setModal(false);
        }
    });

    runBtn?.addEventListener('click', async () => {
        const tid = select?.value || '';
        if (!tid) {
            showErr(t('pick_template', 'Pick a template.'));
            return;
        }
        if (!exportPostMd || !templateUrlTpl) {
            showErr(t('config', 'Missing export configuration.'));
            return;
        }

        runBtn.disabled = true;
        const prevLabel = runBtn.textContent;
        runBtn.textContent = t('busy', 'Working…');
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('hidden');
        }

        try {
            const elementIds = Array.isArray(window.__saMdExportElementIds) ? window.__saMdExportElementIds : [];
            const zipRes = await fetch(exportPostMd, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/zip',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ element_ids: elementIds }),
            });
            if (!zipRes.ok) {
                throw new Error(t('error_zip', 'Could not build export archive.'));
            }
            const zipBuf = await zipRes.arrayBuffer();
            const zip = await JSZip.loadAsync(zipBuf);
            const jsonEntry = zip.file(jsonName);
            if (!jsonEntry) {
                throw new Error(t('error_json', 'Export ZIP is missing JSON manifest.'));
            }
            const jsonText = await jsonEntry.async('string');
            let manifest;
            try {
                manifest = JSON.parse(jsonText);
            } catch {
                throw new Error(t('error_json_parse', 'Invalid JSON in export.'));
            }

            const slug =
                manifest && manifest.project && typeof manifest.project.slug === 'string' && manifest.project.slug
                    ? String(manifest.project.slug).replace(/[^a-zA-Z0-9._-]+/g, '-')
                    : 'project';

            const tplUrl = templateUrlTpl.replace('__TEMPLATE_ID__', encodeURIComponent(tid));
            const tplRes = await fetch(tplUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!tplRes.ok) {
                throw new Error(t('error_template', 'Could not load template.'));
            }
            const tplJson = await tplRes.json();
            const body = tplJson.body;
            if (typeof body !== 'string') {
                throw new Error(t('error_template', 'Could not load template.'));
            }
            let tplZipName =
                typeof tplJson.filename === 'string' && tplJson.filename.trim() ? tplJson.filename.trim() : 'template.njk';
            tplZipName = tplZipName.replace(/^.*[/\\]/, '').replace(/\.\./g, '_');
            if (!tplZipName.toLowerCase().endsWith('.njk')) {
                tplZipName = `${tplZipName.replace(/\.+$/g, '') || 'template'}.njk`;
            }
            zip.file(tplZipName, body);

            const bridgeEl = document.getElementById('sa-md-export-bridge');
            const bridgeJson = bridgeEl ? bridgeEl.textContent.trim() : '';
            const ctx = nunjucksRenderContext(manifest, bridgeJson);
            zip.file(jsonName, JSON.stringify(ctx, null, 2));
            zip.file('render-export-md.cjs', renderExportMdCjs);
            zip.file('replay-md.html', replayMdHtml.replaceAll('__SA_EXPORT_SLUG__', slug));
            zip.file(
                'README-LOCAL-MD.txt',
                [
                    'Локальный повтор рендера (как кнопка «Экспорт MD» в приложении)',
                    '==============================================================',
                    '',
                    'В архиве один JSON для шаблона и импорта:',
                    `- ${jsonName} — манифест (format, version, project, elements, levels_config…), metadata и data: data[field] — { title, body, attachments } или массив таких (или string[] у полей *Path), склейка в Markdown в шаблоне .njk. Импорт — только манифест; лишние ключи игнорируются.`,
                    `- ${tplZipName} — выбранный шаблон.`,
                    '- replay-md.html — браузер: правки в редакторе → «Прогон Nunjucks»; в Chromium «Привязать» JSON и .njk — чтение с диска при каждом прогоне.',
                    '- render-export-md.cjs — то же через Node (npm i nunjucks@3.2.4).',
                    '',
                    'Без Node: откройте replay-md.html, укажите этот JSON и .njk.',
                    '',
                    'С Node:',
                    `  node render-export-md.cjs "${jsonName}" "${tplZipName}" "${slug}-local.md"`,
                    '',
                    'Архив «Экспорт данных» (*_data.zip) содержит только манифест импорта (без metadata/data).',
                    '',
                    'Бит-в-бит: nunjucks ^3.2.4 и фильтры как в репозитории; после правок в saMapFileIcons пересоберите фронт и заново экспортируйте MD.',
                ].join('\n'),
            );

            let md;
            try {
                md = env.renderString(body, ctx);
            } catch (e) {
                const msg = e && e.message ? String(e.message) : String(e);
                throw new Error(t('error_render', 'Nunjucks error:') + ' ' + msg);
            }

            zip.file(mdName, md);
            const outBlob = await zip.generateAsync({ type: 'blob' });

            const filename = `${slug}.zip`;

            const a = document.createElement('a');
            a.href = URL.createObjectURL(outBlob);
            a.download = filename;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            URL.revokeObjectURL(a.href);
            a.remove();
            setModal(false);
        } catch (e) {
            showErr(e instanceof Error ? e.message : t('error_network', 'Request failed.'));
        } finally {
            runBtn.disabled = false;
            runBtn.textContent = prevLabel;
        }
    });
}

mount();
