<?php

// Qué puede hacer cada rol. Se usa en los controladores y para mostrar u ocultar botones.
return [
    'ADMINISTRADOR'      => ['pedido', 'compra', 'entregar', 'devolver', 'merma', 'estado', 'reasignar', 'cancelar', 'ajustar', 'anular', 'material', 'directorio'],
    'CONTROL_MATERIALES' => ['compra', 'entregar', 'devolver', 'merma', 'estado', 'reasignar', 'material', 'directorio'],
    'ALMACEN'            => ['compra', 'entregar', 'devolver', 'merma'],
    'VENTAS'             => ['pedido'],
    'CONSULTA'           => [],
];
