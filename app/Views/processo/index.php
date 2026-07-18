<?php use App\Core\View; ?>
<div class="page-title">Meus Processos</div>
<div class="page-sub">Todos os processos que criou</div>

<div class="card-soft card">
    <div class="card-body p-0"><?= View::partial('tabela_processos', ['lista' => $lista]) ?></div>
</div>
