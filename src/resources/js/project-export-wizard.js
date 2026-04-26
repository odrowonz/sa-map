import JSZip from 'jszip';

const FORMAT = 'sa-map-project-export';

function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') || '' : '';
}

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

function collectIdsFromLevels(levels) {
    const out = [];
    for (const lv of levels || []) {
        for (const art of lv.artifacts || []) {
            for (const el of art.elements || []) {
                if (el && el.id) {
                    out.push(String(el.id));
                }
            }
        }
    }
    return out;
}

function idsForLevel(lv) {
    const s = new Set();
    for (const art of lv.artifacts || []) {
        for (const el of art.elements || []) {
            if (el && el.id) {
                s.add(String(el.id));
            }
        }
    }
    return [...s];
}

function idsForArtifact(art) {
    return (art.elements || []).map((e) => String(e.id)).filter(Boolean);
}

/**
 * @param {Record<string, unknown>} artifactsConfig
 * @param {number} n
 * @param {string} key
 */
function artifactLabelFromConfig(artifactsConfig, n, key) {
    const artsCfg = Array.isArray(artifactsConfig[n])
        ? artifactsConfig[n]
        : Array.isArray(artifactsConfig[String(n)])
          ? artifactsConfig[String(n)]
          : [];
    for (const art of artsCfg) {
        if (art && typeof art === 'object' && String(art.key || '') === key) {
            const lb = String(art.label || '').trim();
            return lb || key;
        }
    }
    return key;
}

/**
 * Только уровни / типы / элементы из манифеста, порядок как в файле.
 *
 * @param {unknown[]} elements
 * @param {Record<string, unknown>} artifactsConfig
 * @param {Record<number, string>} levelTitles
 */
function buildImportLevelsFromManifest(elements, artifactsConfig, levelTitles) {
    /** @type {number[]} */
    const levelOrder = [];
    /** @type {Set<number>} */
    const seenLevel = new Set();
    /** @type {Map<number, string[]>} */
    const artifactKeysByLevel = new Map();
    /** @type {Map<string, {id: string, label: string}[]>} */
    const bucket = new Map();

    function bk(lv, key) {
        return `${lv}\0${key}`;
    }
    function trackKey(lv, key) {
        if (!seenLevel.has(lv)) {
            seenLevel.add(lv);
            levelOrder.push(lv);
        }
        if (!artifactKeysByLevel.has(lv)) {
            artifactKeysByLevel.set(lv, []);
        }
        const arr = artifactKeysByLevel.get(lv);
        if (arr && !arr.includes(key)) {
            arr.push(key);
        }
    }

    for (const raw of elements || []) {
        if (!raw || typeof raw !== 'object') {
            continue;
        }
        const lv = Number(raw.level);
        if (lv < 1 || lv > 10) {
            continue;
        }
        const key = String(raw.artifact_key || '');
        if (!key) {
            continue;
        }
        const id = String(raw.id || '');
        if (!id) {
            continue;
        }
        const k = bk(lv, key);
        if (!bucket.has(k)) {
            bucket.set(k, []);
            trackKey(lv, key);
        }
        const list = bucket.get(k);
        if (list && list.some((x) => x.id === id)) {
            continue;
        }
        const c = raw.content && typeof raw.content === 'object' ? raw.content : {};
        const title = typeof c.title === 'string' ? c.title : '';
        list.push({
            id,
            label: title || id.slice(0, 8) + '…',
        });
    }

    levelOrder.sort((a, b) => a - b);
    const levels = [];
    for (const n of levelOrder) {
        const keys = artifactKeysByLevel.get(n) || [];
        const artifactNodes = [];
        for (const key of keys) {
            const rows = bucket.get(bk(n, key));
            if (!rows?.length) {
                continue;
            }
            artifactNodes.push({
                key,
                label: artifactLabelFromConfig(artifactsConfig, n, key),
                elements: rows,
            });
        }
        if (!artifactNodes.length) {
            continue;
        }
        levels.push({
            n,
            title: levelTitles[n] || `L${n}`,
            artifacts: artifactNodes,
        });
    }
    return levels;
}

/**
 * @param {File} file
 * @param {string} jsonName
 * @param {string} legacyJsonName
 */
async function readManifestElementsFromZip(file, jsonName, legacyJsonName) {
    const zip = await JSZip.loadAsync(file);
    /** @type {string|null} */
    let text = null;

    const tryFile = async (name) => {
        const f = zip.file(name);
        if (f) {
            text = await f.async('string');
        }
    };

    await tryFile(jsonName);
    if (!text) {
        await tryFile(legacyJsonName);
    }
    if (!text) {
        const names = Object.keys(zip.files).filter((n) => {
            const zf = zip.files[n];
            return zf && !zf.dir && !n.includes('/') && n.toLowerCase().endsWith('.json');
        });
        for (const n of names) {
            const f = zip.file(n);
            if (!f) {
                continue;
            }
            const t = await f.async('string');
            try {
                const d = JSON.parse(t);
                if (d && d.format === FORMAT && Array.isArray(d.elements)) {
                    text = t;
                    break;
                }
            } catch {
                /* skip */
            }
        }
    }
    if (!text) {
        throw new Error('no_manifest');
    }
    const data = JSON.parse(text);
    if (!data || data.format !== FORMAT || !Array.isArray(data.elements)) {
        throw new Error('bad_manifest');
    }
    return data.elements;
}

function mount() {
    const cfg = document.getElementById('sa-export-wizard-config');
    if (!cfg) {
        return;
    }

    const treeUrl = cfg.dataset.treeUrl || '';
    const exportUrl = cfg.dataset.exportUrl || '';
    const i18n = readJsonScript('sa-export-wizard-i18n', {});
    const t = (k, fb) => (i18n[k] != null ? i18n[k] : fb);

    const artifactsConfig = readJsonScript('sa-export-wizard-artifacts', {});
    const levelTitles = readJsonScript('sa-export-wizard-level-titles', {});

    const modal = document.getElementById('sa-export-wizard-modal');
    const treeRoot = document.getElementById('sa-export-wizard-tree');
    const titleEl = document.getElementById('sa-export-wizard-title');
    const hintEl = document.getElementById('sa-export-wizard-hint');
    const btnCancel = document.getElementById('sa-export-wizard-cancel');
    const btnContinue = document.getElementById('sa-export-wizard-continue');
    const btnClose = document.getElementById('sa-export-wizard-close');

    if (!modal || !treeRoot || !btnContinue || !btnCancel) {
        return;
    }

    /** @type {{ resolve: (v: string[]) => void, reject: (e?: Error) => void } | null} */
    let pending = null;
    /** @type {Set<string>} */
    let checked = new Set();
    /** @type {string[]} */
    let allElementIds = [];
    /** @type {unknown[]} */
    let levelsSnapshot = [];
    /** Все id из манифеста импорта (для сохранения include_in_import на сервере). */
    let lastImportManifestAllIds = [];

    function setModal(open) {
        if (open) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    function renderTree() {
        treeRoot.innerHTML = '';
        const wrap = document.createElement('div');
        wrap.className = 'space-y-1 pl-0.5';

        for (const lv of levelsSnapshot) {
            if (!lv || typeof lv !== 'object') {
                continue;
            }
            const lvBox = document.createElement('div');
            lvBox.className = 'rounded-lg border border-slate-100 bg-slate-50/80 p-2';
            const lvRow = document.createElement('div');
            lvRow.className = 'flex items-start gap-2';
            const lvCb = document.createElement('input');
            lvCb.type = 'checkbox';
            lvCb.className = 'mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600';
            const idsLv = idsForLevel(lv);
            const allLv = idsLv.length > 0 && idsLv.every((id) => checked.has(id));
            const someLv = idsLv.length > 0 && idsLv.some((id) => checked.has(id));
            lvCb.indeterminate = someLv && !allLv;
            lvCb.checked = allLv;
            lvCb.addEventListener('change', () => {
                if (lvCb.checked) {
                    idsLv.forEach((id) => checked.add(id));
                } else {
                    idsLv.forEach((id) => checked.delete(id));
                }
                renderTree();
            });
            const lvLab = document.createElement('span');
            lvLab.className = 'min-w-0 text-xs font-semibold text-slate-800';
            lvLab.textContent = `L${lv.n} · ${lv.title || ''}`;
            lvRow.appendChild(lvCb);
            lvRow.appendChild(lvLab);
            lvBox.appendChild(lvRow);

            const arts = document.createElement('div');
            arts.className = 'mt-2 ml-5 space-y-2 border-l border-slate-200 pl-3';
            for (const art of lv.artifacts || []) {
                if (!art || typeof art !== 'object') {
                    continue;
                }
                const artBox = document.createElement('div');
                const artRow = document.createElement('div');
                artRow.className = 'flex items-start gap-2';
                const artCb = document.createElement('input');
                artCb.type = 'checkbox';
                artCb.className = 'mt-0.5 h-3.5 w-3.5 shrink-0 rounded border-slate-300 text-blue-600';
                const idsArt = idsForArtifact(art);
                const allArt = idsArt.length > 0 && idsArt.every((id) => checked.has(id));
                const someArt = idsArt.length > 0 && idsArt.some((id) => checked.has(id));
                artCb.indeterminate = someArt && !allArt;
                artCb.checked = allArt;
                artCb.addEventListener('change', () => {
                    if (artCb.checked) {
                        idsArt.forEach((id) => checked.add(id));
                    } else {
                        idsArt.forEach((id) => checked.delete(id));
                    }
                    renderTree();
                });
                const artLab = document.createElement('span');
                artLab.className = 'min-w-0 text-[11px] font-medium text-slate-700';
                artLab.textContent = String(art.label || art.key || '');
                artRow.appendChild(artCb);
                artRow.appendChild(artLab);
                artBox.appendChild(artRow);

                const els = document.createElement('div');
                els.className = 'mt-1 ml-4 space-y-1';
                for (const el of art.elements || []) {
                    if (!el || !el.id) {
                        continue;
                    }
                    const row = document.createElement('label');
                    row.className = 'flex cursor-pointer items-start gap-2 py-0.5';
                    const ecb = document.createElement('input');
                    ecb.type = 'checkbox';
                    ecb.className = 'mt-0.5 h-3.5 w-3.5 shrink-0 rounded border-slate-300 text-blue-600';
                    const id = String(el.id);
                    ecb.checked = checked.has(id);
                    ecb.addEventListener('change', () => {
                        if (ecb.checked) {
                            checked.add(id);
                        } else {
                            checked.delete(id);
                        }
                        renderTree();
                    });
                    const span = document.createElement('span');
                    span.className = 'min-w-0 flex-1 truncate text-[11px] text-slate-600';
                    span.textContent = String(el.label || id);
                    span.title = String(el.label || id);
                    row.appendChild(ecb);
                    row.appendChild(span);
                    els.appendChild(row);
                }
                artBox.appendChild(els);
                arts.appendChild(artBox);
            }
            lvBox.appendChild(arts);
            wrap.appendChild(lvBox);
        }
        treeRoot.appendChild(wrap);
    }

    /**
     * @param {{ context: 'export_data'|'export_md'|'import_data', importFile?: File }} opts
     * @returns {Promise<string[]>}
     */
    function runWizard(opts) {
        return new Promise((resolve, reject) => {
            pending = { resolve, reject };
            const ctx = opts.context;
            if (ctx === 'export_data') {
                titleEl.textContent = t('wizard_title_export', 'Export data');
                hintEl.textContent = t('wizard_hint_export', 'Choose elements to include in the ZIP.');
            } else if (ctx === 'export_md') {
                titleEl.textContent = t('wizard_title_export_md', 'Export MD');
                hintEl.textContent = t('wizard_hint_export_md', 'Choose elements for the data archive, then pick a template.');
            } else {
                titleEl.textContent = t('wizard_title_import', 'Import data');
                hintEl.textContent = t('wizard_hint_import', 'Choose elements to import from the archive.');
            }

            void (async () => {
                try {
                    if (ctx !== 'import_data') {
                        lastImportManifestAllIds = [];
                    }
                    if (ctx === 'import_data') {
                        const file = opts.importFile;
                        if (!file) {
                            throw new Error('no_file');
                        }
                        const jsonName = cfg.dataset.jsonName || '';
                        const legacy = cfg.dataset.legacyJson || 'sa-map-project-export.json';
                        const rawElements = await readManifestElementsFromZip(file, jsonName, legacy);
                        levelsSnapshot = buildImportLevelsFromManifest(rawElements, artifactsConfig, levelTitles);
                        allElementIds = collectIdsFromLevels(levelsSnapshot);
                        if (allElementIds.length === 0) {
                            throw new Error('empty_manifest');
                        }
                        lastImportManifestAllIds = allElementIds.slice();
                        const flags = readJsonScript('sa-element-wizard-flags', {});
                        checked = new Set();
                        for (const id of allElementIds) {
                            const row = flags[id];
                            if (row && row.import === false) {
                                continue;
                            }
                            checked.add(String(id));
                        }
                    } else {
                        const res = await fetch(treeUrl, {
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!res.ok) {
                            throw new Error('tree_http');
                        }
                        const data = await res.json();
                        levelsSnapshot = Array.isArray(data.levels) ? data.levels : [];
                        allElementIds = Array.isArray(data.all_element_ids) ? data.all_element_ids.map(String) : collectIdsFromLevels(levelsSnapshot);
                        const flagKey = ctx === 'export_md' ? 'include_export_md' : 'include_export_data';
                        checked = new Set();
                        for (const lv of levelsSnapshot) {
                            for (const art of lv.artifacts || []) {
                                for (const el of art.elements || []) {
                                    if (el && el.id && el[flagKey] !== false) {
                                        checked.add(String(el.id));
                                    }
                                }
                            }
                        }
                    }
                    renderTree();
                    setModal(true);
                } catch (e) {
                    const p = pending;
                    pending = null;
                    if (p) {
                        p.reject(e instanceof Error ? e : new Error(String(e)));
                    }
                    const code = e instanceof Error ? e.message : String(e);
                    if (code === 'no_manifest' || code === 'bad_manifest') {
                        alert(t('wizard_err_manifest', 'Could not read project JSON in the ZIP.'));
                    } else if (code === 'empty_manifest') {
                        alert(t('wizard_err_empty', 'No importable elements in the archive.'));
                    } else {
                        alert(t('wizard_err_tree', 'Could not load selection tree.'));
                    }
                }
            })();
        });
    }

    function finish(ok) {
        const p = pending;
        pending = null;
        setModal(false);
        if (!ok) {
            lastImportManifestAllIds = [];
        }
        if (!p) {
            return;
        }
        if (ok) {
            const out = allElementIds.filter((id) => checked.has(id));
            p.resolve(out);
        } else {
            p.reject(new Error('cancel'));
        }
    }

    btnContinue.addEventListener('click', () => finish(true));
    btnCancel.addEventListener('click', () => finish(false));
    btnClose?.addEventListener('click', () => finish(false));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            finish(false);
        }
    });

    window.saRunExportWizard = runWizard;

    /** @param {string} url */
    async function downloadExportZip(url, elementIds) {
        const res = await fetch(url, {
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
        if (!res.ok) {
            throw new Error('zip');
        }
        const blob = await res.blob();
        const cd = res.headers.get('Content-Disposition');
        let name = 'export_data.zip';
        if (cd && /filename="?([^";]+)"?/i.test(cd)) {
            const m = cd.match(/filename\*?=(?:UTF-8'')?["']?([^";\n]+)/i);
            if (m) {
                name = decodeURIComponent(m[1].replace(/['"]/g, '').trim());
            }
        }
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        URL.revokeObjectURL(a.href);
        a.remove();
    }

    document.getElementById('sa-export-data-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        void (async () => {
            try {
                const ids = await runWizard({ context: 'export_data' });
                await downloadExportZip(exportUrl, ids);
            } catch {
                /* cancelled or alert */
            }
        })();
    });

    const importForm = document.getElementById('sa-import-data-form');
    const importConfirm = importForm?.dataset.confirm || '';

    importForm?.addEventListener('submit', (e) => {
        const fd = new FormData(importForm);
        const file = fd.get('archive');
        if (!(file instanceof File) || !file.size) {
            return;
        }
        if (importForm.dataset.saWizardPass === '1') {
            importForm.dataset.saWizardPass = '';
            return;
        }
        e.preventDefault();
        void (async () => {
            try {
                const ids = await runWizard({ context: 'import_data', importFile: file });
                if (importConfirm && !window.confirm(importConfirm)) {
                    return;
                }
                let hid = importForm.querySelector('input[name="import_element_ids"]');
                if (!hid) {
                    hid = document.createElement('input');
                    hid.type = 'hidden';
                    hid.name = 'import_element_ids';
                    importForm.appendChild(hid);
                }
                hid.value = JSON.stringify(ids);
                let hidM = importForm.querySelector('input[name="import_manifest_element_ids"]');
                if (!hidM) {
                    hidM = document.createElement('input');
                    hidM.type = 'hidden';
                    hidM.name = 'import_manifest_element_ids';
                    importForm.appendChild(hidM);
                }
                hidM.value = JSON.stringify(lastImportManifestAllIds);
                importForm.dataset.saWizardPass = '1';
                importForm.submit();
            } catch {
                /* cancelled */
            }
        })();
    });
}

mount();
