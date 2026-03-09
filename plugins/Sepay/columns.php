<?php

return [
    [
        'name'            => 'merchant_id',
        'label_key'       => 'common.merchant_id',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required|string|max:255',
        'description_key' => 'common.merchant_id_desc',
    ],
    [
        'name'            => 'secret_key',
        'label_key'       => 'common.secret_key',
        'type'            => 'string',
        'required'        => true,
        'rules'           => 'required|string|max:255',
        'description_key' => 'common.secret_key_desc',
    ],
    [
        'name'            => 'environment',
        'label_key'       => 'common.environment',
        'type'            => 'select',
        'options'         => [
            ['value' => 'sandbox', 'label_key' => 'common.environment_sandbox'],
            ['value' => 'production', 'label_key' => 'common.environment_production'],
        ],
        'required'        => true,
        'rules'           => 'required|in:sandbox,production',
        'description_key' => 'common.environment_desc',
    ],
];
