<?php

declare(strict_types=1);

$status = $status ?? 'Requested';
?>
<span data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
