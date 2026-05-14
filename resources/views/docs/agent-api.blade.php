<x-app-layout>
    <x-slot name="title">Documentation — API Agent</x-slot>

    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <style>
        .markdown-body { font-size: .9rem; line-height: 1.7; }
        .markdown-body h1 { font-size: 1.5rem; font-weight: 700; border-bottom: 2px solid #dee2e6; padding-bottom: .4rem; margin-bottom: 1rem; }
        .markdown-body h2 { font-size: 1.15rem; font-weight: 700; border-bottom: 1px solid #dee2e6; padding-bottom: .3rem; margin-top: 2rem; margin-bottom: .75rem; }
        .markdown-body h3 { font-size: 1rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: .5rem; }
        .markdown-body p  { margin-bottom: .75rem; }
        .markdown-body ul, .markdown-body ol { margin-bottom: .75rem; padding-left: 1.5rem; }
        .markdown-body li { margin-bottom: .2rem; }
        .markdown-body pre { background: #f6f8fa; border: 1px solid #e1e4e8; border-radius: 6px; padding: 1rem; overflow-x: auto; margin-bottom: 1rem; font-size: .8rem; }
        .markdown-body code:not(pre code) { background: #f0f2f5; padding: .15em .35em; border-radius: 3px; font-size: .85em; color: #c7254e; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: .85rem; }
        .markdown-body th { background: #f6f8fa; border: 1px solid #dee2e6; padding: .45rem .75rem; text-align: left; font-weight: 600; }
        .markdown-body td { border: 1px solid #dee2e6; padding: .4rem .75rem; }
        .markdown-body tr:nth-child(even) td { background: #fafafa; }
        .markdown-body blockquote { border-left: 4px solid #0d6efd; background: #f0f5ff; padding: .5rem 1rem; margin: .75rem 0; border-radius: 0 4px 4px 0; color: #444; }
        .markdown-body hr { border: none; border-top: 1px solid #dee2e6; margin: 1.5rem 0; }
        .markdown-body a { color: #0d6efd; }

        /* Sticky TOC */
        #toc { font-size: .82rem; line-height: 1.6; }
        #toc a { color: #495057; text-decoration: none; display: block; padding: .1rem 0; }
        #toc a:hover { color: #0d6efd; }
        #toc .toc-h2 { padding-left: 0; font-weight: 600; }
        #toc .toc-h3 { padding-left: 1rem; color: #6c757d; }
    </style>
    @endpush

    <div class="row g-4">

        {{-- Table des matières (sticky) --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card border-0 shadow-sm" style="position:sticky;top:1rem">
                <div class="card-header bg-white py-2 small fw-semibold">
                    <i class="bi bi-list-ul me-1 text-primary"></i>Sommaire
                </div>
                <div class="card-body py-2 px-3">
                    <div id="toc"></div>
                </div>
                <div class="card-footer bg-white py-2">
                    <a href="{{ base_path('docs/agent-api.md') }}" class="d-none"></a>
                    <a href="https://github.com/VOTRE-COMPTE/SynoManager/blob/main/docs/agent-api.md"
                       target="_blank" class="btn btn-sm btn-outline-secondary w-100 py-0">
                        <i class="bi bi-github me-1"></i>Voir sur GitHub
                    </a>
                </div>
            </div>
        </div>

        {{-- Contenu markdown --}}
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text text-primary"></i>
                    <span class="fw-semibold small">docs/agent-api.md</span>
                    <span class="badge bg-info ms-auto">référence agent</span>
                </div>
                <div class="card-body px-4 py-3">
                    <div id="md-content" class="markdown-body"></div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/marked@12/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
    (function () {
        const markdown = {{ Illuminate\Support\Js::from($markdown) }};

        // Configure marked avec highlight.js
        marked.setOptions({
            highlight: function (code, lang) {
                if (lang && hljs.getLanguage(lang)) {
                    return hljs.highlight(code, { language: lang }).value;
                }
                return hljs.highlightAuto(code).value;
            },
            breaks: false,
            gfm: true,
        });

        // Render
        const container = document.getElementById('md-content');
        container.innerHTML = marked.parse(markdown);

        // Ajouter des IDs sur les titres pour les ancres
        container.querySelectorAll('h1, h2, h3').forEach(function (el) {
            const id = el.textContent
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-');
            el.id = id;
        });

        // Générer la table des matières
        const toc   = document.getElementById('toc');
        const heads = container.querySelectorAll('h2, h3');
        heads.forEach(function (el) {
            const a = document.createElement('a');
            a.href      = '#' + el.id;
            a.textContent = el.textContent;
            a.className = el.tagName === 'H2' ? 'toc-h2' : 'toc-h3';
            toc.appendChild(a);
        });
    }());
    </script>
    @endpush

</x-app-layout>
