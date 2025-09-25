(function(){
  function q(sel, ctx){ return (ctx||document).querySelector(sel); }
  function qa(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }

  function renderLicenses(container, data){
    if(!data || !data.licenses || !data.licenses.length){
      container.innerHTML = '<div class="redeasedd-alert redeasedd-alert--success" role="alert">✅ Redemption successful. License created.</div>';
      return;
    }
    var rows = data.licenses.map(function(l){
      var exp = l.expires && l.expires !== 'lifetime' ? l.expires : 'Lifetime';
      return '<tr>' +
        '<td><code>'+ (l.license_key || '') +'</code></td>' +
        '<td>'+ (l.price_id || '') +'</td>' +
        '<td>'+ (l.status || '') +'</td>' +
        '<td>'+ exp +'</td>' +
      '</tr>';
    }).join('');
    container.innerHTML =
      '<div class="redeasedd-alert redeasedd-alert--success" role="alert">✅ Redemption successful.</div>' +
      '<div style="overflow:auto;margin-top:10px;">' +
        '<table class="redeasedd-table" aria-label="Your License Details">' +
          '<thead><tr><th>License Key</th><th>Tier</th><th>Status</th><th>Expires</th></tr></thead>' +
          '<tbody>'+ rows +'</tbody>' +
        '</table>' +
      '</div>';
  }

  function showError(container, msg){
    container.innerHTML = '<div class="redeasedd-alert redeasedd-alert--error" role="alert">❌ ' + (msg || 'An error occurred') + '</div>';
  }

  function bindForm(form){
    var submit = q('button[type="submit"]', form);
    var out = q('.redeasedd-output', form);

    form.addEventListener('submit', function(e){
      e.preventDefault();
      // reset inline errors
      qa('.redeasedd-error', form).forEach(function(n){ n.textContent=''; });
      out.innerHTML = '';

      // basic client validation
      var email = q('input[name="email"]', form).value.trim();
      var code  = q('input[name="code"]', form).value.trim();
      var pid   = q('select[name="price_id"]', form).value;
      if(!email) { q('[data-err="email"]', form).textContent = 'Email is required.'; return; }
      if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { q('[data-err="email"]', form).textContent = 'Enter a valid email.'; return; }
      if(!code) { q('[data-err="code"]', form).textContent = 'Code is required.'; return; }
      if(!pid) { q('[data-err="price_id"]', form).textContent = 'Select a tier.'; return; }

      // submit
      submit.disabled = true;
      var orig = submit.innerHTML;
      submit.innerHTML = '<span class="redeasedd-spinner" aria-hidden="true"></span> &nbsp;Redeeming…';

      var fd = new URLSearchParams();
      fd.append('action', REDEASEDD_AJAX.action);           // redeasedd_redeem
      fd.append('_wpnonce', REDEASEDD_AJAX.nonce);          // redeasedd_redeem nonce
      fd.append('email', email);
      fd.append('name', q('input[name="name"]', form).value.trim());
      fd.append('code', code);
      fd.append('price_id', pid);

      fetch(REDEASEDD_AJAX.url, {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      }).then(function(r){ return r.json(); }).then(function(json){
        if(json && json.ok){ renderLicenses(out, json); form.reset(); }
        else { showError(out, (json && json.error) ? json.error : 'Unknown error'); }
      }).catch(function(){
        showError(out, 'Network error. Please try again.');
      }).finally(function(){
        submit.disabled = false;
        submit.innerHTML = orig;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    qa('form.redeasedd-redeem-form').forEach(bindForm);
  });
})();
