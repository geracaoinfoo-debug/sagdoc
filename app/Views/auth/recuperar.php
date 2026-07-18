<?php use App\Middleware\CsrfMiddleware; ?>
<div id="view-login">
  <div class="login-card">
    <div class="login-top">
      <div class="brand-badge"><i class="bi bi-key-fill"></i></div>
      <h1>SAGDOC</h1>
      <small>Recuperação de senha</small>
    </div>
    <div class="login-body">
      <h5>Indique o seu email de acesso</h5>
      <?= \App\Core\View::partial('flash') ?>
      <form method="post" action="/recuperar-senha">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <div class="form-floating mb-3">
          <input type="email" class="form-control" name="email" placeholder="email@dga.gw" required autofocus>
          <label><i class="bi bi-envelope me-1"></i> Email</label>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
          <i class="bi bi-send me-1"></i> Enviar link de redefinição
        </button>
        <a href="/login" class="d-block text-center mt-3" style="font-size:13px">← Voltar ao login</a>
      </form>
    </div>
    <div class="login-foot">© 2026 DGA — SAGDOC</div>
  </div>
</div>
