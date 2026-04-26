import { EditorView, basicSetup } from 'codemirror';
import { EditorState } from '@codemirror/state';
import { StreamLanguage } from '@codemirror/language';
import { jinja2 } from '@codemirror/legacy-modes/mode/jinja2';
import nunjucks from 'nunjucks';

const jinja2Lang = StreamLanguage.define(jinja2);

const slateTheme = EditorView.theme(
    {
        '&': {
            fontSize: '13px',
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            backgroundColor: '#f8fafc',
        },
        '.cm-scroller': {
            overflow: 'auto',
            maxHeight: 'min(70vh, 520px)',
        },
        '.cm-content': {
            caretColor: '#0f172a',
            minHeight: '320px',
        },
        '.cm-gutters': {
            backgroundColor: '#f1f5f9',
            color: '#94a3b8',
            border: 'none',
            borderRight: '1px solid #e2e8f0',
        },
        '.cm-activeLineGutter': {
            backgroundColor: '#e2e8f0',
        },
        '&.cm-focused .cm-cursor': {
            borderLeftColor: '#334155',
        },
        '.cm-selectionBackground, ::selection': {
            backgroundColor: '#bfdbfe !important',
        },
        '.cm-activeLine': {
            backgroundColor: '#f1f5f9',
        },
    },
    { dark: false },
);

function validateNjk(src) {
    try {
        const env = new nunjucks.Environment(null, {
            autoescape: false,
            throwOnUndefined: false,
        });
        env.addFilter('md_file_icon', () => '');
        new nunjucks.Template(src, env, null, true);
        return { ok: true, message: '' };
    } catch (err) {
        const lineno = err.lineno ?? err.line ?? null;
        const colno = err.colno ?? err.col ?? null;
        let msg = err.message || String(err);
        if (lineno != null) {
            msg = msg + (colno != null ? ` (${lineno}:${colno})` : ` (${lineno})`);
        }
        return { ok: false, message: msg, lineno };
    }
}

function debounce(fn, ms) {
    let t = null;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
    };
}

function mount() {
    const mountEl = document.getElementById('njk-cm-mount');
    const textarea = document.getElementById('njk-body');
    const errEl = document.getElementById('njk-syntax-status');
    const form = document.getElementById('njk-template-form');

    if (!mountEl || !textarea || !form) {
        return;
    }

    const msgValid = mountEl.dataset.msgValid || '';
    const msgInvalidPrefix = mountEl.dataset.msgInvalidPrefix || '';
    const ariaLabel = mountEl.dataset.ariaLabel || 'NJK';

    const initial = textarea.value;

    const syncToTextarea = (doc) => {
        textarea.value = doc;
    };

    const showStatus = (v) => {
        if (!errEl) return;
        errEl.classList.remove('hidden', 'text-red-600', 'text-emerald-700');
        if (!v.ok) {
            errEl.classList.add('text-red-600');
            errEl.textContent = msgInvalidPrefix + v.message;
            errEl.classList.remove('hidden');
        } else {
            errEl.classList.add('text-emerald-700');
            errEl.textContent = msgValid;
            errEl.classList.remove('hidden');
        }
    };

    const runValidate = (source) => {
        const v = validateNjk(source);
        showStatus(v);
        return v;
    };

    const debouncedValidate = debounce((source) => {
        runValidate(source);
    }, 350);

    const state = EditorState.create({
        doc: initial,
        extensions: [
            basicSetup,
            jinja2Lang,
            EditorView.lineWrapping,
            slateTheme,
            EditorView.contentAttributes.of({ 'aria-label': ariaLabel }),
            EditorView.updateListener.of((u) => {
                if (!u.docChanged) return;
                const text = u.state.doc.toString();
                syncToTextarea(text);
                debouncedValidate(text);
            }),
        ],
    });

    const view = new EditorView({
        state,
        parent: mountEl,
    });

    syncToTextarea(initial);
    runValidate(initial);

    form.addEventListener('submit', (e) => {
        const text = view.state.doc.toString();
        syncToTextarea(text);
        const v = validateNjk(text);
        if (!v.ok) {
            e.preventDefault();
            showStatus(v);
            view.focus();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
