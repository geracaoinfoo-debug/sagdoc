<?php use App\Middleware\CsrfMiddleware; ?>
<div class="page-title">Gestão de Utilizadores</div>
<div class="page-sub">Criar, editar e desativar utilizadores e perfis (RBAC — RF45)</div>

<div class="card-soft card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    Utilizadores do sistema
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario"><i class="bi bi-person-plus"></i> Novo</button>
  </div>
  <div class="card-body p-0">
    <table class="table tbl mb-0">
      <thead><tr><th>Nome</th><th>Utilizador</th><th>Email</th><th>Perfil</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $u): ?>
        <tr>
          <td><?= e($u['nome']) ?></td>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge bg-secondary"><?= e(perfil_label($u['perfil'])) ?></span></td>
          <td><span class="badge bg-<?= $u['ativo'] ? 'success' : 'secondary' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditar<?= (int) $u['id'] ?>"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>

        <div class="modal fade" id="modalEditar<?= (int) $u['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="/admin/utilizadores/<?= (int) $u['id'] ?>" method="post">
                <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
                <div class="modal-header bg-azul text-white">
                  <h5 class="modal-title">Editar <?= e($u['nome']) ?></h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-2"><label class="form-label">Nome</label><input class="form-control" name="nome" value="<?= e($u['nome']) ?>" required></div>
                  <div class="mb-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($u['email']) ?>" required></div>
                  <div class="mb-2"><label class="form-label">Telefone</label><input class="form-control" name="telefone" value="<?= e($u['telefone'] ?? '') ?>"></div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo<?= (int) $u['id'] ?>" <?= $u['ativo'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo<?= (int) $u['id'] ?>">Conta ativa</label>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalNovoUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/admin/utilizadores" method="post">
        <input type="hidden" name="_csrf" value="<?= e(CsrfMiddleware::token()) ?>">
        <div class="modal-header bg-azul text-white">
          <h5 class="modal-title">Novo Utilizador</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Utilizador</label><input class="form-control" name="username" required></div>
            <div class="col-6"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" minlength="6" required></div>
            <div class="col-12"><label class="form-label">Nome completo</label><input class="form-control" name="nome" required></div>
            <div class="col-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
            <div class="col-6"><label class="form-label">Telefone</label><input class="form-control" name="telefone"></div>
            <div class="col-12">
              <label class="form-label">Perfil</label>
              <select class="form-select" name="perfil" id="novoPerfil" required>
                <option value="despachante">Despachante</option>
                <option value="verificador">Verificador</option>
                <option value="chefe_setor">Chefe de Setor</option>
                <option value="gestor">Gestor DGA</option>
                <option value="administrador">Administrador</option>
                <option value="consultor">Consultor</option>
              </select>
            </div>
            <div class="col-6"><label class="form-label">NIF (despachante)</label><input class="form-control" name="nif"></div>
            <div class="col-6"><label class="form-label">Licença (despachante)</label><input class="form-control" name="numero_licenca"></div>
            <div class="col-6"><label class="form-label">Matrícula (verificador)</label><input class="form-control" name="matricula"></div>
            <div class="col-6"><label class="form-label">Setor (verificador)</label><input class="form-control" name="setor" value="Importação"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Criar utilizador</button>
        </div>
      </form>
    </div>
  </div>
</div>
