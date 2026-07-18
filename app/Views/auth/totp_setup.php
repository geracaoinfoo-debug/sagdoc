<?php use App\Middleware\CsrfMiddleware; ?>
<div class="page-title">Verificação em duas etapas</div>
<div class="page-sub">RF02 — 2FA opcional para perfis administrativos</div>

<div class="card-soft card" style="max-width:520px">
  <div class="card-body">
    <p>Adicione esta conta a uma aplicação autenticadora (Google Authenticator, Authy, etc.) inserindo manualmente o código secreto abaixo, depois confirme com o código gerado.</p>
    <div class="alert alert-info">
      <strong>Código secreto:</strong> <code style="font-size:15px;letter-spacing:1px"><?= e($segredo) ?></code>
    </div>
    <p class="small text-muted">URI: <code><?= e($otpauth) ?></code></p>
    <form method="post" action="/perfil/2fa/confirmar">
      <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
      <div class="form-floating mb-3">
        <input type="text" class="form-control" name="codigo" placeholder="000000" maxlength="6" required>
        <label>Código de verificação</label>
      </div>
      <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i> Ativar 2FA</button>
    </form>
  </div>
</div>
