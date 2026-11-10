<?php

return [
    'rpc_url'          => env('BLOCKCHAIN_RPC_URL', 'https://rpc.sepolia.org'),
    'private_key'      => env('BLOCKCHAIN_PRIVATE_KEY'),
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
    'cache_ttl'        => (int) env('BLOCKCHAIN_CACHE_TTL', 3600),
    'abi_path'         => storage_path('app/blockchain/CertificacionAcademica.json'),
];