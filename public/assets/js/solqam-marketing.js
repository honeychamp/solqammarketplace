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

  const fire = (row) => {
    if (!allowed() || !row) return;
    const p = row.params || {};
    const eventId = row.event_id;
    const meta = {
      content_ids: p.content_ids || [],
      content_type: p.content_type || 'product',
      content_name: p.content_name || undefined,
      content_category: p.content_category || undefined,
      contents: p.contents || undefined,
      currency: p.currency || cfg.currency || 'PKR',
      value: p.value,
      num_items: p.num_items,
      search_string: p.search_string,
      event_id: eventId
    };
    Object.keys(meta).forEach((k) => { if (meta[k] === undefined || meta[k] === null || meta[k] === '') delete meta[k]; });

    if (typeof fbq === 'function' && row.event !== 'PageView') {
      try { fbq('track', row.event, meta, { eventID: eventId }); } catch (e) {}
    }
    if (typeof ttq === 'object' && ttq && typeof ttq.track === 'function' && row.event !== 'PageView') {
      const ttName = row.event === 'Purchase' ? 'CompletePayment' : row.event;
      try { ttq.track(ttName, Object.assign({}, meta, { event_id: eventId })); } catch (e) {}
    }

    const gaEvent = row.ga4_event || 'custom';
    const ga = {
      event: gaEvent,
      event_id: eventId,
      currency: p.currency || cfg.currency || 'PKR',
      value: p.value,
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
          value: p.value,
          currency: p.currency || 'PKR',
          transaction_id: p.transaction_id
        });
      } catch (e) {}
    }
  };

  (cfg.queue || []).forEach(fire);

  dl({ event: 'page_view' });
})();
