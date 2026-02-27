<?php

return [
    'validation' => [
        'fullName' => [
            'required' => 'El nombre completo es obligatorio.',
            'regex' => 'El nombre no puede contener números',
        ],
        'email' => [
            'required' => 'El correo electrónico es obligatorio.',
            'email' => 'El campo correo electrónico debe ser una dirección de correo electrónico válida.',
            'unique' => 'Este email no puede ser usado, ya está en el sistema',
        ],
        'documentType' => [
            'required' => 'El tipo de documento es obligatorio.',
            'enum' => 'El tipo de documento ingresado no es válido.',
        ],
        'documentNumber' => [
            'required' => 'El número de documento es obligatorio.',
            'digits_between' => 'El número de documento debe tener entre 8 y 10 dígitos',
            'unique' => 'Este número de documento no puede ser usado, ya está en el sistema',
        ],
        'phoneNumber' => [
            'digits' => 'El teléfono debe tener 10 dígitos',
        ],
        'addresses' => [
            '*' => [
                'type' => [
                    'required' => 'El tipo de dirección es obligatorio.',
                ],
                'street' => [
                    'required' => 'La dirección (calle) es obligatoria.',
                ],
                'city' => [
                    'required' => 'La ciudad es obligatoria.',
                ],
                'state' => [
                    'required' => 'El departamento/estado es obligatorio.',
                ],
                'country' => [
                    'required' => 'El país es obligatorio.',
                ],
            ],
        ],
    ],
];
