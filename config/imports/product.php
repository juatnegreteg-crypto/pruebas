<?php

return [
    /*
     * El primer alias de cada columna se usa como header requerido
     * al validar el archivo subido.
     */
    'columns' => [
        'sku' => ['sku'],
        'name' => ['nombre', 'name'],
        'description' => ['descripcion', 'descripción', 'description'],
        'cost' => ['costo', 'cost'],
        'price' => ['precio', 'price'],
        'currency' => ['moneda', 'currency'],
        'unit' => ['unidad', 'unit'],
        'is_active' => ['estado', 'is_active'],
        'stock' => ['stock'],
    ],
];
