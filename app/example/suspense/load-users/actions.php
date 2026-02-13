<?php

use Core\Http\ServerAction;
use Core\Utils\Console;

// Registrar la acción Suspense
ServerAction::define('loadUsers', function ($data, $params) {
    // Simulamos una consulta lenta

    sleep(5);

    $userId = $data['userId'];

    $users = [
        ['id' => 1, 'name' => 'Juan Pérez', 'email' => 'juan@example.com'],
        ['id' => 2, 'name' => 'María López', 'email' => 'maria@example.com'],
        ['id' => 3, 'name' => 'Carlos García', 'email' => 'carlos@example.com'],
    ];
    ob_start(function ($buffer) {
        $limit = 256 * 1024; // 256kb
        if (strlen($buffer) > $limit) {
            return "Error: respuesta demasiado grande";
        }
        return $buffer;
    });
?>
    <div>
        <span class="text-sm block text-gray-600">
            El usuario que llama es : <?= htmlspecialchars($userId) ?>
        </span>
        <ul class="space-y-2">
            <?php foreach ($users as $user): ?>
                <li class="p-3 bg-blue-50 rounded border-l-4 border-blue-500">
                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                    <!-- meter el user id aqui -->

                    <br>
                    <span class="text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php
    return ob_get_clean();
});
?>
