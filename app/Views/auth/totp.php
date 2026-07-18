<?php use App\Middleware\CsrfMiddleware; ?>
<div id="view-login">
  <div class="login-card">
    <div class="login-top">
      <div class="brand-badge"><i class="bi bi-shield-check"></i></div>
      <h1>SAGDOC</h1>
      <small>Verificação em duas etapas (2FA)</small>
    </div>
    <div class="login-body">
      <h5>Introduza o código da sua aplicação autenticadora</h5>
      <?= \App\Core\View::partial('flash') ?>
      <form method="post" action="/login/totp">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <div class="form-floating mb-3">
          <input type="text" class="form-control" name="codigo" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
          <label>Código de 6 dígitos</label>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
          <i class="bi bi-check2-circle me-1"></i> Verificar
        </button>
      </form>
    </div>
    <div class="login-foot">© 2026 DGA — SAGDOC</div>
  </div>
</div>
