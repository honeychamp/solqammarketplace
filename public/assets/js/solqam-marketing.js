(() => {
  const cfg = window.SOLQAM_MKT || {};
  const allowed = () => {
    if (!cfg.consentRequired) return true;
    return document.cookie.indexOf('solqam_ads_consent=0') === -1;
  };

  const setConsent = (on) => {
    document.cookie = 'solqam_ads_consent=' + (on ? '1' : '0') + ';path=/;max-age=31536000;SameSite=Lax';
    const bar = document.getElementById('solqam-ads-consent');
    if (bar) bar.classList.add('d-none');
  };

  const bar = document.getElementById('solqam-ads-consent');
  if (bar && cfg.consentRequired && document.cookie.indexOf('solqam_ads_consent=') === -1) {
    bar.classList.remove('d-none');
    bar.querySelectorAll('[data-ads]').forEach((btn) => {
      btn.addEventListener('click', () => setConsent(btn.getAttribute('data-ads') === '1'));
    });
  }

  const dl = (obj) => {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(obj);
  };

  const money = (v) => {
    const n = Number(v);
    return Number.isFinite(n) && n > 0 ? Math.round(n * 100) / 100 : undefined;
  };

  try {
    if (cfg.pixelId && typeof fbq === 'function') {
      fbq('set', 'autoConfig', false, cfg.pixelId);
    }
  } catch (e) {}

  const fire = (row) => {
    if (!allowed() || !row) return;
    const p = row.params || {};
    const eventId = row.event_id;
    const value = money(p.value);
    if (row.event === 'Purchase' && value === undefined) return;
    let currency = String(p.currency || cfg.currency || 'PKR').toUpperCase().replace(/[^A-Z]/g, '');
    if (currency.length !== 3) currency = 'PKR';
    const meta = {
      content_ids: p.content_ids || [],
      content_type: p.content_type || 'product',
      content_name: p.content_name || undefined,
      content_category: p.content_category || undefined,
      contents: p.contents || undefined,
      currency: currency,
      num_items: p.num_items,
      search_string: p.search_string,
      event_id: eventId
    };
    if (value !== undefined) meta.value = value;
    Object.keys(meta).forEach((k) => {
      if (k === 'currency') return;
      if (meta[k] === undefined || meta[k] === null || meta[k] === '') delete meta[k];
    });

    if (typeof fbq === 'function' && row.event !== 'PageView') {
      try {
        if (cfg.pixelId && cfg.advancedMatching && typeof cfg.advancedMatching === 'object' && Object.keys(cfg.advancedMatching).length) {
          fbq('init', cfg.pixelId, cfg.advancedMatching);
        }
        fbq('track', row.event, meta, { eventID: eventId });
      } catch (e) {}
    }
    if (typeof ttq === 'object' && ttq && typeof ttq.track === 'function' && row.event !== 'PageView') {
      const ttName = row.event === 'Purchase' ? 'CompletePayment' : row.event;
      try { ttq.track(ttName, Object.assign({}, meta, { event_id: eventId })); } catch (e) {}
    }

    const gaEvent = row.ga4_event || 'custom';
    const ga = {
      event: gaEvent,
      event_id: eventId,
      currency: currency,
      value: value,
      items: p.items || [],
      transaction_id: p.transaction_id,
      tax: p.tax,
      shipping: p.shipping,
      coupon: p.coupon,
      payment_type: p.payment_type,
      search_term: p.search_string
    };
    Object.keys(ga).forEach((k) => { if (ga[k] === undefined || ga[k] === null || ga[k] === '') delete ga[k]; });
    dl(ga);
    if (typeof gtag === 'function' && gaEvent) {
      try { gtag('event', gaEvent, ga); } catch (e) {}
    }
    if (row.event === 'Purchase' && cfg.adsId && cfg.adsLabel && typeof gtag === 'function') {
      try {
        gtag('event', 'conversion', {
          send_to: cfg.adsId + '/' + cfg.adsLabel,
          value: value,
          currency: currency,
          transaction_id: p.transaction_id
        });
      } catch (e) {}
    }
  };

  (cfg.queue || []).forEach(fire);

  dl({ event: 'page_view' });

  const readCookie = (name) => {
    const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
    if (!m) return '';
    try {
      return decodeURIComponent(m[1]);
    } catch (e) {
      return m[1];
    }
  };
  const attachClickIds = (form) => {
    if (!form || form.dataset.mktIds === '1') return;
    const add = (name, val) => {
      if (!val) return;
      let el = form.querySelector('input[name="' + name + '"]');
      if (!el) {
        el = document.createElement('input');
        el.type = 'hidden';
        el.name = name;
        form.appendChild(el);
      }
      el.value = val;
    };
    const rawCookie = (name) => {
      const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
      return m ? m[1] : '';
    };
    add('fbp', rawCookie('_fbp'));
    add('fbc', rawCookie('_fbc'));
    form.dataset.mktIds = '1';
  };
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => attachClickIds(form));
    attachClickIds(form);
  });

  const pkPhone = (raw) => {
    let d = String(raw || '').replace(/\D+/g, '');
    if (d.indexOf('0092') === 0) d = d.slice(2);
    if (d.length === 11 && d.charAt(0) === '0') d = '92' + d.slice(1);
    else if (d.length === 10 && d.charAt(0) === '3') d = '92' + d;
    return d;
  };

  const postLead = (payload) => {
    if (!cfg.leadUrl) return;
    const body = new URLSearchParams();
    Object.keys(payload).forEach((k) => {
      if (payload[k]) body.set(k, payload[k]);
    });
    if (!body.toString()) return;
    if (cfg.csrfName && cfg.csrfHash) body.set(cfg.csrfName, cfg.csrfHash);
    const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
    if (cfg.csrfHeader && cfg.csrfHash) headers[cfg.csrfHeader] = cfg.csrfHash;
    try {
      fetch(cfg.leadUrl, { method: 'POST', credentials: 'same-origin', headers, body, keepalive: true });
    } catch (e) {}
  };

  const bindLeadField = (el, key, valid) => {
    if (!el || el.dataset.mktLead === '1') return;
    el.dataset.mktLead = '1';
    const send = () => {
      const val = (el.value || '').trim();
      if (!valid(val)) return;
      const payload = {};
      payload[key] = val;
      postLead(payload);
    };
    el.addEventListener('blur', send);
    el.addEventListener('change', send);
  };

  bindLeadField(document.getElementById('ck_recipient_name'), 'recipient_name', (v) => v.length >= 2);
  bindLeadField(document.getElementById('ck_phone'), 'phone', (v) => {
    const d = pkPhone(v);
    return d.length >= 12 && d.length <= 13;
  });
  bindLeadField(document.getElementById('checkoutCity'), 'city', (v) => v.length >= 2);
  bindLeadField(document.getElementById('checkoutProvince'), 'province', (v) => v.length >= 2);
  bindLeadField(document.getElementById('ck_postal'), 'postal_code', (v) => /^[0-9]{4,6}$/.test(v));
  const guestEmail = document.querySelector('input[name="guest_email"]');
  bindLeadField(guestEmail, 'email', (v) => v.indexOf('@') > 0);
})();
