<?php
// Proxy: control/producto.php es idéntico a nuevo/producto.php
// Requiere la misma lógica de búsqueda, delegamos al archivo canónico.
require_once dirname(__FILE__) . '/../nuevo/producto.php';
