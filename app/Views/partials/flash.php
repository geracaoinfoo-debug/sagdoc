<?php

use App\Core\Session;

$mapa = ['sucesso' => 'success', 'erro' => 'danger', 'aviso' => 'warning', 'info' => 'info'];
foreach ($mapa as $chave => $classe) {
    $msg = Session::flash($chave);
    if ($msg) {
        echo '<div class="alert alert-' . $classe . ' alert-dismissible alert-flash d-flex align-items-center gap-2 mb-3">'
            . '<i class="bi bi-info-circle"></i><div>' . e($msg) . '</div>'
            . '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>'
            . '</div>';
    }
}
