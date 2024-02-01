<?php

function saveContractToFile($contractName, $contractData) {
    $contractsFile = 'contracts.json';

    if (file_exists($contractsFile)) {
        $contracts = json_decode(file_get_contents($contractsFile), true);
    } else {
        $contracts = [];
    }

    $contracts[$contractName] = $contractData;

    $jsonContent = json_encode($contracts, JSON_PRETTY_PRINT);
    file_put_contents($contractsFile, $jsonContent);
}
?>