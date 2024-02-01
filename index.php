<?php

require_once 'BlockchainPlatform.php'; // Замените на актуальное имя вашего файла

$blockchain = new BlockchainPlatform();

$publicKeyToSearch = "be94e18f61027c984b5e2ac92b3067bc139df9f5ff5360e53d2214fa02a24cb5";
$contractsFound = $blockchain->getContractsByPublicKey($publicKeyToSearch);

if (!empty($contractsFound)) {
    foreach ($contractsFound as $contractName => $contractInfo) {
        echo "Contract Name: $contractName\n";
        echo "Contract Info:\n";
        print_r($contractInfo);
        echo "---------------------\n";
    }
} else {
    echo "No contracts found for the given public key.\n";
}
