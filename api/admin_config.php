<?php
// Contraseña de acceso al panel administrativo.
// Para cambiarla: genera un nuevo hash con
//   php -r "echo password_hash('TU_NUEVA_CLAVE', PASSWORD_DEFAULT), PHP_EOL;"
// y reemplaza el valor de ADMIN_PASSWORD_HASH.
define('ADMIN_PASSWORD_HASH', '$2y$12$.6SpTciZh1NRPd1qtSS0zOz1Sv3..TBpW.vRbBe7t9.qxlEiq60Ne');
