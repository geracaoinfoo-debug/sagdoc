<?php use App\Middleware\CsrfMiddleware; ?>
<div id="view-login">
  <div class="login-card">
    <div class="login-top">
      <div class="brand-badge"><i class="bi bi-key-fill"></i></div>
      <h1>SAGDOC</h1>
      <small>Definir nova senha</small>
    </div>
    <div class="login-body">
      <?= \App\Core\View::partial('flash') ?>
      <form method="post" action="/redefinir-senha">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-floating mb-3">
          <input type="password" class="form-control" name="senha" placeholder="Nova senha" minlength="6" required autofocus>
          <label>Nova senha</label>
        </div>
        <div class="form-floating mb-3">
          <input type="password" class="form-control" name="confirmacao" placeholder="Confirmar senha" minlength="6" required>
          <label>Confirmar senha</label>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
          <i class="bi bi-check2-circle me-1"></i> Redefinir senha
        </button>
      </form>
    </div>
    <div class="login-foot">© 2026 DGA — SAGDOC</div>
  </div>
</div>
