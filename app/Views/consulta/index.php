<?php use App\Core\View; ?>
<div class="page-title">Consultar Processos</div>
<div class="page-sub">Pesquise e filtre processos por DU, importador, data, estado</div>

<div class="card-soft card mb-3">
  <div class="card-body">
    <form method="get" action="/consulta" class="row g-2">
      <div class="col-md-3"><input class="form-control form-control-sm" name="numero_du" placeholder="Nº DU" value="<?= e($filtros['numero_du'] ?? '') ?>"></div>
      <div class="col-md-3"><input class="form-control form-control-sm" name="importador" placeholder="Importador" value="<?= e($filtros['importador'] ?? '') ?>"></div>
      <div class="col-md-3">
        <select class="form-select form-select-sm" name="status">
          <option value="">Todos os estados</option>
          <?php foreach ($statusDisponiveis as $s): ?>
            <option value="<?= e($s) ?>" <?= ($filtros['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select form-select-sm" name="categoria">
          <option value="">Todas as categorias</option>
          <?php foreach ($categorias as $c): ?>
            <option <?= ($filtros['categoria'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label small mb-0">Submissão de</label><input type="date" class="form-control form-control-sm" name="data_de" value="<?= e($filtros['data_de'] ?? '') ?>"></div>
      <div class="col-md-3"><label class="form-label small mb-0">Submissão até</label><input type="date" class="form-control form-control-sm" name="data_ate" value="<?= e($filtros['data_ate'] ?? '') ?>"></div>
      <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Pesquisar</button></div>
      <div class="col-md-3 d-flex align-items-end"><a href="/consulta" class="btn btn-sm btn-outline-secondary w-100">Limpar</a></div>
    </form>
  </div>
</div>

<div class="card-soft card">
  <div class="card-body p-0"><?= View::partial('tabela_processos', ['lista' => $lista]) ?></div>
</div>
