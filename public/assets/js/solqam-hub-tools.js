(function () {
    const search = document.getElementById('hubPageSearch');
    const exportBtn = document.getElementById('hubExcelExport');
    const content = document.querySelector('.hub-content');
    if (!content) return;

    let hint = document.getElementById('hubSearchHint');
    if (!hint) {
        hint = document.createElement('p');
        hint.id = 'hubSearchHint';
        hint.className = 'small text-muted mb-3 d-none';
        content.insertBefore(hint, content.firstChild);
    }

    function cellText(el) {
        if (!el) return '';
        const clone = el.cloneNode(true);
        clone.querySelectorAll('script, style, button, input, select, textarea, canvas, svg, .btn').forEach(function (n) {
            n.remove();
        });
        return (clone.innerText || clone.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function searchableBlocks() {
        const set = [];
        content.querySelectorAll('table tbody tr').forEach(function (el) { set.push(el); });
        content.querySelectorAll('.list-group-item, .ops-stat, .kpi-tile, .command-panel, .card-custom, .console-card').forEach(function (el) {
            if (el.querySelector('table')) return;
            set.push(el);
        });
        return set;
    }

    function isPlaceholderRow(tr) {
        const tds = tr.querySelectorAll('td');
        if (tds.length === 1 && tds[0].hasAttribute('colspan')) {
            const t = (tds[0].innerText || '').trim().toLowerCase();
            return t === '' || t.indexOf('no ') === 0 || t.indexOf('yet') !== -1;
        }
        return (tr.innerText || '').trim() === '';
    }

    function recordTotal() {
        const tables = content.querySelectorAll('table tbody');
        let n = 0;
        let hasRows = false;
        tables.forEach(function (tb) {
            tb.querySelectorAll('tr').forEach(function (tr) {
                hasRows = true;
                if (tr.classList.contains('hub-search-hide')) return;
                if (isPlaceholderRow(tr)) return;
                n++;
            });
        });
        if (!hasRows) {
            searchableBlocks().forEach(function (el) {
                if (!el.classList.contains('hub-search-hide')) n++;
            });
        }
        return n;
    }

    function paintTotal() {
        const badge = document.getElementById('hubTotalBadge');
        if (!badge) return;
        badge.textContent = 'Total: ' + recordTotal();
    }

    function applySearch() {
        const q = (search && search.value ? search.value : '').trim().toLowerCase();
        const blocks = searchableBlocks();
        let shown = 0;
        blocks.forEach(function (row) {
            const hay = (row.innerText || '').toLowerCase();
            const ok = q === '' || hay.indexOf(q) !== -1;
            row.classList.toggle('hub-search-hide', !ok);
            if (ok) shown++;
        });
        if (q === '') {
            hint.classList.add('d-none');
            hint.textContent = '';
        } else {
            hint.classList.remove('d-none');
            hint.textContent = shown + ' match' + (shown === 1 ? '' : 'es') + ' on this page';
        }
        paintTotal();
    }

    function fileStub() {
        const h = document.querySelector('.hub-topbar h5');
        const raw = (h ? h.textContent : document.title) || 'solqam-export';
        return raw.replace(/[^\w\-]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '').slice(0, 70) || 'solqam_page';
    }

    function sheetWrap(inner) {
        return '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
            + '<head><meta charset="UTF-8"></head><body>' + inner + '</body></html>';
    }

    function htmlTable(headers, rows) {
        if (!rows.length && !headers.length) return '';
        let html = '<table border="1">';
        if (headers.length) {
            html += '<tr>';
            headers.forEach(function (h) { html += '<th>' + escapeHtml(h) + '</th>'; });
            html += '</tr>';
        }
        rows.forEach(function (row) {
            html += '<tr>';
            row.forEach(function (cell) { html += '<td>' + escapeHtml(cell) + '</td>'; });
            html += '</tr>';
        });
        html += '</table>';
        return html;
    }

    function productUrl(tr) {
        if (!tr) return '';
        if (tr.dataset && tr.dataset.productUrl) return tr.dataset.productUrl.trim();
        const a = tr.querySelector('a[href*="/product/"]');
        return a && a.href ? a.href : '';
    }

    function productImage(tr) {
        if (!tr) return '';
        if (tr.dataset && tr.dataset.productImage) return tr.dataset.productImage.trim();
        const img = tr.querySelector('img');
        if (!img || !img.src) return '';
        if (/placeholder|data:image/i.test(img.src)) return '';
        return img.src;
    }

    function tablesToHtml() {
        const parts = [];
        content.querySelectorAll('table').forEach(function (table) {
            const heads = Array.from(table.querySelectorAll('thead th, thead td')).map(cellText);
            const packed = [];
            let hasUrl = false;
            let hasImage = false;
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                const cells = Array.from(tr.children)
                    .filter(function (c) { return c.tagName === 'TD' || c.tagName === 'TH'; })
                    .map(cellText);
                if (cells.join('').trim() === '') return;
                const url = productUrl(tr);
                const image = productImage(tr);
                if (url) hasUrl = true;
                if (image) hasImage = true;
                packed.push({ cells: cells, url: url, image: image });
            });
            if (!table.querySelector('tbody')) {
                Array.from(table.rows).forEach(function (tr, i) {
                    if (i === 0 && heads.length) return;
                    const cells = Array.from(tr.cells).map(cellText);
                    if (cells.join('').trim() === '') return;
                    const url = productUrl(tr);
                    const image = productImage(tr);
                    if (url) hasUrl = true;
                    if (image) hasImage = true;
                    packed.push({ cells: cells, url: url, image: image });
                });
            }
            if (heads.length && /^(actions?|moderation)$/i.test(heads[heads.length - 1])) {
                heads.pop();
                packed.forEach(function (row) {
                    if (row.cells.length > heads.length) row.cells.pop();
                });
            }
            if (hasUrl) {
                heads.push('Product URL');
            }
            if (hasImage) {
                heads.push('Image URL');
            }
            const rows = packed.map(function (row) {
                let cells = row.cells.slice();
                if (hasUrl) cells.push(row.url || '');
                if (hasImage) cells.push(row.image || '');
                return cells;
            });
            const chunk = htmlTable(heads, rows);
            if (chunk) parts.push(chunk);
        });
        return parts;
    }

    function statsToHtml() {
        const rows = [];
        content.querySelectorAll('.ops-stat, .kpi-tile').forEach(function (box) {
            const label = (box.querySelector('span') ? box.querySelector('span').innerText : '').trim();
            const value = (box.querySelector('strong') ? box.querySelector('strong').innerText : cellText(box)).trim();
            const extra = (box.querySelector('em') ? box.querySelector('em').innerText : '').trim();
            if (label || value) rows.push([label, value, extra]);
        });
        return rows.length ? htmlTable(['Metric', 'Value', 'Note'], rows) : '';
    }

    function listsToHtml() {
        const rows = [];
        content.querySelectorAll('.list-group-item').forEach(function (item) {
            const t = cellText(item);
            if (t) rows.push([t]);
        });
        content.querySelectorAll('dl').forEach(function (dl) {
            const dts = dl.querySelectorAll('dt');
            const dds = dl.querySelectorAll('dd');
            dts.forEach(function (dt, i) {
                rows.push([cellText(dt), cellText(dds[i])]);
            });
        });
        return rows.length ? htmlTable(['Item', 'Detail'], rows) : '';
    }

    function cardsToHtml() {
        const rows = [];
        content.querySelectorAll('.card-custom, .console-card, .command-panel, .sf-panel, .card').forEach(function (card) {
            if (card.querySelector('table')) return;
            const titleEl = card.querySelector('h4, h5, h6, .fw-bold');
            const title = titleEl ? cellText(titleEl) : '';
            const body = cellText(card);
            if (body.length < 3) return;
            rows.push([title, body.slice(0, 2000)]);
        });
        return rows.length ? htmlTable(['Section', 'Details'], rows) : '';
    }

    function buildSheet() {
        const parts = tablesToHtml();
        const stats = statsToHtml();
        if (stats) parts.unshift(stats);
        const lists = listsToHtml();
        if (lists) parts.push(lists);
        if (!parts.length) {
            const cards = cardsToHtml();
            if (cards) parts.push(cards);
        }
        if (!parts.length) {
            const blob = cellText(content);
            if (blob) parts.push(htmlTable(['Page content'], [[blob.slice(0, 8000)]]));
        }
        if (!parts.length) return '';
        return sheetWrap(parts.join('<br/><br/>'));
    }

    function downloadExcel() {
        const html = buildSheet();
        if (!html) {
            alert('No data found on this page to export.');
            return;
        }
        const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = fileStub() + '.xls';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () {
            URL.revokeObjectURL(a.href);
            a.remove();
        }, 800);
    }

    if (search) {
        search.addEventListener('input', applySearch);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                search.value = '';
                applySearch();
            }
        });
    }
    if (exportBtn) {
        exportBtn.addEventListener('click', downloadExcel);
    }
    paintTotal();
})();
