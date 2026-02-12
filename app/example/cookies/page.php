<?php
session_start();

use Core\Http\CSRF;
use Core\Cookies\Cookie;

$request = Cookie::request();
$cookies = $request->getAll();

// Generar parámetro aleatorio para forzar la recarga del CSS
$cssCacheBuster = random_int(1000, 9999);
?>
<?php if (isset($GLOBALS['css']) && $GLOBALS['css']): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($GLOBALS['css']) . '?last=' . $cssCacheBuster; ?>">
<?php endif; ?>

<script src="../../SerJS/SerJS.js"></script>

<div class="!text-black">
    <h1>Cookies del Request</h1>
    <?php if (empty($cookies)): ?>
        <p class="empty">No hay cookies disponibles.</p>
    <?php else: ?>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cookies as $name => $value): ?>
                    <tr>
                        <td class="!w-40"><?= htmlspecialchars($name) ?></td>
                        <td><?= htmlspecialchars($value) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="empty">Total de cookies: <?= count($cookies) ?></p>
        <p class="empty">Session ID: <?= session_id() ?></p>

        <div class="actions-grid">

            <!-- Acción 4: Toggle con AJAX -->
            <div class="action-card">
                <h3>Creando una cookie con actions</h3>
                <button class="btn-warning" id="toggleBtn" class="bg-red-400 text-white">
                    Create cookie
                </button>

                <p id="togglemessage" class="p-2 mt-4"></p>

            </div>
        </div>

    <?php endif; ?>

    <script type="module">
        const {
            useRef,
            useState,
            useEffect,
            setText,
            setHTML,
            Actions
        } = SerJS;

        const actionBoton = useRef('toggleBtn');
        const toggleMessage = useRef('togglemessage');
        actionBoton.onClick(toggleFeature);

        const cookieActionInstance = await Actions(`<?= CSRF::token(); ?>`);

        async function toggleFeature() {
            const response = await cookieActionInstance.call('create-cookie', {
                enabled: true
            });
            if (response.success) {
                setText(toggleMessage, response.success.data['example-cookie']?.value);
                console.log("lo ke viene s esto..", response)
            }
        }
    </script>

</div>