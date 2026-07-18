<?php
/** @var string $icon */
/** @var string $cor */
/** @var string $numero */
/** @var string $label */
?>
<div class="stat-card">
    <div class="ic" style="background:<?= e($cor) ?>"><i class="bi <?= e($icon) ?>"></i></div>
    <div>
        <div class="n"><?= e((string) $numero) ?></div>
        <div class="l"><?= e($label) ?></div>
    </div>
</div>
