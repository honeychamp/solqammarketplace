function solqamBindGeo(citiesByDivision) {
    function fillCity(div, city) {
        var list = (citiesByDivision && citiesByDivision[div.value]) ? citiesByDivision[div.value] : [];
        var keep = city.getAttribute('data-keep') || city.value;
        city.innerHTML = '<option value="">Select city</option>';
        list.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.code;
            opt.textContent = c.name + ' (' + c.code + ')';
            if (keep && keep === c.code) opt.selected = true;
            city.appendChild(opt);
        });
    }

    function previewFor(prefix) {
        var box = document.getElementById(prefix + '_card_preview');
        if (!box) return;
        var div = document.getElementById(prefix + '_division');
        var city = document.getElementById(prefix + '_city');
        var type = (box.getAttribute('data-type') || 'C').toUpperCase();
        var catSel = document.getElementById(box.getAttribute('data-category') || '');
        var d = div && div.value ? div.value : '••';
        var c = city && city.value ? city.value : '••';
        if (type === 'M') {
            var cat = catSel && catSel.value ? catSel.value : '•••';
            box.textContent = d + '-' + c + '-M-•••••••-' + cat;
        } else {
            box.textContent = d + '-' + c + '-' + type + '-••••••••';
        }
    }

    document.querySelectorAll('.js-division').forEach(function (div) {
        var cityId = div.getAttribute('data-city');
        var city = cityId ? document.getElementById(cityId) : null;
        if (!city) return;
        var prefix = (div.id || '').replace(/_division$/, '');
        var apply = function () {
            fillCity(div, city);
            previewFor(prefix);
        };
        div.addEventListener('change', apply);
        city.addEventListener('change', function () { previewFor(prefix); });
        apply();
    });

    document.querySelectorAll('[data-card-preview]').forEach(function (box) {
        var catId = box.getAttribute('data-category');
        var cat = catId ? document.getElementById(catId) : null;
        if (cat) {
            cat.addEventListener('change', function () {
                previewFor(box.id.replace(/_card_preview$/, ''));
            });
        }
    });
}

function solqamFormatCardInput(el) {
    if (!el) return;
    el.addEventListener('input', function () {
        var raw = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (/^\d{4}M\d{0,10}$/.test(raw)) {
            var out = raw.slice(0, 2);
            if (raw.length > 2) out += '-' + raw.slice(2, 4);
            if (raw.length > 4) out += '-M';
            if (raw.length > 5) out += '-' + raw.slice(5, 12);
            if (raw.length > 12) out += '-' + raw.slice(12, 15);
            el.value = out;
            return;
        }
        if (/^\d{4}[CS]\d{0,8}$/.test(raw)) {
            var t = raw.charAt(4);
            var out2 = raw.slice(0, 2);
            if (raw.length > 2) out2 += '-' + raw.slice(2, 4);
            if (raw.length > 4) out2 += '-' + t;
            if (raw.length > 5) out2 += '-' + raw.slice(5, 13);
            el.value = out2;
        }
    });
}
