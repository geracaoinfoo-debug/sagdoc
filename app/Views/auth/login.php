<?php use App\Middleware\CsrfMiddleware; ?>
<div id="view-login">
  <div class="login-card">
    <div class="login-top">
      <div class="brand-badge"><i class="bi bi-shield-lock-fill"></i></div>
      <h1>SAGDOC</h1>
      <small>Sistema de Apoio à Gestão Documental Aduaneira</small><br>
      <small>Direcção-Geral das Alfândegas — Guiné-Bissau</small>
    </div>
    <div class="login-body">
      <h5>Autenticação de Utilizador</h5>

      <?= \App\Core\View::partial('flash') ?>

      <form id="loginForm" method="post" action="/login" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <div class="form-floating mb-3">
          <input type="text" class="form-control" id="loginUser" name="username" placeholder="Utilizador" required autofocus>
          <label><i class="bi bi-person me-1"></i> Utilizador</label>
        </div>
        <div class="form-floating mb-3">
          <input type="password" class="form-control" id="loginPass" name="password" placeholder="Senha" required>
          <label><i class="bi bi-key me-1"></i> Senha</label>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember" style="font-size:13px">Manter sessão</label>
          </div>
          <a href="/recuperar-senha" style="font-size:13px">Recuperar senha</a>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
          <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
        </button>
      </form>
      <div class="demo-users">
        <b>Perfis de demonstração</b> (senha: <code>demo</code>) — clique para preencher:<br>
        <code data-u="jbarbosa">jbarbosa</code> · Despachante &nbsp;|&nbsp;
        <code data-u="averificador">averificador</code> · Verificador<br>
        <code data-u="chefe">chefe</code> · Chefe de Setor &nbsp;|&nbsp;
        <code data-u="gestor">gestor</code> · Gestor DGA &nbsp;|&nbsp;
        <code data-u="admin">admin</code> · Administrador
      </div>
    </div>
    <div class="login-foot">
      © 2026 DGA — Complemento ao SYDONIA (ASYCUDA) · Acessos registados e auditados (RF05)
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.demo-users code').forEach(function (c) {
  c.addEventListener('click', function () {
    document.getElementById('loginUser').value = c.dataset.u;
    document.getElementById('loginPass').value = 'demo';
  });
});
</script>
